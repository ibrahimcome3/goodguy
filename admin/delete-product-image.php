<?php

header('Content-Type: application/json');
include "../includes.php";
require_once __DIR__ . '/../class/InventoryItem.php';

session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to perform this action.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

$inventoryItemId = isset($data['inventoryItemId']) ? (int) $data['inventoryItemId'] : 0;
$imageId = isset($data['imageId']) ? (int) $data['imageId'] : 0;

if (!$inventoryItemId || !$imageId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing inventoryItemId or imageId.']);
    exit;
}

try {
    $inventory = new InventoryItem($pdo);
    $productId = $inventory->getProductIdForInventoryItem($inventoryItemId);

    if (!$productId) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Product not found for the given inventory item.']);
        exit;
    }

    $result = $inventory->deleteInventoryItemImage($inventoryItemId, $imageId);

    if (isset($result['error'])) {
        http_response_code(404); // Not Found or other error
        echo json_encode($result);
    } else {
        // Add productId to the successful response. This can be useful for
        // client-side logic, such as redirecting back to the product page.
        $result['productId'] = $productId;
        http_response_code(200); // OK
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error deleting image for inventory item {$inventoryItemId}: " . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}

exit;