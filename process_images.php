<?php
// filepath: c:\wamp64\www\goodguy\scripts\process_product_images.php

require_once __DIR__ . '/includes.php'; // Adjust path as needed
require_once __DIR__ . '/class/ProductItem.php'; // Adjust path as needed

// Configuration
$products_dir = __DIR__ . '/products'; // Path to your products directory
$resized_image_width = 600; // Width of the resized image
$default_product_image = 'default.jpg'; // Relative path

// Function to resize an image
function resizeImage(string $source_image, string $destination_image, int $width): bool
{
    $image_info = getimagesize($source_image);
    if (!$image_info) {
        error_log("Could not determine image type for: " . $source_image);
        return false;
    }

    $image_type = $image_info[2];

    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_image);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_image);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_image);
            break;
        default:
            error_log("Unsupported image type for: " . $source_image);
            return false;
    }

    $source_width = imagesx($source);
    $source_height = imagesy($source);
    $height = round($width * $source_height / $source_width);

    $destination = imagecreatetruecolor($width, $height);
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $source_width, $source_height);

    // Save the resized image
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $destination_image, 80); // Adjust quality as needed
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $destination_image, 9);  // Adjust compression as needed
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $destination_image);
            break;
    }

    imagedestroy($source);
    imagedestroy($destination);

    return true;
}

// Main script execution
try {
    $pdo->beginTransaction(); // Start transaction for data consistency

    $productItem = new ProductItem($pdo);

    // 1. Loop through product directories
    $product_folders = glob($products_dir . '/product-*', GLOB_ONLYDIR);

    foreach ($product_folders as $product_folder) {
        // Extract product ID from folder name (e.g., "product-123")
        if (preg_match('/product-(\d+)/', basename($product_folder), $matches)) {
            $product_id = (int) $matches[1];
            print "Processing product ID: " . $product_id . "\n";
            // 2. Check for product-image-folder
            $image_folder = $product_folder . '/product-' . $product_id . '-image';
            if (is_dir($image_folder)) {
                // 3. Create resized image folder
                $resized_folder = $image_folder . '/resized';
                if (!is_dir($resized_folder)) {
                    if (!mkdir($resized_folder, 0777, true)) {
                        error_log("Failed to create resized image folder: " . $resized_folder);
                        continue; // Skip to the next product
                    }
                }

                // 4. Find an image in the product-image-folder
                $images = glob($image_folder . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                if (!empty($images)) {
                    $source_image = $images[0]; // Take the first image found
                    $image_filename = pathinfo($source_image, PATHINFO_FILENAME);
                    $image_extension = strtolower(pathinfo($source_image, PATHINFO_EXTENSION));
                    $resized_image_filename = $image_filename . '_' . $resized_image_width . '.' . $image_extension;
                    $resized_image_path = $resized_folder . '/' . $resized_image_filename;
                    $relative_resized_image_path = 'products/product-' . $product_id . '-image/resized/' . $resized_image_filename; // Relative path for DB

                    // Option A: store just the file name ( "image itself" = name )
                    $image_name_only = $resized_image_filename;

                    // Option B (commented): store binary (not recommended unless required)
                    // $image_blob = file_get_contents($resized_image_path);

                    // 5. Resize the image
                    if (resizeImage($source_image, $resized_image_path, $resized_image_width)) {
                        // 6. Register the image (using Option A)
                        $sql = "INSERT INTO product_images (product_id, image, image_path, created_at)
                                VALUES (:product_id, :image, :image_path, NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':product_id' => $product_id,
                            ':image' => $image_name_only,            // line 107 now just the file name
                            ':image_path' => $relative_resized_image_path
                        ]);

                        // (If using Option B instead of A)
                        /*
                        $sql = "INSERT INTO product_images (product_id, image, image_path, created_at)
                                VALUES (:product_id, :image, :image_path, NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt->bindValue(':image', $image_blob, PDO::PARAM_LOB);
                        $stmt->bindValue(':image_path', $relative_resized_image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        */

                        // 7. Update product primary image (path)
                        $sql = "UPDATE productitem SET primary_image = :primary_image WHERE productID = :product_id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':product_id' => $product_id,
                            ':primary_image' => $relative_resized_image_path
                        ]);

                        echo "Processed product ID: " . $product_id . ", image: " . $relative_resized_image_path . "\n";
                    } else {
                        error_log("Failed to resize image: " . $source_image);
                    }
                } else {
                    // Set default image if no image found
                    $sql = "UPDATE productitem SET primary_image = :primary_image WHERE productID = :product_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':product_id' => $product_id,
                        ':primary_image' => $default_product_image
                    ]);
                    echo "No image found for product ID: " . $product_id . ", setting default image.\n";
                }
            }
        }
    }

    $pdo->commit(); // Commit the transaction
    echo "Script completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack(); // Rollback the transaction on error
    error_log("Transaction failed: " . $e->getMessage());
    echo "An error occurred. Check the error log for details.\n";
} catch (Exception $e) {
    error_log("Script error: " . $e->getMessage());
    echo "An error occurred. Check the error log for details.\n";
}
?>