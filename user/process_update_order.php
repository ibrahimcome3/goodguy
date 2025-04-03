<?php
session_start();
include "../conn.php";
require_once '../class/User.php';

$u = new User();

// Ensure user is logged in and is an admin or seller
if (!isset($_SESSION['uid']) || (!($u->isAdmin($mysqli, $_SESSION['uid']) || $u->getVendorStatus($mysqli, $_SESSION['uid']) == 'active'))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];
    // Sanitize and validate the input data
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $orderStatus = filter_input(INPUT_POST, 'order_status', FILTER_SANITIZE_STRING);
    $orderDueDate = filter_input(INPUT_POST, 'order_due_date', FILTER_SANITIZE_STRING);
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderDueDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
        exit;
    }

    if (!$orderId || !$orderStatus || !$orderDueDate) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit;
    }



    // Update the order in the database
    if ($u->updateOrderLmOrders($mysqli, $orderId, $orderStatus, $orderDueDate)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating order.']);
    }



    if ($action === 'update_order') {
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $quantities = $_POST['quantity'];
        foreach ($quantities as $orderItemId => $newQuantity) {
            $u->updateOrderItemQuantity($mysqli, $orderItemId, $newQuantity);
        }
        echo json_encode(['success' => true]);
    } else if ($action === 'delete_item') {
        $orderItemId = filter_input(INPUT_POST, 'order_item_id', FILTER_VALIDATE_INT);
        if ($u->deleteOrderItem($mysqli, $orderItemId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting item']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>