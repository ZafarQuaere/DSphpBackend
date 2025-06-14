<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and product model
include_once '../../config/database.php';
include_once '../../models/Product.php';
include_once '../../helpers/image_uploader.php'; // We will create this helper

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create product object
$product = new Product($db);

// Get posted data
// $data = json_decode(file_get_contents("php://input")); // Removed this line

// Check if data is not empty and image is set
if (
    !empty($_POST['name']) &&
    !empty($_POST['description']) &&
    !empty($_POST['price']) &&
    !empty($_POST['category_id']) &&
    !empty($_POST['stock_quantity']) &&
    isset($_FILES['image']) && $_FILES['image']['error'] == 0
) {
    // Set product property values
    $product->name = $_POST['name'];
    $product->description = $_POST['description'];
    $product->price = $_POST['price'];
    $product->category_id = $_POST['category_id'];
    $product->stock_quantity = $_POST['stock_quantity'];
    $product->featured = isset($_POST['featured']) ? $_POST['featured'] : 0; // Default to not featured
    $product->created_at = date('Y-m-d H:i:s');

    // Handle image upload
    $image_uploader = new ImageUploader();
    $upload_result = $image_uploader->upload($_FILES['image']);

    if ($upload_result['status'] === 1) {
        $product->image_url = $upload_result['data']['image_path']; // Get the path of the uploaded image

        // Create the product
        if ($product->create()) {
            // Set response code - 201 created
            http_response_code(201);
            echo json_encode(array(
                "status" => 1,
                "message" => "Product was created.",
                "data" => array("id" => $db->lastInsertId(), "image_url" => $product->image_url) 
            ));
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);
            echo json_encode(array("status" => 0, "message" => "Unable to create product.", "data" => null));
        }
    } else {
        // Set response code - 400 bad request (image upload failed)
        http_response_code(400);
        echo json_encode(array("status" => 0, "message" => $upload_result['message'], "data" => null));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("status" => 0, "message" => "Unable to create product. Data is incomplete or image not provided.", "data" => null));
}
?> 