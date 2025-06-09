<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Include necessary files and configurations
// This should define $pdo, and instantiate $cart = new Cart($pdo, $promotion);
require_once __DIR__ . '/includes.php';

$response = ['success' => false, 'message' => 'An error occurred.', 'cartCount' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['inventory_product_id'], $_POST['qty']) &&
        is_numeric($_POST['inventory_product_id']) &&
        is_numeric($_POST['qty'])
    ) {

        $productId = (int) $_POST['inventory_product_id'];
        $quantity = (int) $_POST['qty'];
        // Handle optional size and color, defaulting to null if not provided or empty
        $size = isset($_POST['size']) && !empty($_POST['size']) ? trim($_POST['size']) : null;
        $color = isset($_POST['color']) && !empty($_POST['color']) ? trim($_POST['color']) : null;

        // Validate that the Cart object is available from includes.php
        if (!isset($cart) || !($cart instanceof Cart)) {
            $response['message'] = 'Cart system is not initialized.';
            error_log("cart_ajax.php: Cart object not available or not an instance of Cart from includes.php");
        } elseif ($quantity <= 0) {
            $response['message'] = 'Quantity must be greater than zero.';
        } else {
            try {
                if ($cart->addItem($productId, $quantity, $size, $color)) {
                    $response['success'] = true;
                    $response['message'] = 'Item successfully added to cart!';
                } else {
                    // addItem might return false if product validation (if implemented in addItem) fails
                    // or for other reasons like invalid quantity (though checked above).
                    $response['message'] = 'Could not add item to cart. Please check product availability or quantity.';
                }
            } catch (Exception $e) {
                $response['message'] = 'An unexpected error occurred while adding the item.';
                error_log("Error in cart_ajax.php during cart->addItem: " . $e->getMessage());
            }
        }
    } else {
        $response['message'] = 'Invalid product ID or quantity provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Always attempt to get the current cart count if the cart object is available
if (isset($cart) && ($cart instanceof Cart)) {
    try {
        $response['cartCount'] = $cart->getCartItemCount();
    } catch (Exception $e) {
        error_log("Error in cart_ajax.php during cart->getCartItemCount: " . $e->getMessage());
        // Keep the default cartCount of 0 or a previously set value if an error occurs here
    }
} else {
    // If cart object wasn't even available, try to count session items directly as a fallback
    // This assumes the session structure used by Cart::addItem
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $response['cartCount'] = count($_SESSION['cart']);
    } else {
        $response['cartCount'] = 0;
    }
}


header('Content-Type: application/json');
echo json_encode($response);
exit;
?>