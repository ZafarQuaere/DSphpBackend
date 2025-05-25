<?php
require_once __DIR__ . '/../config/config.php';

class RateLimitMiddleware {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/rate_limit/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
            // Protect cache directory
            file_put_contents($this->cacheDir . '/.htaccess', "Deny from all\n");
        }
    }
    
    /**
     * Check rate limit for the current request
     * @param string $identifier User identifier (IP address or user ID)
     * @param string $endpoint Optional endpoint-specific rate limiting
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int]
     */
    public function checkLimit($identifier = null, $endpoint = null) {
        // Get identifier (default to IP address)
        if (!$identifier) {
            $identifier = $this->getClientIp();
        }
        
        // Create a unique key for this rate limit
        $key = $endpoint ? $identifier . '_' . $endpoint : $identifier;
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        // Get current time
        $now = time();
        
        // Get rate limit settings
        $maxRequests = RATE_LIMIT_REQUESTS;
        $window = RATE_LIMIT_WINDOW;
        
        // Special limits for sensitive endpoints
        if ($endpoint) {
            switch ($endpoint) {
                case 'auth/login':
                case 'auth/register':
                    $maxRequests = 5;
                    $window = 300; // 5 minutes
                    break;
                case 'auth/forgot-password':
                    $maxRequests = 3;
                    $window = 3600; // 1 hour
                    break;
            }
        }
        
        // Read existing rate limit data
        $data = $this->getRateLimitData($cacheFile);
        
        // Clean up old entries
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Count requests in current window
        $requestCount = count($data['requests']);
        
        // Check if limit exceeded
        if ($requestCount >= $maxRequests) {
            // Find oldest request to determine reset time
            $oldestRequest = min($data['requests']);
            $resetTime = $oldestRequest + $window;
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => $resetTime,
                'retry_after' => $resetTime - $now
            ];
        }
        
        // Add current request
        $data['requests'][] = $now;
        $this->saveRateLimitData($cacheFile, $data);
        
        // Calculate remaining requests
        $remaining = $maxRequests - count($data['requests']);
        
        // Calculate reset time (when the oldest request expires)
        $resetTime = !empty($data['requests']) ? min($data['requests']) + $window : $now + $window;
        
        return [
            'allowed' => true,
            'remaining' => $remaining,
            'reset' => $resetTime,
            'retry_after' => 0
        ];
    }
    
    /**
     * Apply rate limiting to the current request
     * @param string $identifier User identifier
     * @param string $endpoint Endpoint being accessed
     */
    public function enforce($identifier = null, $endpoint = null) {
        $result = $this->checkLimit($identifier, $endpoint);
        
        // Set rate limit headers
        header('X-RateLimit-Limit: ' . RATE_LIMIT_REQUESTS);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset']);
        
        if (!$result['allowed']) {
            // Rate limit exceeded
            header('Retry-After: ' . $result['retry_after']);
            http_response_code(429);
            
            die(json_encode([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $result['retry_after']
            ]));
        }
    }
    
    /**
     * Get client IP address
     * @return string
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Get rate limit data from cache
     * @param string $cacheFile
     * @return array
     */
    private function getRateLimitData($cacheFile) {
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (is_array($data) && isset($data['requests'])) {
                return $data;
            }
        }
        
        return ['requests' => []];
    }
    
    /**
     * Save rate limit data to cache
     * @param string $cacheFile
     * @param array $data
     */
    private function saveRateLimitData($cacheFile, $data) {
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);
    }
    
    /**
     * Clean up old cache files
     * Should be called periodically (e.g., via cron job)
     */
    public function cleanupCache() {
        $files = glob($this->cacheDir . '*.json');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = $this->getRateLimitData($file);
            
            // If all requests are older than the window, delete the file
            if (empty($data['requests']) || max($data['requests']) < ($now - RATE_LIMIT_WINDOW)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Reset rate limit for a specific identifier
     * @param string $identifier
     * @param string $endpoint
     */
    public function reset($identifier, $endpoint = null) {
        $key = $endpoint ? $identifier . '_' . $endpoint : $identifier;
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
?> 