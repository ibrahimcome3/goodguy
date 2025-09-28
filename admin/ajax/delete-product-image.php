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

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['product_id']) || !is_numeric($data['product_id']) || !isset($data['filename'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid request. Product ID and filename are required.']]);
    exit;
}

$productId = (int) $data['product_id'];
$filename = basename($data['filename']); // Sanitize filename

try {
    $productItemObj = new ProductItem($pdo);
    $result = $productItemObj->deleteProductImage($productId, $filename);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Image deletion failed for product ID {$productId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['An internal server error occurred.']]);
}