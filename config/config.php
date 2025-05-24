<?php
// Production configuration file with enhanced security and error handling

// Detect environment
define('ENVIRONMENT', getenv('ENVIRONMENT') ? getenv('ENVIRONMENT') : 'production');

// Error reporting based on environment
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Load environment variables from .env file if it exists
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        // Check file permissions for security
        $perms = fileperms($envFile);
        if (($perms & 0x0040) || ($perms & 0x0004)) {
            // File is readable by group or others - security risk in production
            if (ENVIRONMENT === 'production') {
                error_log("WARNING: .env file has insecure permissions. Should be 600 or 640.");
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variable
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                    $value = trim($value, '"\'');
                }
                
                // Set environment variable if not already set
                if (!getenv($name)) {
                    putenv("$name=$value");
                }
            }
        }
    } else if (ENVIRONMENT === 'production') {
        die(json_encode(['error' => 'Configuration error. Please contact administrator.']));
    }
}

// Load environment variables
loadEnv();

// Validate required environment variables
$requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET'];
foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        error_log("Missing required environment variable: $var");
        if (ENVIRONMENT === 'production') {
            die(json_encode(['error' => 'Configuration error. Please contact administrator.']));
        } else {
            die("Missing required environment variable: $var");
        }
    }
}

// Security: Validate JWT secret strength
if (strlen(getenv('JWT_SECRET')) < 32) {
    error_log("JWT_SECRET is too short. Should be at least 32 characters for production.");
    if (ENVIRONMENT === 'production') {
        die(json_encode(['error' => 'Configuration error. Please contact administrator.']));
    }
}

// API Information
define('API_VERSION', getenv('API_VERSION') ? getenv('API_VERSION') : '1.0.0');
define('API_NAME', getenv('API_NAME') ? getenv('API_NAME') : 'Dilli Style API');
define('BASE_URL', getenv('BASE_URL') ? getenv('BASE_URL') : 'https://backend.dillistyle.shop');

// JWT Configuration
define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_EXPIRY', getenv('JWT_EXPIRY') ? (int)getenv('JWT_EXPIRY') : 3600); // 1 hour default
define('JWT_REFRESH_EXPIRY', getenv('JWT_REFRESH_EXPIRY') ? (int)getenv('JWT_REFRESH_EXPIRY') : 604800); // 7 days default

// Security Settings
define('MAX_LOGIN_ATTEMPTS', getenv('MAX_LOGIN_ATTEMPTS') ? (int)getenv('MAX_LOGIN_ATTEMPTS') : 5);
define('LOCKOUT_TIME', getenv('LOCKOUT_TIME') ? (int)getenv('LOCKOUT_TIME') : 900); // 15 minutes default
define('RATE_LIMIT_REQUESTS', getenv('RATE_LIMIT_REQUESTS') ? (int)getenv('RATE_LIMIT_REQUESTS') : 100);
define('RATE_LIMIT_WINDOW', getenv('RATE_LIMIT_WINDOW') ? (int)getenv('RATE_LIMIT_WINDOW') : 900); // 15 minutes

// CORS Settings
define('ALLOWED_ORIGINS', getenv('ALLOWED_ORIGINS') ? explode(',', getenv('ALLOWED_ORIGINS')) : ['https://dillistyle.shop', 'https://www.dillistyle.shop']);
define('ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Request Settings
define('MAX_REQUEST_SIZE', getenv('MAX_REQUEST_SIZE') ? (int)getenv('MAX_REQUEST_SIZE') : 10485760); // 10MB default

// Cache Settings
define('CACHE_ENABLED', getenv('CACHE_ENABLED') ? filter_var(getenv('CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN) : true);
define('CACHE_EXPIRY', getenv('CACHE_EXPIRY') ? (int)getenv('CACHE_EXPIRY') : 3600);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Security Headers Function
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none';");
    
    // Strict Transport Security (HSTS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Referrer Policy
    header('Referrer-Policy: no-referrer-when-downgrade');
    
    // Remove PHP version
    header_remove('X-Powered-By');
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'time' => date('Y-m-d H:i:s')
    ];
    
    error_log(json_encode($error));
    
    if (ENVIRONMENT !== 'production') {
        return false; // Let PHP handle it normally in development
    }
    
    // In production, return generic error
    http_response_code(500);
    die(json_encode(['error' => 'An error occurred. Please try again later.']));
}

// Set custom error handler
set_error_handler('customErrorHandler');

// Custom exception handler
function customExceptionHandler($exception) {
    $error = [
        'type' => 'Exception',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'time' => date('Y-m-d H:i:s')
    ];
    
    error_log(json_encode($error));
    
    if (ENVIRONMENT !== 'production') {
        die(json_encode($error));
    }
    
    // In production, return generic error
    http_response_code(500);
    die(json_encode(['error' => 'An error occurred. Please try again later.']));
}

// Set custom exception handler
set_exception_handler('customExceptionHandler');

// Shutdown function to catch fatal errors
function shutdownHandler() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        customErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

// Register shutdown function
register_shutdown_function('shutdownHandler');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
    // Create .htaccess to protect logs directory
    file_put_contents($logsDir . '/.htaccess', "Deny from all\n");
}
?> 