<?php
// File: header_main.php

// Ensure includes.php (with $pdo, class definitions etc.) is included *before* this file in your main page scripts.
// Ensure session_start() is called *before* this file in your main page scripts.

// --- Data Fetching ---
// Instantiate necessary objects (assuming they are defined in includes.php)
try {
    $cart = new Cart($pdo, $promotion);
    $wishlist = new Wishlist($pdo, 42); // Assuming Wishlist class exists and needs PDO
    $invt = new InventoryItem($pdo); // Assuming InventoryItem class exists and needs PDO
    $category = new Category(); // Assuming Category class exists and needs PDO
} catch (Exception $e) {
    error_log("Error instantiating classes in header_main.php: " . $e->getMessage());
    // Handle error gracefully - maybe set defaults?
    $cartDetails = [];
    $cartCount = 0;
    $cartTotal = 0.0;
    $wishlistCount = 0;
    $categories = [];
    // Optionally: die("Header setup failed.");
}


// Get Cart Details using the optimized method (handle potential errors from constructor)
if (isset($cart)) {
    $cartDetails = $cart->getCartDetails();
    $cartCount = count($cartDetails);
    $cartTotal = $cart->calculateCartTotal($cartDetails);
} else {
    $cartDetails = [];
    $cartCount = 0;
    $cartTotal = 0.0;
}


// Get Wishlist Count (dynamic) - handle potential errors from constructor
$wishlistCount = (isset($_SESSION['uid']) && isset($wishlist)) ? $wishlist->no_of_wish_list_item : 0; // Assuming method exists

// Get Categories (Consider Caching Later if needed) - handle potential errors from constructor
if (isset($category)) {
    try {
        $categoryStmt = $category->get_parent_category(); // Assuming this method returns a PDOStatement
        $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        $categories = []; // Prevent errors if fetch fails
    }
} else {
    $categories = [];
}


$storeName = "Goodguy"; // Define store name

