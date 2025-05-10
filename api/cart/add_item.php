<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database, models and middleware
include_once '../../config/database.php';
include_once '../../models/Cart.php';
include_once '../../middlewares/AuthMiddleware.php';

// Create auth middleware
$auth = new AuthMiddleware();

// Validate JWT token
$decoded = $auth->validateToken();
if(!$decoded) {
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from token
$user_id = $decoded->data->id;

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(
    !empty($data->product_id) &&
    !empty($data->quantity) &&
    $data->quantity > 0
) {
    // Create cart object
    $cart = new Cart($db);
    
    // Get user cart
    $cart_id = $cart->getUserCart($user_id);
    
    // Add item to cart
    if($cart->addItem($data->product_id, $data->quantity)) {
        // Get updated cart items and totals
        $stmt = $cart->getCartItems();
        $totals = $cart->getCartTotals();
        
        // Prepare response
        $cart_arr = array(
            "id" => $cart_id,
            "user_id" => $user_id,
            "items" => array(),
            "total_price" => $totals['total_price'],
            "total_items" => $totals['total_items']
        );
        
        // Get cart items
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $item = array(
                "id" => $id,
                "product_id" => $product_id,
                "product_name" => $name,
                "image_url" => $image_url,
                "price" => $price,
                "quantity" => $quantity,
                "subtotal" => $price * $quantity
            );
            
            array_push($cart_arr["items"], $item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Return cart data
        echo json_encode($cart_arr);
    } else {
        // Set response code - 503 Service Unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("message" => "Unable to add item to cart."));
    }
} else {
    // Set response code - 400 Bad Request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "Unable to add item to cart. Data is incomplete."));
}
?> 