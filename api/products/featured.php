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

// Get featured products
$stmt = $product->readFeatured();
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    // Products array
    $products_data = array();
    $products_data["products"] = array();
    
    // Retrieve table contents
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "price" => $price,
            "category" => $category_name,
            "image_url" => $image_url,
            "stock_quantity" => $stock_quantity,
            "featured" => (bool)$featured,
            "created_at" => $created_at
        );
        
        array_push($products_data["products"], $product_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show products data in JSON format
    echo json_encode(array(
        "status" => 1,
        "message" => "Featured products retrieved successfully",
        "data" => $products_data
    ));
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no products found
    echo json_encode(array(
        "status" => 0,
        "message" => "No featured products found.",
        "data" => null
    ));
}
?> 