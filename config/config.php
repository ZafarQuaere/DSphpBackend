<?php
// Load environment variables from .env file if it exists
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variable
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
}

// Load environment variables
loadEnv();

// API Information
define('API_VERSION', getenv('API_VERSION') ? getenv('API_VERSION') : '1.0.0');
define('API_NAME', getenv('API_NAME') ? getenv('API_NAME') : 'Dilli Style API');
define('BASE_URL', getenv('BASE_URL') ? getenv('BASE_URL') : 'https://backend.dillistyle.shop');

// JWT Secret
define('JWT_SECRET', getenv('JWT_SECRET') ? getenv('JWT_SECRET') : 'your_default_jwt_secret_key');
?> 