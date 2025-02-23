<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $colors = $_POST['color'];
    $sizes = $_POST['size'];
    $description = $_POST['description'];
    $skus = $_POST['sku']; // Get SKUs
    $tax = $_POST['tax'];
    $cost = $_POST['price'];
    $bcode = $_POST['bcode']; // Bar codes
    $quantityOnHand = $_POST['quantity'];
    $image_paths = []; // Array to store processed image paths
    $image_path = handleVariantImageUpload($product_id, $_FILES['image']);  // Process image and get path
    $sku = '{"size":"128gb"}';




    $result = insertInventoryItem($mysqli, $product_id, $description, $skus, $sku, $cost, $tax, $quantityOnHand, $bcode, $image_path);

    if (isset($result['success']) && $result['success']) {
        $p->makeSubDirectoriesForVarients($product_id, $result['id']);
        $p->imageprocessorforproductInInventory($product_id, $result['id'], $_FILES);
        header("Location: success.php?id=" . $result['id']); // Pass the inserted ID for success page
        exit();
    } else {
        // Handle errors appropriately (e.g., display error messages to user)
        echo "Error: " . $result['error'];
    }
}
// ... redirect after processing all variants

exit();


function insertInventoryItem($mysqli, $product_id, $description, $sku_code, $sku, $cost, $tax, $quantityOnHand, $bcode)
{
    $sql = "INSERT INTO inventoryitem (productItemID, `description`, sku_code, sku, cost, tax, quantityOnHand, barcode) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return ["error" => "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error];
    }

    if (!$stmt->bind_param("isssddis", $product_id, $description, $sku_code, $sku, $cost, $tax, $quantityOnHand, $bcode)) {
        $stmt->close(); // Clean up
        return ["error" => "Bind param failed: " . $stmt->error];
    }

    if ($stmt->execute()) {
        $stmt->close(); // Clean up
        return ["success" => true, "id" => $mysqli->insert_id]; // Return inserted ID
    } else {
        $stmt->close(); // Clean up
        return ["error" => "Execute failed: " . $stmt->error];
    }
}


function handleVariantImageUpload($productId, $files)
{

    $productDir = "../products/product-" . $productId . "/product-" . $productId . "-image/"; // Consistent directory structure
    if (!is_dir($productDir) && !mkdir($productDir, 0777, true)) {
        return "default_image.jpg"; // Or handle the directory creation error appropriately
    }


    if ($files["error"] == UPLOAD_ERR_OK) {
        $temp = explode(".", $files["name"]);
        $newFilename = round(microtime(true)) . '.' . end($temp); // Include index in filename
        $targetFile = $productDir . $newFilename;

        if (move_uploaded_file($files["tmp_name"], $targetFile)) {
            // ... your image processing/resizing functions ...
            return $targetFile; // Return full path
        } else {
            return "default_image.jpg"; // Or handle the move_uploaded_file error
        }
    } else {
        return "default_image.jpg"; // Handle any upload errors
    }

}