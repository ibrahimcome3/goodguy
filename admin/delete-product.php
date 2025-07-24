<?php
session_start();
require_once '../includes.php'; // Your database connection
require_once '../class/ProductItem.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid product ID.</h2>";
    exit;
}

$productId = (int) $_GET['id'];
$productItem = new ProductItem($pdo);

// Optional: Check user permissions here

try {
    $success = $productItem->deleteProduct($productId);
    if ($success) {
        // Redirect or show success message
        header("Location: view-product.php?deleted=1");
        exit;
    } else {
        echo "<h2>Failed to delete product.</h2>";
    }
} catch (Exception $e) {
    echo "<h2>Error deleting product: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>