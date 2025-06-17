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

// Get products
$stmt = $product->read();
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    // Products array
    $products_arr = array();
    $products_arr["products"] = array();
    
    // Load BASE_URL from .env or use default
    $base_url = getenv('BASE_URL') ?: 'http://localhost:8000';

    // Retrieve table contents
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => html_entity_decode($description),
            "price" => $price,
            "category_id" => $category_id,
            "category_name" => $category_name,
            "image_url" => $image_url ? $base_url . '/' . $image_url : null,
            "stock_quantity" => $stock_quantity,
            "featured" => (bool)$featured,
            "created_at" => $created_at
        );
        
        array_push($products_arr["products"], $product_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show products data in JSON format
    echo json_encode(array(
        "status" => 1,
        "message" => "Products retrieved successfully",
        "data" => $products_arr
    ));
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no products found
    echo json_encode(array(
        "status" => 0,
        "message" => "No products found.",
        "data" => null
    ));
}
?> 