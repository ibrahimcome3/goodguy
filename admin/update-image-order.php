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
$order = isset($data['order']) && is_array($data['order']) ? $data['order'] : [];

if (!$inventoryItemId || empty($order)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing inventoryItemId or order data.']);
    exit;
}

try {
    $inventory = new InventoryItem($pdo);
    $result = $inventory->updateInventoryItemImageOrder($inventoryItemId, $order);

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error in update-image-order.php: " . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}

exit;