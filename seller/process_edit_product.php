<?php
session_start();
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/ProductItem.php';

$p = new ProductItem();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = $_POST['product_id'];

    // Validate and sanitize POST data (add more robust validation as needed)
    $product_name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
    $product_information = filter_input(INPUT_POST, 'produt_info', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $cost = filter_input(INPUT_POST, 'cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $sku = filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_STRING);
    $barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);

    //Handle Image Uploads
    $imagesToDelete = isset($_POST['images_to_delete']) ? $_POST['images_to_delete'] : [];
    $newImages = [];
    if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        $newImages = $p->moveProductImage($productId, $_FILES['file']);
        if (isset($newImages['error'])) {
            $error = $newImages['error'];
        }
    }

    //Check if there is at least one image
    $sql = "SELECT COUNT(*) AS imageCount FROM product_images WHERE product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $imageCount = $row['imageCount'];

    if ($imageCount - count($imagesToDelete) + count($newImages) < 1) {
        echo "<p style='color:red;'>You need at least one image for a product.</p>";
        exit;
    }

    $mysqli->begin_transaction();
    try {
        // Update product information
        $sql = "UPDATE productitem SET product_name = ?,  brand = ?, product_information = ?, category = ? WHERE productID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssii", $product_name, $brand, $product_information, $category, $productId);
        $stmt->execute();
        if ($stmt->error) {
            throw new Exception("Error updating product information: " . $stmt->error);
        }

        // Delete images if specified
        if (!empty($imagesToDelete)) {
            foreach ($imagesToDelete as $imageId) {
                $p->deleteImage($mysqli, $imageId, $productId);
            }
        }

        $mysqli->commit();
        header("Location: seller-dashboard.php");
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "<p style='color: red;'>Error updating product: " . $e->getMessage() . "</p>";
    }
}
?>