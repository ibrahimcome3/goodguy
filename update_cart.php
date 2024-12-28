<?php
session_start();
// Update product quantities in cart if the user clicks the "Update" button on the shopping cart page
echo(1);
if ( isset($_SESSION['cart'])) {
  if(isset($_POST)){
//var_dump($_POST);
// Loop through the post data so we can update the quantities for every product in car
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'quantity') !== false && is_numeric($v)) {
            $id = str_replace('quantity-', '', $k);
            $quantity = (int)$v;
            // Always do checks and validation
            if (is_numeric($id) && isset($_SESSION['cart'][$id]) && $quantity > 0) {
                // Update new quantity
                $_SESSION['cart'][$id] = $quantity;
            }
        }
        }
    }
    // Prevent form resubmission...
    header('location: cart_.php');
    exit;
}


?>