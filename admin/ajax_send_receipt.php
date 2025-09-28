<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';

header('Content-Type: application/json');

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// --- Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$orderId = (int) $_POST['order_id'];
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID.']);
    exit();
}

try {
    $orderObj = new Order($pdo);
    $order = $orderObj->getOrderDetails($orderId);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit();
    }

    // Send the payment receipt email
    $success = $orderObj->sendPaymentReceiptEmail($orderId);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        // The mailer might have logged a more specific error.
        // This is a generic message for the user.
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Check server logs.']);
    }
} catch (Exception $e) {
    // Log the actual error for debugging
    error_log('Error in ajax_send_receipt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}
