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

// Make sure data is not empty
if(!empty($data->username) && !empty($data->password)) {
    // Set user property values
    $user->username = $data->username;
    
    // Check if username exists and get user details
    if($user->usernameExists()) {
        // Verify the password
        if(password_verify($data->password, $user->password)) {
            // Create JWT token
            $jwt = new JwtUtil();
            $token = $jwt->generateJwtToken($user->id, $user->username, $user->email, $user->role);
            
            // Set response code - 200 OK
            http_response_code(200);
            
            // Display JWT token
            echo json_encode(array(
                "token" => $token,
                "type" => "Bearer",
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "role" => $user->role
            ));
        } else {
            // Set response code - 401 Unauthorized
            http_response_code(401);
            
            // Display error message
            echo json_encode(array("message" => "Login failed. Invalid password."));
        }
    } else {
        // Set response code - 401 Unauthorized
        http_response_code(401);
        
        // Display error message
        echo json_encode(array("message" => "Login failed. User not found."));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display error message
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?> 