<?php
class ImageUploader {
    private $target_dir = "../../uploads/images/"; // Relative to the location of this helper file
    private $max_file_size = 5000000; // 5MB
    private $allowed_types = array("jpg", "jpeg", "png", "gif");

    public function __construct() {
        // Create the target directory if it doesn't exist
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir, 0777, true);
        }
    }

    public function upload($file) {
        $target_file = $this->target_dir . basename($file["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($file["tmp_name"]);

        if ($check === false) {
            return array("status" => 0, "message" => "File is not an image.");
        }

        if ($file["size"] > $this->max_file_size) {
            return array("status" => 0, "message" => "Sorry, your file is too large. Max size is " . ($this->max_file_size / 1000000) . "MB.");
        }

        if (!in_array($imageFileType, $this->allowed_types)) {
            return array("status" => 0, "message" => "Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        }

        // Create a unique file name to prevent overwriting existing files
        $new_file_name = uniqid('img_', true) . '.' . $imageFileType;
        $final_target_file = $this->target_dir . $new_file_name;

        if (move_uploaded_file($file["tmp_name"], $final_target_file)) {
            // Return the relative path from the api/products/create.php perspective
            $relative_path = 'uploads/images/' . $new_file_name; 
            return array(
                "status" => 1, 
                "message" => "The file " . htmlspecialchars(basename($file["name"])) . " has been uploaded.",
                "data" => array("image_path" => $relative_path)
            );
        } else {
            return array("status" => 0, "message" => "Sorry, there was an error uploading your file.");
        }
    }

    public function delete($image_path) {
        // Construct the full path to the image file from the project root
        // Assumes $image_path is like 'uploads/images/img_xxxxxxxx.jpg'
        $full_path = dirname(__DIR__, 2) . '/' . $image_path; // Go up two directories from helpers to the project root

        if (file_exists($full_path)) {
            if (unlink($full_path)) {
                return array("status" => 1, "message" => "Image deleted successfully.");
            } else {
                return array("status" => 0, "message" => "Error deleting image file.");
            }
        } else {
            return array("status" => 0, "message" => "Image file not found for deletion.");
        }
    }
}
?> 