?>
<header class="header header-10 header-intro-clearance">
    <div class="header-top">
        <div class="container">
            <div class="header-left">
                <a href="tel:+2348051067944"><i class="icon-phone"></i>Call: +2348051067944</a>
            </div><!-- End .header-left -->

            <div class="header-right">
                <ul class="top-menu">
                    <li>
                        <a href="#">Links</a>
                        <ul>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="vendor.php" style="color: orange;">Be a vendor</a></li>
                            <?php if (isset($_SESSION["uid"])): ?>
                                <li>
                                    <a href="wishlist.php"><i class="icon-heart-o"></i>Wishlist
                                        <span class="wishlist-count">(<?= (int) $wishlistCount ?>)</span>
                                    </a>
                                </li>
                                <li><a href="dashboard.php"><i class="icon-user"></i>Dashboard</a></li>
                                <li><a href="logout.php"><i class="icon-log-out"></i>Log Out</a></li>
                            <?php else: ?>
                                <li>
                                    <a href="wishlist.php"><i class="icon-heart-o"></i>Wishlist
                                        <span>(0)</span> <?php // Show 0 if not logged in ?>
                                    </a>
                                </li>
                                <li class="login"><a href="login.php"><i class="icon-user"></i>Sign in / Sign up</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul><!-- End .top-menu -->
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-top -->

    <div class="header-middle">
        <div class="container">
            <div class="header-left h-100 d-flex align-items-center justify-content-center">
                <button class="mobile-menu-toggler">
                    <span class="sr-only">Toggle mobile menu</span>
                    <i class="icon-bars"></i>
                </button>

                <a href="index.php" class="logo">
                    <div class="h-100 d-flex align-items-center justify-content-center">
                        <div style="color: red"><img src="assets/images/goodguy.svg" alt="goodguyng.com logo"
                                width="30"></div>
                        <div
                            style="margin-left: 10px; font-size: 20px; color: black; margin-top:-8px; font-weight: bold;">
                            <?= htmlspecialchars($storeName) ?>.com
                        </div>
                    </div>
                </a>
            </div><!-- End .header-left -->

            <div class="header-center">
                <div
                    class="header-search header-search-extended header-search-visible header-search-no-radius d-none d-lg-block">
                    <a href="#" class="search-toggle" role="button"><i class="icon-search"></i></a>
                    <form action="product-search.php" method="get">
                        <div class="header-search-wrapper search-wrapper-wide">
                            <label for="q" class="sr-only">Search</label>
                            <input type="search" class="form-control" name="q" id="q" placeholder="Search product ..."
                                required>
                            <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                        </div><!-- End .header-search-wrapper -->
                    </form>
                </div><!-- End .header-search -->
            </div>

            <div class="header-right">
                <div class="header-dropdown-link">
                    <a href="wishlist.php" class="wishlist-link">
                        <i class="icon-heart-o"></i>
                        <span class="wishlist-count"><?= (int) $wishlistCount ?></span>
                        <span class="wishlist-txt">Wishlist</span>
                    </a>

                    <div class="dropdown cart-dropdown">
                        <a href="cart.php" class="dropdown-toggle" role="button" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false" data-display="static">
                            <i class="icon-shopping-cart"></i>
                            <span class="cart-count"><?= (int) $cartCount ?></span>
                            <span class="cart-txt">Cart</span>
                        </a>

                        <?php if ($cartCount > 0 && !empty($cartDetails)): // Check cartDetails too ?>
                            <div class="dropdown-menu dropdown-menu-right">
                                <div class="dropdown-cart-products">
                                    <?php foreach ($cartDetails as $itemId => $cartItem):
                                        // Add checks for expected array structure
                                        if (!isset($cartItem['product']) || !isset($cartItem['quantity']) || !isset($cartItem['cost']))
                                            continue;

                                        $product = $cartItem['product'];
                                        $quantity = $cartItem['quantity'];
                                        $cost = $cartItem['cost']; // Unit cost (potentially promotional)
                                
                                        // --- Optimized Image Handling ---
                                        // Assumes 'image_path' is fetched by getCartDetails()
                                        // *** Adjust 'image_path' key if different in your $product array ***
                                        $imageSrc = (!empty($product['image_path']) && file_exists($product['image_path'])) // Optional: Check if file exists
                                            ? htmlspecialchars($product['image_path'])
                                            : 'assets/images/products/default-product.jpg'; // Default image
                                
                                        ?>
                                        <div class="product">
                                            <div class="product-cart-details">
                                                <h4 class="product-title">
                                                    <a href="product-detail.php?itemid=<?= (int) $itemId ?>">
                                                        <?= htmlspecialchars($product['description'] ?? 'Product') ?>
                                                    </a>
                                                </h4>
                                                <span class="cart-product-info">
                                                    <span class="cart-product-qty"><?= (int) $quantity ?></span>
                                                    &nbsp;x &#8358;&nbsp;<?= number_format($cost, 2) ?>
                                                </span>
                                            </div><!-- End .product-cart-details -->
                                            <figure class="product-image-container">
                                                <a href="product-detail.php?itemid=<?= (int) $itemId ?>" class="product-image">
                                                    <img src="<?= $imageSrc ?>"
                                                        alt="<?= htmlspecialchars($product['description'] ?? 'Product Image') ?>">
                                                </a>
                                            </figure>
                                            <?php // --- OPTIMIZED Removal Link --- ?>
                                            <a href="cart.php?remove_item=<?= (int) $itemId ?>" class="btn-remove"
                                                title="Remove Product"><i class="icon-close"></i></a>
                                        </div><!-- End .product -->
                                    <?php endforeach; ?>
                                </div><!-- End .cart-product -->
                                <div class="dropdown-cart-total">
                                    <span>Total</span>
                                    <span class="cart-total-price">&#8358;&nbsp;<?= number_format($cartTotal, 2) ?></span>
                                </div><!-- End .dropdown-cart-total -->
                                <div class="dropdown-cart-action">
                                    <a href="cart.php" class="btn btn-primary">View Cart</a>
                                    <a href="checkout.php" class="btn btn-outline-primary-2"><span>Checkout</span><i
                                            class="icon-long-arrow-right"></i></a>
                                </div><!-- End .dropdown-cart-total -->
                            </div><!-- End .dropdown-menu -->
                        <?php else: ?>
                            <div class="dropdown-menu dropdown-menu-right">
                                <p class="text-center p-3">Your cart is empty.</p>
                            </div>
                        <?php endif; ?>
                    </div><!-- End .cart-dropdown -->
                </div>
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-middle -->

    <div class="sticky-wrapper">
        <div class="header-bottom sticky-header">
            <div class="container">
                <div class="header-left">
                    <div class="dropdown category-dropdown">
                        <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false" data-display="static" title="Browse Categories">
                            Browse Categories <i class="icon-angle-down"></i>
                        </a>

                        <div class="dropdown-menu">
                            <nav class="side-nav">
                                <ul class="menu-vertical sf-arrows">
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $row): ?>
                                            <li class="megamenu-container">
                                                <?php // Ensure 'cat' and 'catid' are the correct keys from your DB ?>
                                                <a class="sf-with-ul"
                                                    href="category.php?catid=<?= htmlspecialchars($row['catid'] ?? '') ?>">
                                                    <?= htmlspecialchars(ucfirst(strtolower($row['cat'] ?? 'Category'))) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><a href="#">No Categories Found</a></li>
                                    <?php endif; ?>
                                </ul><!-- End .menu-vertical -->
                            </nav><!-- End .side-nav -->
                        </div><!-- End .dropdown-menu -->
                    </div><!-- End .category-dropdown -->
                </div><!-- End .header-left -->

                <nav class="main-nav">
                    <ul class="menu sf-arrows">
                        <li class="active"><a href="index.php">Home</a></li>
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <?php // Add other main navigation links here ?>
                    </ul><!-- End .menu -->
                </nav><!-- End .main-nav -->

                <div class="header-right">
                    <i class="la la-lightbulb-o"></i>
                    <p><span>Sale on selected items Up to 30% Off</span></p> <?php // Consider making this dynamic ?>
                </div>
            </div><!-- End .container -->
        </div><!-- End .header-bottom -->
    </div><!-- End .sticky-wrapper -->
</header><!-- End .header -->