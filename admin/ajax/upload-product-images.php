<?php
require_once "../../includes.php";
session_start();

// Check if user is logged in
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Validate product ID
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$productId = (int) $_POST['product_id'];

// Check if files were uploaded
if (empty($_FILES['images'])) {
    echo json_encode(['success' => false, 'error' => 'No files were uploaded']);
    exit;
}

// Define directories
$productDir = "../../products/product-{$productId}";
$imageDir = "{$productDir}/product-{$productId}-image";
$resizedDir = "{$imageDir}/resized";

// Create directories if they don't exist
if (!file_exists($productDir)) {
    mkdir($productDir, 0755, true);
}
if (!file_exists($imageDir)) {
    mkdir($imageDir, 0755, true);
}
if (!file_exists($resizedDir)) {
    mkdir($resizedDir, 0755, true);
}

$uploadedFiles = [];
$errors = [];

// Process each uploaded file
foreach ($_FILES['images']['name'] as $key => $name) {
    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['images']['tmp_name'][$key];
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name); // Sanitize filename
        $name = time() . '-' . $name; // Add timestamp to avoid filename conflicts

        // Get file extension
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // Check if file is an image
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $errors[] = "File {$name} is not a valid image";
            continue;
        }

        // Full-size image path
        $uploadPath = "{$imageDir}/{$name}";

        // Move the uploaded file
        if (move_uploaded_file($tmp_name, $uploadPath)) {
            // Create resized version
            $resizedPath = "{$resizedDir}/{$name}";

            // Simple resize using GD library
            list($width, $height) = getimagesize($uploadPath);
            $maxDimension = 800; // Maximum width/height

            if ($width > $maxDimension || $height > $maxDimension) {
                $ratio = $width / $height;

                if ($ratio > 1) {
                    $newWidth = $maxDimension;
                    $newHeight = $maxDimension / $ratio;
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = $maxDimension * $ratio;
                }

                $src = imagecreatefromstring(file_get_contents($uploadPath));
                $dst = imagecreatetruecolor($newWidth, $newHeight);

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                switch ($ext) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($dst, $resizedPath, 90);
                        break;
                    case 'png':
                        imagepng($dst, $resizedPath, 8);
                        break;
                    case 'gif':
                        imagegif($dst, $resizedPath);
                        break;
                }

                imagedestroy($src);
                imagedestroy($dst);
            } else {
                // Just copy the file if it's already small enough
                copy($uploadPath, $resizedPath);
            }

            $uploadedFiles[] = $name;
        } else {
            $errors[] = "Failed to upload file {$name}";
        }
    } else {
        $errors[] = "Error code: " . $_FILES['images']['error'][$key];
    }
}

echo json_encode([
    'success' => count($uploadedFiles) > 0,
    'files' => $uploadedFiles,
    'errors' => $errors
]);

?>