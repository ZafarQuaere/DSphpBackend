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

// Get search keyword
$keyword = isset($_GET['query']) ? $_GET['query'] : "";

if(empty($keyword)) {
    // Set response code - 400 Bad Request
    http_response_code(400);
    echo json_encode(array("message" => "Missing search query parameter."));
    exit;
}

// Search products
$stmt = $product->search($keyword);
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    // Products array
    $products_arr = array();
    $products_arr["products"] = array();
    
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
        
        array_push($products_arr["products"], $product_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show products data in JSON format
    echo json_encode($products_arr);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no products found
    echo json_encode(array("message" => "No products found matching: " . $keyword));
}
?> 