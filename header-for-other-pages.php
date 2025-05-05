<?php
include "includes.php";

// Ensure includes.php is included before this file
// require_once "includes.php"; // Assuming this file includes the necessary classes and database connection

// Function to get cart items (consider moving this to a Cart class)
function getCartItems($pdo, $promotion)
{
    if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
        return [];
    }

    $cartItems = $_SESSION['cart'];
    $inventoryItemIds = array_column($cartItems, 'inventory_product_id');
    $placeholders = implode(',', array_fill(0, count($inventoryItemIds), '?'));

    $sql = "SELECT * FROM inventoryitem WHERE InventoryItemID IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($inventoryItemIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cartData = [];
    foreach ($products as $product) {
        $cartData[$product['InventoryItemID']] = [
            'product' => $product,
            'quantity' => 0,
            'cost' => $product['cost'],
        ];
        foreach ($cartItems as $item) {
            if ($item['inventory_product_id'] == $product['InventoryItemID']) {
                $cartData[$product['InventoryItemID']]['quantity'] += $item['quantity'];
            }
        }
        if ($promotion->check_if_item_is_in_inventory_promotion($product['InventoryItemID'])) {
            $cartData[$product['InventoryItemID']]['cost'] = $promotion->get_promoPrice_price($product['InventoryItemID']);
        }
    }

    return $cartData;
}

// Get cart items
$cartItems = getCartItems($pdo, $promotion);
$cart = new Cart($pdo, $promotion);
$cartTotal = $cart->calculateCartTotal($cartItems);
// Get wishlist count (if user is logged in)
$wishlistCount = isset($_SESSION['uid']) && isset($wishlist) ? $wishlist->no_of_wish_list_item : 0;
?>

