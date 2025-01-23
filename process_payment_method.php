<?php
// ... database connection and other setup

if (isset($_POST['paymentMethod']) && isset($_POST['orderID'])) {
    $paymentMethod = $_POST['paymentMethod'];
    $orderId = $_POST['orderID'];

    // Update the order's payment method in the database
    $sql = "UPDATE orders SET payment_method = ? WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $paymentMethod, $orderId); // "s" for string, "i" for integer
    $stmt->execute();


    if ($stmt->affected_rows > 0) { // Check if the update was successful
        echo "Payment method updated successfully";
    } else {
        echo "Error updating payment method:" . $stmt->error;
    }


    $stmt->close();
}
?>