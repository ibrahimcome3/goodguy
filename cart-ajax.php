<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "includes.php";
// include "conn.php"; // Remove this, $pdo from includes.php should be used

$response = ['success' => false, 'message' => '']; // Initialize response

if (isset($_POST['inventory_product_id'], $_POST['qty']) && is_numeric($_POST['inventory_product_id']) && is_numeric($_POST['qty'])) {
    $productId = (int) $_POST['inventory_product_id'];
    $quantity = (int) $_POST['qty'];
    $size = isset($_POST['size']) ? $_POST['size'] : null;
    $color = isset($_POST['color']) ? $_POST['color'] : null;

    // Ensure $pdo and $promotion are available from includes.php
    if (!isset($pdo) || !isset($promotion)) {
        $response['message'] = 'Server configuration error.';
        error_log("cart-ajax.php: PDO or Promotion object not available from includes.php");
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    try {
        $cart = new Cart($pdo, $promotion); // Instantiate Cart correctly

        if ($cart->addItem($productId, $quantity, $size, $color)) {
            $response['success'] = true;
            $response['message'] = 'Item added to cart!';
        } else {
            // addItem might return false if product not found (if validation added) or quantity invalid
            $response['message'] = 'Could not add item to cart. Invalid product or quantity.';
        }

    } catch (Exception $e) {  // Catch potential errors
        $response['message'] = 'An error occurred while processing your request.';
        error_log("Cart Error: " . $e->getMessage());
    }

} else {
    $response['message'] = 'Invalid request.';
}
header('Content-Type: application/json'); // Important: set the correct header
echo json_encode($response);  // Send JSON response back to client
exit();
?>