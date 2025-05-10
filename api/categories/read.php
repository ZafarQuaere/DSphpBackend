<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and category model
include_once '../../config/database.php';
include_once '../../models/Category.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create category object
$category = new Category($db);

// Get categories
$stmt = $category->read();
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    // Categories array
    $categories_arr = array();
    $categories_arr["categories"] = array();
    
    // Retrieve table contents
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $category_item = array(
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "image_url" => $image_url,
            "created_at" => $created_at
        );
        
        array_push($categories_arr["categories"], $category_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show categories data in JSON format
    echo json_encode($categories_arr);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no categories found
    echo json_encode(array("message" => "No categories found."));
}
?> 