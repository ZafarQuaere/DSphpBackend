<?php

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Try database connection to see if that's the issue
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode([
            "error" => "Database connection failed", 
            "details" => "Could not connect to the database"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        "error" => "Exception caught", 
        "message" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
    exit;
}

// Include config
require_once 'config/config.php';

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

echo json_encode(array(
    "message" => "Welcome to " . API_NAME,
    "version" => API_VERSION,
    "status" => "Active",
    "base_url" => BASE_URL
));
?> 