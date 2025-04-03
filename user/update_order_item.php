<?php
include "../conn.php";
require_once '../class/Order.php';
require_once '../class/User.php';

$o = new Order();
$u = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'update_quantity') {
        $orderItemId = $_POST['order_item_id'];
        $quantity = $_POST['quantity'];

        // Get the order ID associated with this order item
        $orderId = getOrderIdFromOrderItemId($mysqli, $orderItemId);

        //update quantity
        $success = $o->updateOrderItemQuantity($mysqli, $orderItemId, $quantity);
        if ($success) {
            //update the order total
            updateOrderTotal($mysqli, $u, $orderId);
            echo "success";
        } else {
            echo "error";
        }

    } elseif ($action === 'delete_item') {
        $orderItemId = $_POST['order_item_id'];
        // Get the order ID associated with this order item
        $orderId = getOrderIdFromOrderItemId($mysqli, $orderItemId);
        //delete the item
        $success = $o->deleteOrderItem($mysqli, $orderItemId);
        if ($success) {
            //update the order total
            updateOrderTotal($mysqli, $u, $orderId);
            echo "success";
        } else {
            echo "error";
        }

    } elseif ($action === 'submit_form') {
        $order_id = $_POST['order_id'];
        $quantities = $_POST['quantity'];
        foreach ($quantities as $order_item_id => $quantity) {
            $o->updateOrderItemQuantity($mysqli, $order_item_id, $quantity);
        }
        //update the order total
        updateOrderTotal($mysqli, $u, $order_id);
        echo "success";
    } else {
        echo "Invalid action.";
    }
} else {
    echo "Invalid request.";
}

function calculateOrderTotals($orderItems)
{
    $totalAmount = 0;
    $totalItems = 0;
    foreach ($orderItems as $item) {
        $totalAmount += ($item['cost'] * $item['quwantitiyofitem']);
        $totalItems += $item['quwantitiyofitem'];
    }
    return ['totalAmount' => $totalAmount, 'totalItems' => $totalItems];
}

function getOrderIdFromOrderItemId($mysqli, $orderItemId)
{
    $stmt = $mysqli->prepare("SELECT orderID FROM lm_order_line WHERE order_item_id = ?");
    $stmt->bind_param("i", $orderItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['orderID'];
}

function updateOrderTotal($mysqli, $u, $orderId)
{
    $orderItems = $u->getOrderItemsFromLmOrders($mysqli, $orderId);
    $orderTotals = calculateOrderTotals($orderItems);
    $sql = "UPDATE `lm_orders` SET order_total = ? WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("di", $orderTotals['totalAmount'], $orderId);
    $stmt->execute();
}
?>