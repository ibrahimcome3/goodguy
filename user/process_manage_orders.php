<?php
include "../conn.php";
require_once '../class/User.php';
require_once '../class/Order.php';

$o = new Order();

$u = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'update_order') {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['new_status'];
        $newDueDate = $_POST['new_due_date'];

        $success = $u->updateOrderLmOrders($mysqli, $orderId, $newStatus, $newDueDate);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
        }
    } elseif ($action === 'update_location') {
        $orderId = $_POST['order_id'];
        $newLocation = $_POST['new_location'];

        // Update the order location
        $success = $o->updateOrderLocation($mysqli, $orderId, $newLocation);

        // Add a tracking entry
        if ($success) {
            $o->addOrderTracking($mysqli, $orderId, $newLocation);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order location.']);
        }
    } elseif ($action === 'accept_order') {
        $orderId = $_POST['order_id'];
        $success = $o->acceptOrder($mysqli, $orderId);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to accept order.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>