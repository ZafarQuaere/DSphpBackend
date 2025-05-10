<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and product model
include_once '../../config/database.php';
include_once '../../models/Product.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create product object
$product = new Product($db);

// Get ID from URL
$product->id = isset($_GET['id']) ? $_GET['id'] : die();

// Read product details
if($product->readOne()) {
    // Create array
    $product_arr = array(
        "id" => $product->id,
        "name" => $product->name,
        "description" => $product->description,
        "price" => $product->price,
        "category" => $product->category_name,
        "image_url" => $product->image_url,
        "stock_quantity" => $product->stock_quantity,
        "featured" => (bool)$product->featured,
        "created_at" => $product->created_at
    );
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Make it json format
    echo json_encode($product_arr);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user product does not exist
    echo json_encode(array("message" => "Product does not exist."));
}
?> 