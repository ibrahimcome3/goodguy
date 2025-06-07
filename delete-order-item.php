<?php
// It's good practice to ensure session is started.
// If includes.php already does this, this check is harmless.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes.php"; // Provides $pdo, loads Order class

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    // Optionally set a message for the login page
    $_SESSION['login_redirect_message'] = "Please log in to manage your orders.";
    header("Location: login.php");
    exit();
}

// Ensure $orders object is instantiated (assuming $pdo is available from includes.php)
if (!isset($pdo)) {
    // This indicates a problem with includes.php
    error_log("PDO object not available in delete-order-item.php");
    // Set a generic error message for the user
    if (isset($_GET['oid']) && is_numeric($_GET['oid'])) {
        $_SESSION['error_message'] = "A system error occurred. Could not process your request.";
        header("Location: order_detail.php?order_id=" . htmlspecialchars($_GET['oid']));
    } else {
        $_SESSION['error_message'] = "A system error occurred.";
        header("Location: my_orders.php");
    }
    exit();
}

// Instantiate Order class, similar to my_orders.php
if (!isset($orders) || !($orders instanceof Order)) {
    $orders = new Order($pdo);
}

// Validate required GET parameters
if (!isset($_GET['oid']) || !isset($_GET['oitem'])) {
    $_SESSION['error_message'] = "Invalid request: Missing order or item ID.";
    // Redirect to a sensible default if critical info is missing
    header("Location: my_orders.php");
    exit();
}

// Sanitize/validate IDs
$orderId = filter_var($_GET['oid'], FILTER_VALIDATE_INT);
$orderItemId = filter_var($_GET['oitem'], FILTER_VALIDATE_INT);

if ($orderId === false || $orderItemId === false || $orderId <= 0 || $orderItemId <= 0) {
    $_SESSION['error_message'] = "Invalid order or item ID format.";
    $redirectOid = isset($_GET['oid']) ? htmlspecialchars($_GET['oid']) : null;
    if ($redirectOid && is_numeric($_GET['oid']) && $_GET['oid'] > 0) {
        header("Location: order_detail.php?order_id=" . $redirectOid);
    } else {
        header("Location: my_orders.php");
    }
    exit();
}

// Before attempting to remove, check the order status
// Assumes Order class has a method like get_order_by_id($orderId)
$orderDetails = $orders->get_order_by_id($orderId); // You might need to create/adjust this method

$nonDeletableStatuses = ['completed', 'concluded', 'delivered', 'cancelled'];

if ($orderDetails && isset($orderDetails['order_status']) && in_array(strtolower($orderDetails['order_status']), $nonDeletableStatuses)) {
    $_SESSION['error_message'] = "Cannot remove items from an order with status: " . htmlspecialchars(ucfirst($orderDetails['order_status'])) . ".";
} else if (!$orderDetails) {
    $_SESSION['error_message'] = "Order not found. Cannot remove item.";
} else {
    // The method remove_order_item_from_an_order should also ideally perform an ownership check
    // (i.e., ensure $orderId belongs to the logged-in user $_SESSION['uid']).
    // This ownership check should be INSIDE the remove_order_item_from_an_order method.
    if ($orders->remove_order_item_from_an_order($orderId, $orderItemId, $_SESSION['uid'])) { // Pass UID for ownership check
        $_SESSION['success_message'] = "Order item removed successfully.";
    } else {
        // The remove_order_item_from_an_order method should return false if ownership check fails
        // or if the item doesn't exist or another DB error occurs.
        // More specific error messages could be set within the Order class method if needed.
        $_SESSION['error_message'] = "Failed to remove order item. It might not exist, or you may not have permission, or the order status prevents it.";
    }
}

header("Location: order_detail.php?order_id=" . $orderId);
exit();
?>