<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user model
include_once '../../config/database.php';
include_once '../../models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(
    !empty($data->username) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    // Set user property values
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;
    $user->role = isset($data->role) ? $data->role : "USER";
    
    // Validate username and email
    $errors = array();
    
    // Validate username (3-20 characters)
    if(strlen($user->username) < 3 || strlen($user->username) > 20) {
        $errors[] = "Username must be between 3 and 20 characters.";
    }
    
    // Validate email format
    if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Validate password (6-40 characters)
    if(strlen($user->password) < 6 || strlen($user->password) > 40) {
        $errors[] = "Password must be between 6 and 40 characters.";
    }
    
    // Check if username already exists
    if($user->usernameExists()) {
        $errors[] = "Username already exists.";
    }
    
    // Check if email already exists
    if($user->emailExists()) {
        $errors[] = "Email already exists.";
    }
    
    // If no errors, create the user
    if(empty($errors)) {
        // Create the user
        if($user->create()) {
            // Set response code - 201 created
            http_response_code(201);
            
            // Display success message
            echo json_encode(array("message" => "User was successfully registered."));
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);
            
            // Display error message
            echo json_encode(array("message" => "Unable to register the user."));
        }
    } else {
        // Set response code - 400 bad request
        http_response_code(400);
        
        // Display error message
        echo json_encode(array("message" => "Error: " . implode(" ", $errors)));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display error message
    echo json_encode(array("message" => "Unable to register the user. Data is incomplete."));
}
?> 