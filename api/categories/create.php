<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and category model
include_once '../../config/database.php';
include_once '../../models/Category.php';
include_once '../../helpers/image_uploader.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create category object
$category = new Category($db);

// Get posted data
// Assuming 'name', 'description' are sent via POST and 'image' is the file input
if (
    !empty($_POST['name']) &&
    !empty($_POST['description']) &&
    isset($_FILES['image'])
) {
    // Set category property values
    $category->name = $_POST['name'];
    $category->description = $_POST['description'];
    $category->created_at = date('Y-m-d H:i:s');

    // Handle image upload
    $image_uploader = new ImageUploader();
    $upload_result = $image_uploader->upload($_FILES['image']);

    if ($upload_result['status'] === 1) {
        $category->image_url = $upload_result['data']['image_path'];

        // Create the category
        if ($category->create()) {
            http_response_code(201);
            echo json_encode(array(
                "status" => 1,
                "message" => "Category was created.",
                "data" => array("id" => $db->lastInsertId(), "image_url" => $category->image_url)
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("status" => 0, "message" => "Unable to create category.", "data" => null));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("status" => 0, "message" => $upload_result['message'], "data" => null));
    }
} else {
    http_response_code(400);
    echo json_encode(array("status" => 0, "message" => "Unable to create category. Data is incomplete or image not provided.", "data" => null));
}
?> 