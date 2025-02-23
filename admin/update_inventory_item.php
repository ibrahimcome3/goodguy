<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem();
$c = new Category();
$i = new InventoryItem();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inventoryItemId = $_POST['id'];
    $productId = $_POST['product_id'];
    $description = $_POST['description'];
    $sku = $_POST['sku'];
    //$sku = '{"v":"dd"}';
    $cost = $_POST['cost'];
    $tax = $_POST['tax'];
    $quantityOnHand = $_POST['quantityOnHand'];
    $barcode = $_POST['barcode'];

    //Sanitize Inputs (Very Important!)
    $description = $mysqli->real_escape_string($description);
    // $sku = $mysqli->real_escape_string($sku);
    $barcode = $mysqli->real_escape_string($barcode);

    var_dump($description);
    var_dump($sku);
    var_dump($barcode);
    var_dump($cost);
    var_dump($tax);
    var_dump($quantityOnHand);
    var_dump($inventoryItemId);
    var_dump($productId);

    var_dump($_POST);

    //try {
    // Update inventory item details
    $updateSql = "UPDATE inventoryitem SET `description`=?, sku=?, cost=?, tax=?, quantityOnHand=?, barcode=? WHERE inventoryitemID=?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("ssdddii", $description, $sku, $cost, $tax, $quantityOnHand, $barcode, $inventoryItemId);


    if (!$updateStmt->execute()) {
        throw new Exception("Error updating inventory item: " . $updateStmt->error);
    }


    // Handle image deletion
    if (isset($_POST['images_to_delete'])) {
        foreach ($_POST['images_to_delete'] as $imagePath) {
            $fullImagePath = "../products/product-{$productId}/inventory-{$productId}-{$inventoryItemId}/resized/{$imagePath}";
            if (file_exists($fullImagePath)) {
                if (!unlink($fullImagePath)) {
                    throw new Exception("Error deleting image: $fullImagePath");
                } else {
                    $deleteImageSql = "DELETE FROM inventory_item_image WHERE inventory_item_id = ? AND image_path = ?";
                    $deleteImageStmt = $mysqli->prepare($deleteImageSql);
                    $deleteImageStmt->bind_param("is", $inventoryItemId, $imagePath);
                    $deleteImageStmt->execute();
                }
            }
        }
    }
    // } catch (Exception $e) {
    //     // Handle the exception (log the error, display a user-friendly message, etc.)
    //     error_log("Error updating inventory item: " . $e->getMessage()); // Log the error
    //     echo "An error occurred while updating the inventory item. Please try again later."; // User-friendly message
    // }


    // if (isset($_POST['images_to_delete'])) {
    //     foreach ($_POST['images_to_delete'] as $imagePath) {
    //         $fullImagePath = "../products/product-" . $_POST['product_id'] . "/inventory-" . $_POST['product_id'] . "-" . $_POST['id'] . "/resized/" . $imagePath;

    //         if (file_exists($fullImagePath)) {
    //             if (!unlink($fullImagePath)) {
    //                 // Handle the error - image could not be deleted
    //                 echo "Error deleting image: $fullImagePath";
    //             } else {
    //                 //Remove from database if successful
    //                 $deleteImageSql = "DELETE FROM inventory_item_image WHERE image_path = ?";
    //                 $deleteImageStmt = $mysqli->prepare($deleteImageSql);
    //                 $deleteImageStmt->bind_param("s", $imagePath);
    //                 $deleteImageStmt->execute();
    //             }
    //         }
    //     }
    // }
    // handles image upload
    var_dump($_FILES);
    if (isset($_FILES['image'])) {

        $p->imageprocessorforproductInInventory($productId, $inventoryItemId, $_FILES);
    }
}
// ... (Redirect or other processing after update) ...

?>