<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem($pdo);
$c = new Category($pdo);
$i = new InventoryItem($pdo);

// Get the referring URL (the page that sent the request)
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'view_inventory.php'; //Default to view_inventory if no referer


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inventoryItemId = $_POST['id'];
    $productId = $_POST['product_id'];
    $description = $_POST['description'];
    $cost = $_POST['cost'];
    $tax = $_POST['tax'];
    $quantityOnHand = $_POST['quantityOnHand'];
    $barcode = $_POST['barcode'];
    $discount = $_POST['discount'];


    //Sanitize Inputs (Very Important!)
    $description = $mysqli->real_escape_string($description);
    $barcode = $mysqli->real_escape_string($barcode);

    try {
        // Update inventory item details
        $updateSql = "UPDATE inventoryitem SET `description`=?, cost=?, tax=?, quantityOnHand=?, barcode=?, discount=? WHERE inventoryitemID=?";
        $updateStmt = $mysqli->prepare($updateSql);
        $updateStmt->bind_param("sddsddi", $description, $cost, $tax, $quantityOnHand, $barcode, $discount, $inventoryItemId);

        if (!$updateStmt->execute()) {
            throw new Exception("Error updating inventory item: " . $updateStmt->error);
        }

        // Handle image deletion
        if (isset($_POST['images_to_delete'])) {
            //$productDir = "../products/product-{$productId}/inventory-{$productId}-{$inventoryItemId}/resized/";
            foreach ($_POST['images_to_delete'] as $imagePath) {
                var_dump($imagePath);
                $pathParts = explode('/', $imagePath);
                $imageName = end($pathParts); // Get only the filename
                //$fullImagePath = $productDir . $imageName;
                $fullImagePath = "../{$imagePath}";

                if (file_exists($fullImagePath)) {
                    if (unlink($fullImagePath)) {
                        // Correct DELETE statement: use $imageName (filename only)
                        $deleteImageSql = "DELETE FROM inventory_item_image WHERE inventory_item_id = ? AND image_path = ?";
                        $stmt = $mysqli->prepare($deleteImageSql);
                        $stmt->bind_param("is", $inventoryItemId, $fullImagePath); // Use $imageName here
                        if ($stmt->execute()) {
                            //Success
                        } else {
                            throw new Exception("Error deleting image from database: " . $stmt->error);
                        }
                    } else {
                        throw new Exception("Error deleting image file: $fullImagePath");
                    }
                } else {
                    error_log("Image file not found: $fullImagePath");
                }
            }
        }
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'][0] !== UPLOAD_ERR_NO_FILE) { //check if any image uploaded.
            $imageUploadResult = $p->imageprocessorforproductInInventory($productId, $inventoryItemId, $_FILES);
            if (isset($imageUploadResult['error'])) {
                throw new Exception("Error uploading images: " . $imageUploadResult['error']);
            }
        }

        // Redirect back to the referring page
        header("Location: " . $referer);
        exit; // Important to stop further execution after the redirect

    } catch (Exception $e) {
        error_log("Error updating inventory item: " . $e->getMessage());
        // Display an error message to the user (consider a better way to handle errors)
        echo "An error occurred while updating the inventory item. Please try again later.";
    }
}
?>