<?php
// File: header_main.php

// This file should be included by pages that have already included "includes.php"
// "includes.php" is expected to:
// 1. Start the session
// 2. Establish the $pdo connection
// 3. Define or autoload classes (Category, Cart, Wishlist, Promotion, InventoryItem, User)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Data Fetching ---
// Default values
$cartDetails = [];
$cartCount = 0;
$cartTotal = 0.0;
$wishlistCount = 0;
$categoriesWithSubcategories = [];
$storeName = "Goodguy"; // Define store name

try {
    // Ensure $pdo is available from includes.php
    if (!isset($pdo)) {
        if (file_exists(__DIR__ . '/includes.php')) {
            require_once __DIR__ . '/includes.php';
            if (!isset($pdo)) {
                throw new Exception("PDO connection is not available even after including includes.php.");
            }
        } else {
            throw new Exception("PDO connection is not available and includes.php not found.");
        }
    }

    // Instantiate necessary objects
    // Note: $user and $invt might already be instantiated in includes.php.
    // If so, you can remove their instantiation here if they are globally available.
    // For this rewrite, I'll assume they need to be instantiated here if not already global.
    if (!isset($user) || !($user instanceof User)) {
        $user = new User($pdo);
    }
    if (!isset($invt) || !($invt instanceof InventoryItem)) {
        $invt = new InventoryItem($pdo);
    }
    if (!isset($promotion) || !($promotion instanceof Promotion)) {
        $promotion = new Promotion($pdo);
    }

    $cart = new Cart($pdo, $promotion);
    $category = new Category($pdo);

    $loggedInUserId = $_SESSION['uid'] ?? null;
    if ($loggedInUserId) {
        $wishlist = new Wishlist($pdo, $loggedInUserId);
        $wishlistCount = $wishlist->no_of_wish_list_item;
    } else {
        $wishlistCount = 0;
    }

    // Get Cart Details
    $cartDetails = $cart->getCartDetails();
    $cartCount = $cart->getCartItemCount();
    $cartTotal = $cart->calculateCartTotal($cartDetails); // Pass $cartDetails

    // Get 3-Level Categories
    $topLevelStmt = $category->getTopLevelParentCategories();
    if ($topLevelStmt) {
        $parentCategoriesData = $topLevelStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($parentCategoriesData as $parentCat) {
            $subCategoryStmt = $category->getDirectSubcategoriesByParentId($parentCat['category_id'] ?? 0);
            if ($subCategoryStmt) {
                $subCategoriesData = $subCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
                $processedSubcategories = [];
                foreach ($subCategoriesData as $subCat) {
                    $subSubCategoryStmt = $category->getDirectSubcategoriesByParentId($subCat['category_id'] ?? 0);
                    if ($subSubCategoryStmt) {
                        $subCat['subsubcategories'] = $subSubCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $subCat['subsubcategories'] = [];
                    }
                    $processedSubcategories[] = $subCat;
                }
                $parentCat['subcategories'] = $processedSubcategories;
            } else {
                $parentCat['subcategories'] = [];
            }
            $categoriesWithSubcategories[] = $parentCat;
        }
    }

} catch (Exception $e) {
    error_log("Error in header_main.php setup: " . $e->getMessage());
    // Set defaults again in case of partial failure
    $cartDetails = [];
    $cartCount = 0;
    $cartTotal = 0.0;
    $wishlistCount = 0;
    $categoriesWithSubcategories = [];
    // Optionally: echo "<p style='text-align:center; color:red;'>Header data could not be loaded. Please try again later.</p>";
}
?>
<style>
    /* Styles from header-for-other-pages.php for cart dropdown text */
    .cart-dropdown .product-title a {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
        /* Adjust as needed */
        line-height: 1.3em;
        max-height: 3.9em;
        /* 1.3em * 3 lines */
    }

    .cart-dropdown .product-title {
        overflow: hidden;
    }


    .header-10 .header-bottom .menu>li:not(:hover):not(.active):not(.show)>a {
        color: black;
    }

    .header.header-10.header-intro-clearance .header-bottom .header-right p {
        font-weight: 600;
        letter-spacing: -.01em;
        color: black;
    }
