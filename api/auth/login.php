<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database, user model and jwt utilities
include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../config/jwt.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate presence of username and password
$errors = array();
if (empty($data->username)) {
    $errors[] = "Username is required.";
}
if (empty($data->password)) {
    $errors[] = "Password is required.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => 0,
        "message" => "Validation failed: " . implode(" ", $errors),
        "data" => null
    ));
    exit; 
}

// Set user property values
$user->username = $data->username;

// Check if username exists and get user details
if($user->usernameExists()) {
    // Verify the password
    if(password_verify($data->password, $user->password)) {
        // Create JWT token
        $jwt = new JwtUtil();
        $token = $jwt->generateJwtToken($user->id, $user->username, $user->email, $user->role);
        
        http_response_code(200);
        echo json_encode(array(
            "status" => 1,
            "message" => "Login successful.",
            "data" => array(
                "token" => $token,
                "type" => "Bearer",
                "user" => array( // Nest user details under a 'user' key for better structure
                    "id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email,
                    "role" => $user->role
                )
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array(
            "status" => 0,
            "message" => "Login failed. Invalid username or password.",
            "data" => null
        ));
    }
} else {
    http_response_code(401);
    echo json_encode(array(
        "status" => 0,
        "message" => "Login failed. Invalid username or password.",
        "data" => null
    ));
}
?> 