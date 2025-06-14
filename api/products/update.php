<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); // Should be PUT or POST, using POST for simplicity with form-data
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

// Get ID of product to be edited from URL or request body
// For POST with form-data, it's better to get it from $_POST
$product_id = isset($_POST['id']) ? $_POST['id'] : die(json_encode(array("status" => 0, "message" => "Product ID not provided.")));
$product->id = $product_id;

// Get current product details to find the old image if it's being replaced
if (!$product->readOne()) {
    http_response_code(404);
    echo json_encode(array("status" => 0, "message" => "Product not found."));
    exit;
}
$old_image_url = $product->image_url;

// Get data from POST request (since we expect form-data for file upload)
// Sanitize and set product property values from $_POST
$product->name = isset($_POST['name']) ? htmlspecialchars(strip_tags($_POST['name'])) : $product->name;
$product->description = isset($_POST['description']) ? htmlspecialchars(strip_tags($_POST['description'])) : $product->description;
$product->price = isset($_POST['price']) ? htmlspecialchars(strip_tags($_POST['price'])) : $product->price;
$product->category_id = isset($_POST['category_id']) ? htmlspecialchars(strip_tags($_POST['category_id'])) : $product->category_id; // Assuming category_id is sent
$product->stock_quantity = isset($_POST['stock_quantity']) ? htmlspecialchars(strip_tags($_POST['stock_quantity'])) : $product->stock_quantity;
$product->featured = isset($_POST['featured']) ? htmlspecialchars(strip_tags($_POST['featured'])) : $product->featured;

// Handle image upload if a new image is provided
$new_image_uploaded = false;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $upload_result = $image_uploader->upload($_FILES['image']);
    if ($upload_result['status'] === 1) {
        $product->image_url = $upload_result['data']['image_path'];
        $new_image_uploaded = true;
        // If a new image is uploaded and there was an old one, delete the old one
        if ($new_image_uploaded && !empty($old_image_url)) {
            $image_uploader->delete($old_image_url);
        }
    } else {
        http_response_code(400);
        echo json_encode(array("status" => 0, "message" => "Image upload failed: " . $upload_result['message'], "data" => null));
        exit;
    }
} else {
    // Keep the old image URL if no new image is uploaded
    $product->image_url = $old_image_url;
}

// Update the product
if ($product->update()) {
    http_response_code(200);
    echo json_encode(array(
        "status" => 1, 
        "message" => "Product was updated.",
        "data" => array("id" => $product->id, "image_url" => $product->image_url)
    ));
} else {
    http_response_code(503);
    echo json_encode(array("status" => 0, "message" => "Unable to update product.", "data" => null));
}
?> 