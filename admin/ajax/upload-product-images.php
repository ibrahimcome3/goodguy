<?php
require_once "../../includes.php";
require_once __DIR__ . '/../../class/ProductItem.php'; // Make sure path is correct
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized access.']]);
    exit;
}

// Validate product ID
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid product ID.']]);
    exit;
}

$productId = (int) $_POST['product_id'];

// Check if files were uploaded
if (empty($_FILES['images']) || $_FILES['images']['error'][0] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['No files were uploaded.']]);
    exit;
}

try {
    $productItemObj = new ProductItem($pdo);
    $result = $productItemObj->uploadProductImages($productId, $_FILES['images']);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Image upload failed for product ID {$productId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['An internal server error occurred.']]);
}
?>