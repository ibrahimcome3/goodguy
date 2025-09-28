<?php
include 'includes.php';
// REMOVE THIS LINE - includes.php now handles the PDO connection and $cart instantiation.
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Include necessary files and configurations
// This should define $pdo, and instantiate $cart = new Cart($pdo, $promotion);


$response = ['success' => false, 'message' => 'An error occurred.', 'cartCount' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($cart) && ($cart instanceof Cart)) {
    $action = $_POST['action'] ?? null;

    if ($action === 'add_item') {
        if (
            isset($_POST['inventory_product_id'], $_POST['qty']) &&
            is_numeric($_POST['inventory_product_id']) &&
            is_numeric($_POST['qty'])
        ) {
            $productId = (int) $_POST['inventory_product_id'];
            $quantity = (int) $_POST['qty'];
            $size = isset($_POST['size']) && !empty($_POST['size']) ? trim($_POST['size']) : null;
            $color = isset($_POST['color']) && !empty($_POST['color']) ? trim($_POST['color']) : null;

            if ($quantity <= 0) {
                $response['message'] = 'Quantity must be greater than zero.';
            } else {
                if ($cart->addItem($productId, $quantity, $size, $color)) {
                    $response['success'] = true;
                    $response['message'] = 'Item successfully added to cart!';
                } else {
                    $response['message'] = 'Could not add item to cart. Please check product availability or quantity.';
                }
            }
        } else {
            $response['message'] = 'Invalid product ID or quantity provided for adding.';
        }
    } elseif ($action === 'remove_item') {
        if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
            $productIdToRemove = (int) $_POST['item_id'];
            if ($cart->removeItemByProductId($productIdToRemove)) {
                $response['success'] = true;
                $response['message'] = 'Item successfully removed from cart!';
            } else {
                // This branch might not be hit if removeItemByProductId always returns true
                $response['message'] = 'Could not remove item from cart.';
            }
        } else {
            $response['message'] = 'Invalid item ID provided for removal.';
        }
    } elseif ($action === 'update_item') {
        if (isset($_POST['item_id'], $_POST['qty']) && is_numeric($_POST['item_id']) && is_numeric($_POST['qty'])) {
            $productIdToUpdate = (int) $_POST['item_id'];
            $newQuantity = (int) $_POST['qty'];
            if ($cart->updateItemQuantity($productIdToUpdate, $newQuantity)) { // Assumes updateItemQuantity method exists
                $response['success'] = true;
                $response['message'] = 'Cart updated successfully!';
            } else {
                $response['message'] = 'Could not update item quantity.';
            }
        } else {
            $response['message'] = 'Invalid item ID provided for removal.';
        }
    } else {
        $response['message'] = 'Invalid action specified.';
    }

    // After any action (add/remove), get updated cart details for the dropdown
    // This block is now outside the specific action blocks to run for both add and remove.
    if ($response['success']) { // Only regenerate HTML if the action was successful
        $cartDetails = $cart->getCartDetails();
        $currentCartCount = $cart->getCartItemCount();
        $currentCartTotal = $cart->calculateCartTotal($cartDetails);

        $response['cartCount'] = $currentCartCount;
        $response['cartTotalFormatted'] = "&#8358;&nbsp;" . number_format($currentCartTotal, 2);

        ob_start();
        if ($currentCartCount > 0 && !empty($cartDetails)) {
            foreach ($cartDetails as $itemId => $cartItemData) { // $itemId here is InventoryItemID
                $productData = $cartItemData['product'] ?? [];
                $qty = $cartItemData['quantity'] ?? 0;
                $cost = $cartItemData['cost'] ?? 0.0;
                $desc = htmlspecialchars($productData['description'] ?? 'Product');
                $imgP = $productData['image_path'] ?? '';

                $webRoot = $_SERVER['DOCUMENT_ROOT'];
                $cleanImgPathRel = ltrim($imgP, './');
                $fullServerPath = $webRoot . '/' . ltrim($cleanImgPathRel, '/'); // Corrected path for file_exists

                $imgSrc = (!empty($imgP) && file_exists($fullServerPath))
                    ? htmlspecialchars($cleanImgPathRel)
                    : 'assets/images/products/default-product.png';

                echo '<div class="product">';
                echo '  <div class="product-cart-details">';
                echo '    <h4 class="product-title"><a href="product-detail.php?itemid=' . (int) $itemId . '">' . $desc . '</a></h4>';
                echo '    <span class="cart-product-info">';
                echo '      <span class="cart-product-qty">' . (int) $qty . '</span>';
                echo '      &nbsp;x &#8358;&nbsp;' . number_format($cost, 2);
                echo '    </span>';
                echo '  </div>';
                echo '  <figure class="product-image-container">';
                echo '    <a href="product-detail.php?itemid=' . (int) $itemId . '" class="product-image">';
                echo '      <img src="' . $imgSrc . '" alt="' . $desc . '">';
                echo '    </a>';
                echo '  </figure>';
                // Add data-item-id for the remove button
                echo '  <a href="#" class="btn-remove btn-remove-dropdown-item" data-item-id="' . (int) $itemId . '" title="Remove Product"><i class="icon-close"></i></a>';
                echo '</div>';
            }
        }
        $response['cartItemsHtml'] = ob_get_clean();
    }

} elseif (!isset($cart) || !($cart instanceof Cart)) {
    $response['message'] = 'Cart system is not initialized.';
    error_log("cart_ajax.php: Cart object not available or not an instance of Cart from includes.php");
} else {
    $response['message'] = 'Invalid request method.';
}

// Always attempt to get the current cart count if the cart object is available
if (isset($cart) && ($cart instanceof Cart)) {
    try {
        // If an action failed, $response['success'] would be false, and we still need to send the current cart count.
        // If an action succeeded, cartCount is already set.
        if (!isset($response['cartCount'])) {
            $response['cartCount'] = $cart->getCartItemCount();
        }
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