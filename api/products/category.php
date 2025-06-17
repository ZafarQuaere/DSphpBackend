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

// Get category name from URL
$category_name_param = isset($_GET['name']) ? $_GET['name'] : null;

if (is_null($category_name_param)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => 0,
        "message" => "Category name parameter is missing.",
        "data" => null
    ));
    die(); // exit after sending error
}

// Get products by category
$stmt = $product->readByCategory($category_name_param);
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    $products_arr = array();
    $products_arr["records"] = array();

    // Load BASE_URL from .env or use default
    $base_url = getenv('BASE_URL') ?: 'http://localhost:8000';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => html_entity_decode($description),
            "price" => $price,
            "category_id" => $category_id,
            "image_url" => $image_url ? $base_url . '/' . $image_url : null
        );
        array_push($products_arr["records"], $product_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show products data in JSON format
    echo json_encode(array(
        "status" => 1,
        "message" => "Products in category '" . $category_name_param . "' retrieved successfully",
        "data" => $products_arr
    ));
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no products found
    echo json_encode(array(
        "status" => 0,
        "message" => "No products found in category: " . $category_name_param,
        "data" => null
    ));
}
?> 