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
    //Removed $category from here
    // $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
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
        //Removed category
        $sql = "UPDATE productitem SET product_name = ?,  brand = ?, product_information = ? WHERE productID = ?";
        $stmt = $mysqli->prepare($sql);
        //Removed category
        $stmt->bind_param("sssi", $product_name, $brand, $product_information, $productId);
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

        // Handle categories (modified to use categories[] array)
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            // Delete existing category associations for this product
            $deleteSql = "DELETE FROM product_categories WHERE product_id = ?";
            $deleteStmt = $mysqli->prepare($deleteSql);
            $deleteStmt->bind_param("i", $productId);
            $deleteStmt->execute();

            // Insert the new category associations
            foreach ($_POST['categories'] as $categoryId) {
                //Ensure the categoryId is an integer to avoid SQL injection.
                $categoryId = intval($categoryId);
                if ($categoryId) {
                    $insertSql = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                    $insertStmt = $mysqli->prepare($insertSql);
                    $insertStmt->bind_param("ii", $productId, $categoryId);
                    $insertStmt->execute();
                }
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