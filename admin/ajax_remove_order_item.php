<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['order_id']) || !isset($data['order_item_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$orderId = (int) $data['order_id'];
$orderItemId = (int) $data['order_item_id'];

try {
    $orderObj = new Order($pdo);
    if ($orderObj->removeItemFromOrder($orderId, $orderItemId)) {
        $newOrderDetails = $orderObj->getOrderDetails($orderId);
        echo json_encode(['success' => true, 'message' => 'Item removed successfully.', 'order' => $newOrderDetails]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to remove item.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error removing item from order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}