<?php
require_once '../config/jwt.php';

class AuthMiddleware {
    private $jwt;
    
    public function __construct() {
        $this->jwt = new JwtUtil();
    }
    
    // Check if request has valid JWT token
    public function validateToken() {
        // Get all headers
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Check if authorization header exists
        if(!$authHeader) {
            return $this->sendError("Access denied. Authorization header not found.");
        }
        
        // Check if it's a bearer token
        $parts = explode(" ", $authHeader);
        if(count($parts) != 2 || $parts[0] != 'Bearer') {
            return $this->sendError("Access denied. Invalid token format.");
        }
        
        $token = $parts[1];
        
        // Validate JWT token
        $decodedToken = $this->jwt->validateToken($token);
        
        if(!$decodedToken) {
            return $this->sendError("Access denied. Invalid or expired token.");
        }
        
        // Token is valid, return decoded data
        return $decodedToken;
    }
    
    // Check if user has admin role
    public function validateAdmin($decoded) {
        if($decoded->data->role !== 'ADMIN') {
            $this->sendError("Access denied. Admin privileges required.");
            return false;
        }
        return true;
    }
    
    // Format error response
    private function sendError($message) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(array(
            "message" => $message
        ));
        return false;
    }
}
?> 