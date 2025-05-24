<?php
/**
 * Security Helper Functions
 * Common security utilities for the application
 */

/**
 * Generate a secure random token
 * @param int $length
 * @return string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 * @param string $hash
 * @return bool
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Sanitize output for HTML
 * @param string $data
 * @return string
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Encrypt data
 * @param string $data
 * @param string $key
 * @return string
 */
function encryptData($data, $key = null) {
    if (!$key) {
        $key = getenv('ENCRYPTION_KEY') ?: JWT_SECRET;
    }
    
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

/**
 * Decrypt data
 * @param string $data
 * @param string $key
 * @return string|false
 */
function decryptData($data, $key = null) {
    if (!$key) {
        $key = getenv('ENCRYPTION_KEY') ?: JWT_SECRET;
    }
    
    $c = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len = 32);
    $ciphertext_raw = substr($c, $ivlen + $sha2len);
    
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    
    if (hash_equals($hmac, $calcmac)) {
        return $original_plaintext;
    }
    
    return false;
}

/**
 * Validate IP address
 * @param string $ip
 * @return bool
 */
function validateIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/**
 * Check if request is from allowed IP
 * @param string $ip
 * @param array $whitelist
 * @return bool
 */
function isAllowedIP($ip, $whitelist = []) {
    if (empty($whitelist)) {
        return true; // No whitelist means all IPs are allowed
    }
    
    foreach ($whitelist as $allowed) {
        if (strpos($allowed, '/') !== false) {
            // CIDR notation
            list($subnet, $mask) = explode('/', $allowed);
            if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
                return true;
            }
        } else {
            // Single IP
            if ($ip === $allowed) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Log security event
 * @param string $event
 * @param array $data
 */
function logSecurityEvent($event, $data = []) {
    $logData = [
        'event' => $event,
        'timestamp' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'data' => $data
    ];
    
    $logFile = __DIR__ . '/../logs/security.log';
    error_log(json_encode($logData) . PHP_EOL, 3, $logFile);
}

/**
 * Check for common attack patterns
 * @param string $input
 * @return bool Returns true if attack pattern detected
 */
function detectAttackPattern($input) {
    $patterns = [
        // SQL Injection patterns
        '/(\bunion\b.*\bselect\b|\bselect\b.*\bunion\b)/i',
        '/(\bdrop\b.*\btable\b|\btruncate\b.*\btable\b)/i',
        '/(\bexec\b|\bexecute\b).*\(/i',
        '/\b(script|javascript|vbscript|onload|onerror|onclick)\b/i',
        
        // XSS patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/on\w+\s*=/i',
        
        // Path traversal
        '/\.\.\/|\.\.\\\\/',
        
        // Command injection
        '/[;&|]\s*(cat|ls|pwd|whoami|id|uname)/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate secure filename
 * @param string $filename
 * @return string
 */
function generateSecureFilename($filename) {
    // Get extension
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
    
    // Generate unique name
    $name = generateSecureToken(16);
    
    return $name . ($ext ? '.' . $ext : '');
}

/**
 * Validate file upload security
 * @param string $tmpFile
 * @param array $allowedTypes
 * @return bool
 */
function validateFileUploadSecurity($tmpFile, $allowedTypes = []) {
    // Check if file exists
    if (!file_exists($tmpFile)) {
        return false;
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpFile);
    finfo_close($finfo);
    
    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Check for PHP code in file
    $content = file_get_contents($tmpFile);
    if (preg_match('/<\?php|<\?=/i', $content)) {
        return false;
    }
    
    return true;
}
?> 