<header class="header">
    <div class="header-top">
        <div class="container">
            <div class="header-left" style="margin: 20px;">
                <!-- Left content (if any) -->
            </div><!-- End .header-left -->
            <div class="header-right">
                <ul class="top-menu">
                    <li>
                        <a href="#">Links</a>
                        <ul>
                            <li><a href="tel:#"><i class="icon-phone"></i>Call: +2348051067944</a></li>
                            <?php if (isset($_SESSION["uid"])) { ?>
                                <li>
                                    <a href="wishlist.php"><i class="icon-heart-o"></i>Wishlist
                                        <span class="wishlist-count">(<?= $wishlistCount ?>)</span>
                                    </a>
                                </li>
                            <?php } else { ?>
                                <li>
                                    <a href="wishlist.php"><i class="icon-heart-o"></i>Wishlist
                                        <span>(0)</span>
                                    </a>
                                </li>
                            <?php } ?>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="vendor.php" style="color: orange">Be a vendor</a></li>
                            <?php if (isset($_SESSION["uid"])) { ?>
                                <li><a href="logout.php"><i class="icon-user"></i>Log Out</a></li>
                                <li><a href="dashboard.php"><i class="icon-user"></i>Dashboard</a></li>
                            <?php } else { ?>
                                <li><a href="login.php"><i class="icon-user"></i>Login</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                </ul><!-- End .top-menu -->
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-top -->

    <div class="header-middle sticky-header">
        <div class="container">
            <div class="header-left">
                <button class="mobile-menu-toggler">
                    <span class="sr-only">Toggle mobile menu</span>
                    <i class="icon-bars"></i>
                </button>

                <a href="index.php" class="logo">
                    <svg width="40px" viewBox="0 -1 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink">
                        <title>emoji_happy_simple [#454]</title>
                        <desc>Created with Sketch.</desc>
                        <defs></defs>
                        <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Dribbble-Light-Preview" transform="translate(-224.000000, -6165.000000)"
                                fill="#000000">
                                <g id="icons" transform="translate(56.000000, 160.000000)">
                                    <path
                                        d="M176,6009.21053 L180,6009.21053 L180,6005 L176,6005 L176,6009.21053 Z M168,6008.15789 L172,6008.15789 L172,6006.05263 L168,6006.05263 L168,6008.15789 Z M177,6010.26316 L179,6010.26316 C179,6016.57895 169,6016.57895 169,6010.26316 L171,6010.26316 C171,6014.47368 177,6014.47368 177,6010.26316 L177,6010.26316 Z"
                                        id="emoji_happy_simple-[#454]"></path>
                                    <?php $storeName = "Goodguy"; ?>
                                    <text x="2" y="10" font-family="sans-serif" font-size="4"
                                        fill="black"><?php echo $storeName; ?></text>
                                </g>
                            </g>
                        </g>
                    </svg>
                </a>

                <nav class="main-nav">
                    <!-- Main navigation links (if any) -->
                </nav><!-- End .main-nav -->
            </div><!-- End .header-left -->
            <div class="header-center">
                <div
                    class="header-search header-search-extended header-search-visible header-search-no-radius d-none d-lg-block">
                    <a href="#" class="search-toggle" role="button"><i class="icon-search"></i></a>
                    <form action="product-search.php" method="get">
                        <div class="header-search-wrapper search-wrapper-wide">
                            <label for="q" class="sr-only">Search</label>
                            <input type="search" class="form-control" name="q" id="q" placeholder="Search product ..."
                                required="">
                            <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                        </div><!-- End .header-search-wrapper -->
                    </form>
                </div><!-- End .header-search -->
            </div>
            <div class="header-right">
                <div class="dropdown cart-dropdown">
                    <a href="cart.php" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false" data-display="static">
                        <i class="icon-shopping-cart"></i>
                        <span class="cart-count"><?= count($cartItems); ?></span>
                    </a>
                    <?php if (count($cartItems) > 0) { ?>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-cart-products">
                                <?php foreach ($cartItems as $itemId => $cartItem) {
                                    $product = $cartItem['product'];
                                    $quantity = $cartItem['quantity'];
                                    $cost = $cartItem['cost'];
                                    ?>
                                    <div class="product">
                                        <div class="product-cart-details">
                                            <h4 class="product-title">
                                                <a href="product.html"><?= $product['description'] ?></a>
                                            </h4>
                                            <span class="cart-product-info">
                                                <span class="cart-product-qty"><?= $quantity ?></span>
                                                &nbsp;x &#8358;&nbsp;<?= number_format($cost, 2) ?>
                                            </span>
                                        </div><!-- End .product-cart-details -->
                                        <figure class="product-image-container">
                                            <a href="product-detail?itemid=<?= $product['InventoryItemID'] ?>"
                                                class="product-image">
                                                <img src="<?= $invt->get_product_image($pdo, $product['InventoryItemID']); ?>"
                                                    alt="product">
                                            </a>
                                        </figure>
                                        <a href="cart.php?remove=<?= array_search($product['InventoryItemID'], array_column($_SESSION['cart'], 'inventory_product_id')) ?>"
                                            class="btn-remove" title="Remove Product"><i class="icon-close"></i></a>
                                    </div><!-- End .product -->
                                <?php } ?>
                            </div><!-- End .cart-product -->
                            <div class="dropdown-cart-total">
                                <span>Total</span>
                                <span class="cart-total-price">&#8358;&nbsp;<?= number_format($cartTotal, 2) ?></span>
                            </div><!-- End .dropdown-cart-total -->
                            <div class="dropdown-cart-action">
                                <a href="cart.php" class="btn btn-primary">View Cart</a>
                                <a href="checkout-process-validation.php"
                                    class="btn btn-outline-primary-2"><span>Checkout</span><i
                                        class="icon-long-arrow-right"></i></a>
                            </div><!-- End .dropdown-cart-total -->
                        </div><!-- End .dropdown-menu -->
                    <?php } ?>
                </div><!-- End .cart-dropdown -->
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-middle -->
</header><!-- End .header -->