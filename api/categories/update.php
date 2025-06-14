<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); // Using POST for form-data
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Category.php';
include_once '../../helpers/image_uploader.php';

$database = new Database();
$db = $database->getConnection();

$category = new Category($db);
$image_uploader = new ImageUploader();

$category_id = isset($_POST['id']) ? $_POST['id'] : die(json_encode(array("status" => 0, "message" => "Category ID not provided.")));
$category->id = $category_id;

if (!$category->readOne()) {
    http_response_code(404);
    echo json_encode(array("status" => 0, "message" => "Category not found."));
    exit;
}
$old_image_url = $category->image_url;

$category->name = isset($_POST['name']) ? htmlspecialchars(strip_tags($_POST['name'])) : $category->name;
$category->description = isset($_POST['description']) ? htmlspecialchars(strip_tags($_POST['description'])) : $category->description;

$new_image_uploaded = false;
if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
    $upload_result = $image_uploader->upload($_FILES['category_image']);
    if ($upload_result['status'] === 1) {
        $category->image_url = $upload_result['data']['image_path'];
        $new_image_uploaded = true;
        if ($new_image_uploaded && !empty($old_image_url)) {
            $image_uploader->delete($old_image_url);
        }
    } else {
        http_response_code(400);
        echo json_encode(array("status" => 0, "message" => "Image upload failed: " . $upload_result['message'], "data" => null));
        exit;
    }
} else {
    $category->image_url = $old_image_url;
}

if ($category->update()) {
    http_response_code(200);
    echo json_encode(array(
        "status" => 1, 
        "message" => "Category was updated.",
        "data" => array("id" => $category->id, "image_url" => $category->image_url)
    ));
} else {
    http_response_code(503);
    echo json_encode(array("status" => 0, "message" => "Unable to update category.", "data" => null));
}
?> 