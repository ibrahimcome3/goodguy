<?php
require_once "../../includes.php";
session_start();

// Check if user is logged in
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['product_id']) || !isset($data['filename'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$productId = (int) $data['product_id'];
$filename = basename($data['filename']); // Sanitize filename

// Check if the image is the primary image
$stmt = $pdo->prepare("SELECT primary_image FROM productitem WHERE productID = :product_id");
$stmt->execute([':product_id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product && $product['primary_image'] == $filename) {
    // If deleting the primary image, set primary_image to NULL
    $updateStmt = $pdo->prepare("UPDATE productitem SET primary_image = NULL WHERE productID = :product_id");
    $updateStmt->execute([':product_id' => $productId]);
}

// Define file paths
$imageDir = "../../products/product-{$productId}/product-{$productId}-image";
$resizedDir = "{$imageDir}/resized";

$originalFile = "{$imageDir}/{$filename}";
$resizedFile = "{$resizedDir}/{$filename}";

$success = true;
$errors = [];

// Delete resized image
if (file_exists($resizedFile)) {
    if (!unlink($resizedFile)) {
        $success = false;
        $errors[] = "Failed to delete resized image";
    }
}

// Delete original image
if (file_exists($originalFile)) {
    if (!unlink($originalFile)) {
        $success = false;
        $errors[] = "Failed to delete original image";
    }
}

echo json_encode([
    'success' => $success,
    'errors' => $errors
]);