</style>
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
                                <li><a href="user_dashboard_overview.php"><i class="icon-user"></i>Dashboard</a></li>
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
        <div class="container d-flex align-items-center justify-content-between">
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

                                        $productDetailsInCart = $cartItem['product']; // Renamed for clarity
                                        $quantityInCart = $cartItem['quantity'];
                                        $costInCart = $cartItem['cost']; // Unit cost (potentially promotional)
                                
                                        // Image path from $productDetailsInCart (fetched by Cart::getCartDetails)
                                        // Ensure 'image_path' key exists and file_exists checks the correct local path
                                        $imagePathFromCart = $productDetailsInCart['image_path'] ?? '';
                                        $imageSrc = (!empty($imagePathFromCart) && file_exists(ltrim($imagePathFromCart, './')))
                                            ? htmlspecialchars(ltrim($imagePathFromCart, './'))
                                            : 'assets/images/products/default-product.jpg'; // Default image
                                
                                        ?>
                                        <div class="product">
                                            <div class="product-cart-details">
                                                <h4 class="product-title">
                                                    <a href="product-detail.php?itemid=<?= (int) $itemId ?>">
                                                        <?= htmlspecialchars($productDetailsInCart['description'] ?? 'Product') ?>
                                                    </a>
                                                </h4>
                                                <span class="cart-product-info">
                                                    <span class="cart-product-qty"><?= (int) $quantityInCart ?></span>
                                                    &nbsp;x &#8358;&nbsp;<?= number_format($costInCart, 2) ?>
                                                </span>
                                            </div><!-- End .product-cart-details -->
                                            <figure class="product-image-container">
                                                <a href="product-detail.php?itemid=<?= (int) $itemId ?>" class="product-image">
                                                    <img src="<?= $imageSrc ?>"
                                                        alt="<?= htmlspecialchars($productDetailsInCart['description'] ?? 'Product Image') ?>">
                                                </a>
                                            </figure>
                                            <a href="cart.php?remove=<?= (int) $itemId ?>" class="btn-remove" <?php // Assuming 'remove' is the GET param your cart.php expects ?> title="Remove Product"><i
                                                    class="icon-close"></i></a>
                                        </div><!-- End .product -->
                                    <?php endforeach; ?>
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
        <div class="header-bottom sticky-header" style="background-color: #f8f9fa; color: black;">
            <div class="container d-flex align-items-center justify-content-between">
                <div class="header-left">
                    <div class="dropdown category-dropdown">
                        <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false" data-display="static" title="Browse Categories">
                            Browse Categories </i>
                        </a>
                        <div class="dropdown-menu">
                            <nav class="side-nav">
                                <ul class="menu-vertical sf-arrows">
                                    <?php if (!empty($categoriesWithSubcategories)): ?>
                                        <?php foreach ($categoriesWithSubcategories as $parentCategory): ?>
                                            <li class="megamenu-container">
                                                <?php // Ensure 'cat' and 'catid' are the correct keys from your DB ?>
                                                <a class="sf-with-ul"
                                                    href="shop.php?category=<?= htmlspecialchars($parentCategory['category_id'] ?? '') ?>">
                                                    <?= htmlspecialchars(ucfirst(strtolower($parentCategory['name'] ?? 'Category'))) ?>
                                                </a>
                                                <?php if (!empty($parentCategory['subcategories'])): ?>
                                                    <ul>
                                                        <?php foreach ($parentCategory['subcategories'] as $subCategory): ?>
                                                            <li class="megamenu-container"> <!-- Apply class if sub-subs exist -->
                                                                <a
                                                                    href="shop.php?category=<?= htmlspecialchars($subCategory['category_id'] ?? '') ?>">
                                                                    <?= htmlspecialchars(ucfirst(strtolower($subCategory['name'] ?? 'Subcategory'))) ?>
                                                                </a>
                                                                <?php if (!empty($subCategory['subsubcategories'])): ?>
                                                                    <ul>
                                                                        <?php foreach ($subCategory['subsubcategories'] as $subSubCategory): ?>
                                                                            <li><a
                                                                                    href="shop.php?category=<?= htmlspecialchars($subSubCategory['category_id'] ?? '') ?>">
                                                                                    <?= htmlspecialchars(ucfirst(strtolower($subSubCategory['name'] ?? 'Sub-Subcategory'))) ?>
                                                                                </a></li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
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

<script>
    $(document).ready(function () {
        // Get the current page URL
        var currentPageUrl = window.location.href;
        var currentPath = window.location.pathname; // More reliable for matching, e.g., /shop.php

        // Loop through each navigation link in the main menu
        $('.main-nav .menu li a').each(function () {
            var linkUrl = $(this).attr('href');

            // Remove any existing 'active' class from the parent li
            // This is important if you have a default active class (like on "Home")
            // and want to ensure only the correct one is highlighted.
            // However, your "Home" link has 'active' on the <li>, so we'll target that.
            $(this).parent('li').removeClass('active');

            // Check if the link's href is part of the current page URL or matches the path
            if (currentPageUrl.indexOf(linkUrl) !== -1 || currentPath.endsWith(linkUrl)) {
                // Add 'active' class to the parent <li> of the matched link
                $(this).parent('li').addClass('active');
                // If you only want one link to be active, you might 'return false;' here to stop the loop.
            }
        });
    });
</script>