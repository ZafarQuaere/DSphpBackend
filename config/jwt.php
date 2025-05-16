<?php
class JwtUtil {
    private $key;
    private $issuer = "dilli_style_api";
    private $audience = "dilli_style_client";
    private $algorithm = 'HS256';
    private $tokenExpiry = 3600; // 1 hour

    public function __construct() {
        // Get JWT secret key from environment variable
        $this->key = defined('JWT_SECRET') ? JWT_SECRET : "dilli_style_secret_key";
    }

    // Generate JWT token
    public function generateJwtToken($userId, $username, $email, $role) {
        $issuedAt = time();
        $expiryTime = $issuedAt + $this->tokenExpiry;

        $payload = array(
            "iss" => $this->issuer,
            "aud" => $this->audience,
            "iat" => $issuedAt,
            "exp" => $expiryTime,
            "data" => array(
                "id" => $userId,
                "username" => $username,
                "email" => $email,
                "role" => $role
            )
        );

        // Custom simple JWT encode function
        return $this->customEncode($payload);
    }

    // Validate JWT token
    public function validateToken($jwt) {
        try {
            $decoded = $this->customDecode($jwt);
            
            // Check if token is expired
            if(isset($decoded->exp) && $decoded->exp < time()) {
                return false;
            }
            
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Custom JWT encode function
    private function customEncode($payload) {
        $header = json_encode(['alg' => $this->algorithm, 'typ' => 'JWT']);
        $payload = json_encode($payload);
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    // Custom JWT decode function
    private function customDecode($jwt) {
        $tokenParts = explode(".", $jwt);
        
        if(count($tokenParts) != 3) {
            throw new Exception("Invalid token format");
        }
        
        $header = $this->base64UrlDecode($tokenParts[0]);
        $payload = $this->base64UrlDecode($tokenParts[1]);
        $signature = $this->base64UrlDecode($tokenParts[2]);
        
        // Verify signature
        $base64UrlHeader = $tokenParts[0];
        $base64UrlPayload = $tokenParts[1];
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->key, true);
        
        if($signature !== $expectedSignature) {
            throw new Exception("Invalid signature");
        }
        
        return json_decode($payload);
    }
    
    // Base64Url encode
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    // Base64Url decode
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?> 