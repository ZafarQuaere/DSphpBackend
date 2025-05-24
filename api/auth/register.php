<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Try/catch block to catch any exceptions
try {
    // Include database and user model
    include_once '../../config/database.php';
    include_once '../../models/User.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Create user object
    $user = new User($db);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Make sure data is not empty
    $validation_errors = array(); // Use an associative array for field-specific errors

    if (empty($data->username)) {
        $validation_errors[] = "Username is required.";
    }
    if (empty($data->email)) {
        $validation_errors[] = "Email is required.";
    }
    if (empty($data->password)) {
        $validation_errors[] = "Password is required.";
    }

    if (!empty($validation_errors)) {
        http_response_code(400);
        echo json_encode(array(
            "status" => 0,
            "message" => "Validation failed: " . implode(" ", $validation_errors),
            "data" => null // Changed from "errors" to "data"
        ));
        exit;
    }
    
    // Set user property values
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;
    $user->role = isset($data->role) ? $data->role : "USER";
    
    // Further field-specific validations
    $validation_errors = array(); // Reset for specific validations
    if(strlen($user->username) < 3 || strlen($user->username) > 20) {
        $validation_errors[] = "Username must be between 3 and 20 characters.";
    }
    if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Invalid email format.";
    }
    if(strlen($user->password) < 6 || strlen($user->password) > 40) {
        $validation_errors[] = "Password must be between 6 and 40 characters.";
    }
    if($user->usernameExists()) {
        $validation_errors[] = "Username already exists.";
    }
    if($user->emailExists()) {
        $validation_errors[] = "Email already exists.";
    }
    
    if (!empty($validation_errors)) {
        http_response_code(400);
        echo json_encode(array(
            "status" => 0,
            "message" => "Validation failed: " . implode(" ", $validation_errors),
            "data" => null // Changed from "errors" to "data"
        ));
        exit;
    }

    // Create the user
    if($user->create()) {
        http_response_code(201);
        echo json_encode(array(
            "status" => 1,
            "message" => "User was successfully registered.",
            // As per user initial request, data should have id, type, attributes.
            // Assuming $user->id is populated after creation.
            "data" => array(
                "id" => $user->id, // Assuming $user object has id property populated after create()
                "type" => "user",
                "attributes" => array(
                    "username" => $user->username,
                    "email" => $user->email,
                    "role" => $user->role
                )
            )
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => 0,
            "message" => "Unable to register the user. A service error occurred.",
            "data" => null // Changed from "errors" to "data"
        ));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => 0,
        "message" => "Server error occurred: " . $e->getMessage(), 
        "data" => null // Changed from "errors" to "data"
    ));
}
?> 