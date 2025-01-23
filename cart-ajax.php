<?php

include "includes.php";
include "conn.php";

$response = ['success' => false, 'message' => '']; // Initialize response

if (isset($_POST['inventory_product_id'], $_POST['qty']) && is_numeric($_POST['inventory_product_id']) && is_numeric($_POST['qty'])) {
    // ... (Existing code to get product details)
    $quantity = (int) $_POST['qty'];
    $arr = array();
    foreach ($_POST as $key => $val) {
        if ($key === 'color')
            $arr[$key] = $val;
        if ($key === 'size')
            $arr[$key] = $val;
    }
    $cart = new Cart();
    $arryofproperties = array();

    $product_id = (int) $_POST['inventory_product_id'];
    $product_with_key = array();
    $product_with_key['product'] = $product_id;
    $product_array = array();
    $product_array = array($product_with_key, $arr);
    $JSON_product_order = json_encode($product_array);

    $stmt = $pdo->prepare('SELECT * FROM inventoryitem WHERE InventoryItemID = ?');
    $stmt->execute([$_POST['inventory_product_id']]);
    // Fetch the product from the database and return the result as an Array
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the product exists (array is not empty)
    //echo $JSON_product_order;

    try {
        if ($product && $quantity > 0) {
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                if (array_key_exists($product_id, $_SESSION['cart'])) {
                    // Product exists in cart so just update the quanity
                    $_SESSION['cart'][$product_id] += $quantity;
                    $_SESSION['detail-item'][$product_id] = $JSON_product_order;
                } else {
                    // Product is not in cart so add it
                    $_SESSION['cart'][$product_id] = $quantity;
                    $_SESSION['detail-item'][$product_id] = $JSON_product_order;
                }
            } else {
                // There are no products in cart, this will add the first product to cart
                $_SESSION['cart'] = array($product_id => $quantity);
                $_SESSION['detail-item'][$product_id] = $JSON_product_order;
            }

            $response['success'] = true;
            $response['message'] = 'Item added to cart!';
            //var_dump($_SESSION['cart']);
        } else {
            $response['message'] = 'Invalid product or quantity.';
        }

    } catch (Exception $e) {  // Catch potential errors
        $response['message'] = 'An error occurred: ' . $e->getMessage(); //More specific error message (for debugging)
        // Log the error for debugging.  Don't show the full error to the user in a production environment.
        error_log("Cart Error: " . $e->getMessage());
    }

} else {
    $response['message'] = 'Invalid request.';
}

header('Content-Type: application/json'); // Important: set the correct header
echo json_encode($response);  // Send JSON response back to client
exit();



?>