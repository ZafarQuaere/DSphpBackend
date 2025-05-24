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
        http_response_code(503); // Service Unavailable
        echo json_encode(array(
            "status" => 0,
            "message" => "Database connection failed. Could not connect to the database.", 
            "data" => null
        ));
        exit;
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => 0,
        "message" => "Server error during database initialization: " . $e->getMessage(), 
        "data" => null
    ));
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

// Default welcome message - make it a success response
http_response_code(200);
echo json_encode(array(
    "status" => 1,
    "message" => "Welcome to " . API_NAME,
    "data" => array(
        "name" => API_NAME,
        "version" => API_VERSION,
        "service_status" => "Active", // Renamed from 'status' to avoid conflict with root status
        "base_url" => BASE_URL
    )
));
?> 