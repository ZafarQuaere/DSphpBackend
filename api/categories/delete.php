<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Category.php';
include_once '../../helpers/image_uploader.php';

$database = new Database();
$db = $database->getConnection();

$category = new Category($db);
$image_uploader = new ImageUploader();

$category->id = isset($_GET['id']) ? $_GET['id'] : die(json_encode(array("status" => 0, "message" => "Category ID not provided.")));

if ($category->readOne()) {
    $image_to_delete = $category->image_url;
    if ($category->delete()) {
        if (!empty($image_to_delete)) {
            $image_uploader->delete($image_to_delete);
        }
        http_response_code(200);
        echo json_encode(array("status" => 1, "message" => "Category was deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("status" => 0, "message" => "Unable to delete category.", "data" => null));
    }
} else {
    http_response_code(404);
    echo json_encode(array("status" => 0, "message" => "Category not found.", "data" => null));
}
?> 