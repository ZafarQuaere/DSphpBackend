<?php
/**
 * Dilli Style API Entry Point
 * Production-ready implementation with security and performance optimizations
 */

// Include configuration (handles environment setup and error handling)
require_once __DIR__ . '/config/config.php';

// Set security headers
setSecurityHeaders();

// Include middleware
require_once __DIR__ . '/middlewares/RateLimitMiddleware.php';
require_once __DIR__ . '/middlewares/ValidationMiddleware.php';

// Initialize middleware
$rateLimiter = new RateLimitMiddleware();
$validator = new ValidationMiddleware();

// Apply rate limiting
$rateLimiter->enforce();

// Validate and sanitize request
$validator->validateRequest();

// Handle maintenance mode
if (getenv('MAINTENANCE_MODE') === 'true') {
    http_response_code(503);
    die(json_encode([
        'error' => 'Service Unavailable',
        'message' => getenv('MAINTENANCE_MESSAGE') ?: 'The service is currently under maintenance. Please try again later.',
        'status' => 503
    ]));
}

// Set JSON content type
header('Content-Type: application/json; charset=UTF-8');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', $requestUri);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// Remove query string from path
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

// API Routes
$routes = [
    'GET' => [
        '' => 'handleWelcome',
        'health' => 'handleHealthCheck',
        'api/auth/verify' => 'handleAuthVerify',
        'api/products' => 'handleProductsList',
        'api/products/featured' => 'handleFeaturedProducts',
        'api/categories' => 'handleCategoriesList',
        'api/cart' => 'handleCartGet',
    ],
    'POST' => [
        'api/auth/register' => 'handleAuthRegister',
        'api/auth/login' => 'handleAuthLogin',
        'api/auth/logout' => 'handleAuthLogout',
        'api/auth/refresh' => 'handleAuthRefresh',
        'api/cart/add' => 'handleCartAdd',
        'api/cart/update' => 'handleCartUpdate',
        'api/cart/remove' => 'handleCartRemove',
    ],
    'PUT' => [
        'api/auth/profile' => 'handleProfileUpdate',
    ],
    'DELETE' => [
        'api/cart/clear' => 'handleCartClear',
    ],
    'OPTIONS' => [
        '*' => 'handleOptions',
    ]
];

// Route the request
if (isset($routes[$method])) {
    // Check for exact match
    if (isset($routes[$method][$path])) {
        $handler = $routes[$method][$path];
        $handler();
    } 
    // Check for wildcard OPTIONS
    else if ($method === 'OPTIONS' && isset($routes['OPTIONS']['*'])) {
        handleOptions();
    }
    // Check for dynamic routes (e.g., products/{id})
    else if (preg_match('/^api\/products\/(\d+)$/', $path, $matches)) {
        handleProductDetail($matches[1]);
    }
    else {
        handleNotFound();
    }
} else {
    handleMethodNotAllowed();
}

// Route Handlers
function handleWelcome() {
    // Check database connection
    $dbStatus = checkDatabaseConnection();
    
    http_response_code(200);
    echo json_encode([
        'status' => 1,
        'message' => 'Welcome to ' . API_NAME,
        'data' => [
            'name' => API_NAME,
            'version' => API_VERSION,
            'service_status' => 'Active',
            'base_url' => BASE_URL,
            'database_status' => $dbStatus ? 'Connected' : 'Disconnected',
            'environment' => ENVIRONMENT
        ]
    ]);
}

function handleHealthCheck() {
    $checks = [
        'api' => true,
        'database' => checkDatabaseConnection(),
        'cache' => is_writable(__DIR__ . '/cache'),
        'logs' => is_writable(__DIR__ . '/logs'),
    ];
    
    $healthy = !in_array(false, $checks);
    
    http_response_code($healthy ? 200 : 503);
    echo json_encode([
        'status' => $healthy ? 1 : 0,
        'message' => $healthy ? 'All systems operational' : 'Some systems are down',
        'checks' => $checks,
        'timestamp' => date('c')
    ]);
}

function handleOptions() {
    http_response_code(204);
    exit;
}

function handleNotFound() {
    http_response_code(404);
    echo json_encode([
        'status' => 0,
        'error' => 'Not Found',
        'message' => 'The requested endpoint does not exist.',
        'path' => $_SERVER['REQUEST_URI']
    ]);
}

function handleMethodNotAllowed() {
    header('Allow: ' . implode(', ', array_keys($GLOBALS['routes'])));
    http_response_code(405);
    echo json_encode([
        'status' => 0,
        'error' => 'Method Not Allowed',
        'message' => 'The request method is not supported for this endpoint.',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
}

// Database connection check
function checkDatabaseConnection() {
    try {
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        return $db !== null;
    } catch (Exception $e) {
        error_log('Database connection check failed: ' . $e->getMessage());
        return false;
    }
}

// Include API endpoint handlers
function includeApiHandler($file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 0,
            'error' => 'Internal Server Error',
            'message' => 'API handler not found.'
        ]);
        exit;
    }
}

// Auth endpoints
function handleAuthRegister() {
    includeApiHandler('api/auth/register.php');
}

function handleAuthLogin() {
    includeApiHandler('api/auth/login.php');
}

function handleAuthLogout() {
    includeApiHandler('api/auth/logout.php');
}

function handleAuthVerify() {
    includeApiHandler('api/auth/verify.php');
}

function handleAuthRefresh() {
    includeApiHandler('api/auth/refresh.php');
}

function handleProfileUpdate() {
    includeApiHandler('api/auth/update_profile.php');
}

// Product endpoints
function handleProductsList() {
    includeApiHandler('api/products/read.php');
}

function handleProductDetail($id) {
    $_GET['id'] = $id;
    includeApiHandler('api/products/read_single.php');
}

function handleFeaturedProducts() {
    includeApiHandler('api/products/featured.php');
}

// Category endpoints
function handleCategoriesList() {
    includeApiHandler('api/categories/read.php');
}

// Cart endpoints
function handleCartGet() {
    includeApiHandler('api/cart/read.php');
}

function handleCartAdd() {
    includeApiHandler('api/cart/add.php');
}

function handleCartUpdate() {
    includeApiHandler('api/cart/update.php');
}

function handleCartRemove() {
    includeApiHandler('api/cart/remove.php');
}

function handleCartClear() {
    includeApiHandler('api/cart/clear.php');
}

// Cleanup function for rate limit cache (to be called by cron)
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'rate-limit-cache') {
    if (isset($_GET['key']) && $_GET['key'] === getenv('CLEANUP_KEY')) {
        $cleaned = $rateLimiter->cleanupCache();
        echo json_encode([
            'status' => 1,
            'message' => "Cleaned $cleaned rate limit cache files",
            'timestamp' => date('c')
        ]);
        exit;
    } else {
        handleNotFound();
    }
}
?> 