<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and product model
include_once '../../config/database.php';
include_once '../../models/Product.php';
include_once '../../helpers/image_uploader.php'; 

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create product object
$product = new Product($db);
$image_uploader = new ImageUploader();

// Get product ID from URL or request body
$product->id = isset($_GET['id']) ? $_GET['id'] : die(json_encode(array("status" => 0, "message" => "Product ID not provided.")));

// First, get product details to find the image URL for deletion
if ($product->readOne()) {
    $image_to_delete = $product->image_url;

    // Delete the product from database
    if ($product->delete()) {
        // If product deletion is successful, try to delete the image file
        if (!empty($image_to_delete)) {
            $image_uploader->delete($image_to_delete); // We don't strictly need to check the result here, 
                                                    // as the main operation (DB delete) was successful.
        }
        http_response_code(200);
        echo json_encode(array("status" => 1, "message" => "Product was deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("status" => 0, "message" => "Unable to delete product.", "data" => null));
    }
} else {
    http_response_code(404);
    echo json_encode(array("status" => 0, "message" => "Product not found.", "data" => null));
}
?> 