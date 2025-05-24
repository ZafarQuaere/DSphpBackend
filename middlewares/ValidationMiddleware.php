<?php
require_once __DIR__ . '/../config/config.php';

class ValidationMiddleware {
    
    /**
     * Sanitize and validate all input data
     */
    public function validateRequest() {
        // Check request size
        $this->checkRequestSize();
        
        // Sanitize all input data
        $_GET = $this->sanitizeData($_GET);
        $_POST = $this->sanitizeData($_POST);
        $_REQUEST = $this->sanitizeData($_REQUEST);
        
        // Validate content type for POST/PUT requests
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
            $this->validateContentType();
        }
        
        // Validate JSON input if present
        $this->validateJsonInput();
    }
    
    /**
     * Check if request size exceeds limit
     */
    private function checkRequestSize() {
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        
        if ($contentLength > MAX_REQUEST_SIZE) {
            http_response_code(413);
            die(json_encode([
                'error' => 'Request entity too large',
                'message' => 'Request size exceeds maximum allowed size.',
                'max_size' => MAX_REQUEST_SIZE
            ]));
        }
    }
    
    /**
     * Validate content type
     */
    private function validateContentType() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Extract main content type (before semicolon)
        $mainContentType = explode(';', $contentType)[0];
        $mainContentType = trim(strtolower($mainContentType));
        
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data'
        ];
        
        if (!in_array($mainContentType, $allowedTypes)) {
            http_response_code(415);
            die(json_encode([
                'error' => 'Unsupported media type',
                'message' => 'Content-Type must be one of: ' . implode(', ', $allowedTypes)
            ]));
        }
    }
    
    /**
     * Validate JSON input
     */
    private function validateJsonInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            
            if (!empty($input)) {
                $decoded = json_decode($input, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    die(json_encode([
                        'error' => 'Invalid JSON',
                        'message' => 'Request body contains invalid JSON: ' . json_last_error_msg()
                    ]));
                }
                
                // Sanitize JSON data and merge with $_POST
                $_POST = array_merge($_POST, $this->sanitizeData($decoded));
                $_REQUEST = array_merge($_REQUEST, $_POST);
            }
        }
    }
    
    /**
     * Recursively sanitize data
     * @param mixed $data
     * @return mixed
     */
    public function sanitizeData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // Sanitize key
                $cleanKey = $this->sanitizeString($key);
                unset($data[$key]);
                
                // Sanitize value
                $data[$cleanKey] = $this->sanitizeData($value);
            }
        } else if (is_string($data)) {
            $data = $this->sanitizeString($data);
        }
        
        return $data;
    }
    
    /**
     * Sanitize string input
     * @param string $string
     * @return string
     */
    private function sanitizeString($string) {
        // Remove null bytes
        $string = str_replace(chr(0), '', $string);
        
        // Strip tags and encode special characters
        $string = strip_tags($string);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        
        // Remove any potential SQL injection attempts
        $string = $this->preventSqlInjection($string);
        
        return $string;
    }
    
    /**
     * Basic SQL injection prevention
     * @param string $string
     * @return string
     */
    private function preventSqlInjection($string) {
        // Common SQL injection patterns
        $patterns = [
            '/(\bunion\b.*\bselect\b|\bselect\b.*\bunion\b)/i',
            '/(\bdrop\b.*\btable\b|\btruncate\b.*\btable\b)/i',
            '/(\/\*.*?\*\/|--.*?$)/m',
            '/\b(script|javascript|vbscript)\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                // Log potential attack
                error_log("Potential SQL injection attempt detected: " . substr($string, 0, 100));
                
                // Remove the malicious pattern
                $string = preg_replace($pattern, '', $string);
            }
        }
        
        return $string;
    }
    
    /**
     * Validate email address
     * @param string $email
     * @return bool
     */
    public function validateEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (Indian format)
     * @param string $phone
     * @return bool
     */
    public function validatePhone($phone) {
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Indian phone number pattern
        $pattern = '/^(\+91|91|0)?[6-9]\d{9}$/';
        return preg_match($pattern, $phone);
    }
    
    /**
     * Validate password strength
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate required fields
     * @param array $data
     * @param array $required
     * @return array ['valid' => bool, 'missing' => array]
     */
    public function validateRequired($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }
    
    /**
     * Validate numeric value
     * @param mixed $value
     * @param float $min
     * @param float $max
     * @return bool
     */
    public function validateNumeric($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            return false;
        }
        
        $value = (float)$value;
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date format
     * @param string $date
     * @param string $format
     * @return bool
     */
    public function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate URL
     * @param string $url
     * @return bool
     */
    public function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate file upload
     * @param array $file $_FILES array element
     * @param array $allowedTypes
     * @param int $maxSize in bytes
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => 'File upload failed with error code: ' . $file['error']
            ];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($maxSize / 1048576) . 'MB'
            ];
        }
        
        // Check file type if specified
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return [
                    'valid' => false,
                    'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)
                ];
            }
        }
        
        // Additional security check for executable files
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar', 'exe', 'sh', 'bat'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed for security reasons'
            ];
        }
        
        return ['valid' => true, 'error' => null];
    }
}
?> 