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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$inventoryItemId = isset($_POST['inventory_item_id']) ? (int) $_POST['inventory_item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

if (!$orderId || !$inventoryItemId || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

try {
    $orderObj = new Order($pdo);
    $result = $orderObj->addItemToOrder($orderId, $inventoryItemId, $quantity);

    if ($result === true) {
        $newOrderDetails = $orderObj->getOrderDetails($orderId);
        $newOrderItems = $orderObj->getOrderItems($orderId);
        echo json_encode(['success' => true, 'message' => 'Item added successfully.', 'order' => $newOrderDetails, 'items' => $newOrderItems]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result]); // $result might contain an error message
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error adding item to order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}