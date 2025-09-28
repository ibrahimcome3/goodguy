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

if (!$data || !isset($data['order_item_id']) || !isset($data['quantity']) || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$orderItemId = (int) $data['order_item_id'];
$quantity = (int) $data['quantity'];
$orderId = (int) $data['order_id'];

try {
    $orderObj = new Order($pdo);

    if ($orderObj->updateOrderItemQuantityPDO($orderItemId, $quantity)) {
        $newOrderDetails = $orderObj->getOrderDetails($orderId);
        echo json_encode(['success' => true, 'message' => 'Quantity updated.', 'order' => $newOrderDetails]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update quantity.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error updating order item quantity: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}