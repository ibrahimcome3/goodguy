<?php
// Ensure session is started BEFORE includes if header needs it
session_start();
require_once "includes.php"; // Provides $pdo, classes, functions

// Instantiate necessary objects (using $pdo)
try {
    // Only instantiate classes needed directly on this page
    $Orvi = new Review($pdo); // For ratings
    $cat = new Category($pdo); // For category links/names if needed elsewhere
    // $p, $product_obj, $promotion, $var_obj might not be needed if data is fetched via SQL JOINs
} catch (Exception $e) {
    error_log("Error instantiating classes in index.php: " . $e->getMessage());
    die("A site error occurred. Please try again later.");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Welcome to GoodGuyng.com</title> <?php // More descriptive title ?>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">

    <style>
        .truncate-2-lines {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* number of lines to show */
            line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 2.4em;
            /* Approx height for 2 lines */
        }

        .product-image {
            max-width: 100%;
            height: auto;
            aspect-ratio: 1 / 1;
            /* Make images square-ish, adjust as needed */
            object-fit: contain;
            /* Fit image within the aspect ratio box */
        }

        .product-grid-item {
            margin-bottom: 25px;
        }

        .product-title {
            margin-bottom: 0.5rem;
            /* Space below title */
        }

        .product-price {
            margin-bottom: 0.5rem;
            /* Space below price */
        }

        .ratings-container {
            margin-bottom: 1rem;
            /* Space below ratings */
        }

        /* Optional: Subtle hover effect */
        .product:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            transition: all 0.2s ease-in-out;
        }

        /* Cart update animation */
        .cart-updated-animation {
            transform: scale(1.2);
            transition: transform 0.3s ease-in-out;
        }

        .btn-wishlist.added-to-wishlist {
            color: #c96;
        }

        .intro-slide .intro-content {
            background-color: rgba(0, 0, 0, 0.6);
            /* Semi-transparent black background - adjust color and opacity */
            padding: 20px 30px;
            /* Add some padding around the text */
            border-radius: 5px;
            /* Optional: rounded corners */
            display: inline-block;
            /* Make the background only as wide as needed, or remove for full width */
            color: #fff;
            /* Ensure text inside is white */
            max-width: 90%;
            /* Prevent it getting too wide on large screens */
        }

        /* Ensure text elements inside are white */
        .intro-slide .intro-content h1,
        .intro-slide .intro-content h3,
        .intro-slide .intro-content .intro-price span,
        .intro-slide .intro-content .intro-price sup,
        .intro-slide .intro-content a.btn {
            color: #fff;
            text-shadow: none;
            /* Shadow might not be needed with a background */
        }

        /* Make primary subtitle stand out if needed */
        .intro-slide .intro-content .intro-subtitle.text-primary {
            color: #facc15;
            /* Example: Yellow */
        }

        /* Adjust button style if needed */
        .intro-slide .intro-content a.btn-primary {
            background-color: #fff;
            color: #333;
            border-color: #fff;
        }

        .intro-slide .intro-content a.btn-primary:hover {
            background-color: #eee;
            border-color: #eee;
            color: #333;
        }

        /* Increase size of the main promotional price */
        .intro-slide .intro-content .intro-price .text-third {
            font-size: 3rem;
            /* Adjust this value */
            font-weight: 600;
            line-height: 1.1;
        }

        /* Increase size and add strikethrough to the regular (old) price */
        .intro-slide .intro-content .intro-price .intro-old-price {
            font-size: 2rem;
            /* Adjust this value */
            font-weight: 400;
            vertical-align: middle;
            margin-right: 8px;
            line-height: 1;
            text-decoration: line-through;
            /* This adds the strikethrough */
            color: #ccc;
            /* Optional: Makes the old price lighter grey */
        }

        /* Optional: Adjust spacing around the price container if needed */
        .intro-slide .intro-content .intro-price {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }


        /* --- Category Block Icon Styling --- */
        .cat-block figure {
            display: flex;
            /* Use flexbox to center the icon */
            justify-content: center;
            align-items: center;
            width: 70px;
            /* Adjust size of the circle */
            height: 70px;
            /* Adjust size of the circle */
            border-radius: 50%;
            /* Make it circular */
            background-color: #f8f8f8;
            /* Light background, adjust as needed */
            margin: 0 auto 1.5rem;
            /* Center the circle horizontally, add space below */
            transition: background-color 0.3s ease, color 0.3s ease;
            /* Smooth hover effect */
        }

        .cat-block figure i {
            font-size: 32px;
            /* Adjust icon size */
            color: #c96;
            /* Theme's primary color (adjust if different) */
            line-height: 1;
            /* Ensure proper vertical alignment */
        }

        /* Optional: Hover effect */
        .cat-block:hover figure {
            background-color: #c96;
            /* Theme color background on hover */
        }

        .cat-block:hover figure i {
            color: #fff;
            /* White icon on hover */
        }

        .cat-block-title {
            font-size: 1.4rem;
            /* Adjust title size if needed */
            color: #333;
            text-align: center;
            margin-top: 0;
            /* Remove default margin if any */
        }

        /* --- End Category Block Icon Styling --- */
    </style>

</head>

<body>
    <div class="page-wrapper">

    <?php

include "header_main.php";
?>

        <main class="main">
            <!-- ========================= BANNER SLIDER ========================= -->
            <div class="intro-slider-container mb-4">
                <div class="intro-slider owl-carousel owl-simple owl-nav-inside" data-toggle="owl" data-owl-options='{
                        "nav": false, "dots": true, "loop": true, "autoplay": true, "autoplayTimeout": 5000,
                        "responsive": { "992": { "nav": true } } }'>
                    <?php
                    try {
                        // Fetch active banners and linked promotion item prices
                        // Assumes 'promotion_items' table has 'promoitemi_d', 'promoPrice', 'regularPrice'
                        // Assumes 'promotion_message' table has 'is_active' column for filtering
                        $sql_banner = "SELECT
                                           pm.title_message,
                                           pm.price_explainer,
                                           pm.banner,
                                           pm.link,
                                           pi.promoPrice,    -- Fetched from promotion_items
                                           pi.regularPrice   -- Fetched from promotion_items
                                       FROM
                                           promotion_message pm
                                       LEFT JOIN
                                           promotion_items pi ON pm.promotion_item = pi.promoitemi_d -- Adjust 'promoitemi_d' if the primary key name in promotion_items is different
                                       WHERE
                                           pm.is_active = 1 -- Make sure 'is_active' column exists in promotion_message table
                                       -- ORDER BY pm.display_order ASC -- Optional: Add a column for ordering banners if needed
                                       ";
                        $stmt_banner = $pdo->query($sql_banner); // Use query() for simple selects without user input
                    

                        while ($row_banner = $stmt_banner->fetch(PDO::FETCH_ASSOC)) {
                            // Basic validation for essential banner fields from promotion_message
                            if (empty($row_banner['banner']) || empty($row_banner['link'])) {
                                error_log("Skipping banner due to missing banner image or link. Data: " . print_r($row_banner, true)); // Log skipped banners for debugging
                                continue; // Skip this banner if essential info is missing
                            }
                            ?>
                            <div class="intro-slide"
                                style="background-image: url(banner/<?= htmlspecialchars($row_banner['banner']) ?>);">
                                <div class="container intro-content">
                                    <?php /* Banner content using fetched data */ ?>
                                    <h3 class="intro-subtitle text-primary">
                                        <?= htmlspecialchars($row_banner['title_message']) ?>
                                    </h3>
                                    <h1 class="intro-title"><?= htmlspecialchars($row_banner['price_explainer']) ?></h1>

                                    <?php // Display prices only if promoPrice is available from the JOINED table
                                            // Add is_numeric check for robustness
                                            if (!empty($row_banner['promoPrice']) && is_numeric($row_banner['promoPrice'])): ?>
                                        <div class="intro-price">
                                            <?php // Display old price only if regularPrice is also available and numeric
                                                        if (!empty($row_banner['regularPrice']) && is_numeric($row_banner['regularPrice'])): ?>
                                                <sup
                                                    class="intro-old-price">N<?= number_format((float) $row_banner['regularPrice']) ?></sup>
                                            <?php endif; ?>
                                            <span class="text-third">
                                                N<?= number_format((float) $row_banner['promoPrice']) ?>
                                            </span>
                                        </div>

                                    <?php endif; ?>

                                    <a href="<?= htmlspecialchars($row_banner['link']) ?>" class="btn btn-primary btn-round">
                                        <span>Shop Now</span> <i class="icon-long-arrow-right"></i>
                                    </a>
                                </div><!-- End .intro-content -->
                            </div><!-- End .intro-slide -->
                            <?php
                        }
                    } catch (PDOException $e) {
                        error_log("Error fetching promotion banners: " . $e->getMessage());
                        // Optionally display a user-friendly message if the slider fails completely
                        // echo "<p class='text-center text-danger'>Error loading promotional banners.</p>";
                    }
                    ?>
                </div><!-- End .intro-slider -->
                <span class="slider-loader"></span>
            </div><!-- End .intro-slider-container -->
            <!-- ========================= BANNER SLIDER END ========================= -->


            <!-- ========================= POPULAR CATEGORIES ========================= -->
            <div class="container categories pt-3 pb-3">
                <h2 class="title-lg text-center mb-4">Shop by Category</h2>
                <div class="row justify-content-center">
                    <?php
                    // Add an 'icon' key with the appropriate class name
                    $top_categories = [
                        ['id' => 'DRINKS', 'name' => 'Drinks', 'icon' => 'icon-coffee'], // Example icon
                        ['id' => 'ELECTRONICS', 'name' => 'Electronics', 'icon' => 'icon-laptop'], // Example icon
                        ['id' => 'Car Parts', 'name' => 'Car Parts', 'icon' => 'icon-cog'],    // Example icon
                        ['id' => 'Cosmetics', 'name' => 'Skin Care', 'icon' => 'icon-leaf'],   // Example icon
                        // Add more categories with their icons if needed
                    ];
                    foreach ($top_categories as $top_cat): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <a href="category.php?catid=<?= urlencode($top_cat['id']) ?>" class="cat-block">
                                <figure>
                                    <?php // Replace the <img> tag with an <i> tag for the icon ?>
                                    <i class="<?= htmlspecialchars($top_cat['icon']) ?>"></i>
                                </figure>
                                <h3 class="cat-block-title"><?= htmlspecialchars($top_cat['name']) ?></h3>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- ========================= POPULAR CATEGORIES END ========================= -->



            <!-- ========================= FEATURED PRODUCTS ========================= -->
            <div class="container featured-products pt-4 pb-2">
                <h2 class="title-lg text-center mb-4">Featured Products</h2>
                <div class="row">
                    <?php
                    try {
                        // --- OPTIMIZED SQL QUERY ---
                        // Fetches product details, category name, and active promotion info in one go.
                        // Filters out products without images. Orders by date added.
                        $limitFeatured = 12; // Number of products to show
                        $sql_featured = "
                        SELECT
                            ii.InventoryItemID, ii.cost, ii.description, ii.date_added,
                            iii.image_path,
                            p.productID,
                            cn.name AS cat_name, cn.category_id AS cat_id,
                            COALESCE(po_item.promoPrice, po_prod.promoPrice) AS promoPrice,
                            COALESCE(po_item.regularPrice, po_prod.regularPrice) AS regularPrice
                        FROM
                            inventoryitem ii
                        JOIN productitem p ON ii.productItemID = p.productID
                        JOIN categories cn ON p.category = cn.category_id
                        LEFT JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1

                        LEFT JOIN promooffering po_item ON ii.InventoryItemID = po_item.inventory_item_id
                                                        AND po_item.start_date <= NOW() AND po_item.end_date >= NOW()

                        LEFT JOIN promooffering po_prod ON p.productID = po_prod.product_id
                                                        AND po_prod.inventory_item_id IS NULL -- Crucial: Only match product-wide
                                                        AND po_prod.start_date <= NOW() AND po_prod.end_date >= NOW()
                        WHERE
                            ii.status = 'active'
                            AND iii.image_path IS NOT NULL AND iii.image_path != ''
                        ORDER BY
                            ii.date_added DESC
                        LIMIT :limit
                        ";
                        $stmt_featured = $pdo->prepare($sql_featured);
                        $stmt_featured->bindParam(':limit', $limitFeatured, PDO::PARAM_INT);
                        $stmt_featured->execute();

                        while ($row = $stmt_featured->fetch(PDO::FETCH_ASSOC)) {
                            $itemId = $row['InventoryItemID'];
                            $imagePath = $row['image_path']; // Directly from query
                            $categoryName = $row['cat_name'] ?? 'Uncategorized';
                            $categoryId = $row['cat_id'];
                            $isPromo = !empty($row['promoPrice']); // Check if promo price exists and is valid
                            $isNew = (strtotime($row['date_added']) > strtotime('-30 days')); // Example 'New' logic
                    
                            // Get ratings separately (still potential N+1, but isolated)
                            $ratingWidth = $Orvi->get_rating_($itemId);
                            $reviewCount = $Orvi->get_rating_review_number($itemId);
                            ?>
                            <div class="col-6 col-md-4 col-lg-3 product-grid-item"> <?php // Responsive Grid ?>
                                <div class="product text-center"> <?php // Center content ?>
                                    <figure class="product-media">
                                        <?php if ($isPromo): ?><span class="product-label label-sale">Sale</span><?php endif; ?>
                                        <?php if ($isNew): ?><span class="product-label label-new">New</span><?php endif; ?>
                                        <a href="product-detail.php?itemid=<?= $itemId ?>">
                                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                                alt="<?= htmlspecialchars($row['description']) ?>" class="product-image"
                                                loading="lazy">
                                        </a>
                                        <div class="product-action-vertical">
                                            <a href="#" data-product-id="<?= $itemId ?>"
                                                class="btn-product-icon btn-wishlist btn-expandable"
                                                title="Add to Wishlist"><span>add to wishlist</span></a>
                                            <?php /* Quickview can be complex, consider removing for simplicity */ ?>
                                            <!-- <a href="<?= htmlspecialchars($imagePath) ?>" class="btn-product-icon btn-quickview" title="Quick view"><span>Quick view</span></a> -->
                                        </div>
                                        <div class="product-action">
                                            <a href="#" product-info="<?= $itemId ?>" class="submit-cart btn-product btn-cart"
                                                title="Add to cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>
                                    <div class="product-body">
                                        <div class="product-cat font-weight-normal">
                                            <a
                                                href="category.php?catid=<?= $categoryId ?>"><?= htmlspecialchars($categoryName) ?></a>
                                        </div>
                                        <h3 class="product-title truncate-2-lines">
                                            <a
                                                href="product-detail.php?itemid=<?= $itemId ?>"><?= htmlspecialchars($row['description']) ?></a>
                                        </h3>
                                        <?php if ($isPromo): ?>
                                            <div class="product-price">
                                                <span
                                                    class="new-price">&#8358;<?= number_format((float) $row['promoPrice'], 2) ?></span>
                                                <?php if (!empty($row['regularPrice'])): ?>
                                                    <span class="old-price">Was
                                                        &#8358;<?= number_format((float) $row['regularPrice'], 2) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="product-price">
                                                <span class="price">N<?= number_format((float) $row['cost'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ratings-container justify-content-center"> <?php // Center ratings ?>
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: <?= $ratingWidth ?>%;"></div>
                                            </div>
                                            <span class="ratings-text">( <?= $reviewCount ?> Reviews )</span>
                                        </div>
                                    </div>
                                </div><!-- End .product -->
                            </div><!-- End Col -->
                            <?php
                        } // end while
                    } catch (PDOException $e) {
                        error_log("Error fetching featured products: " . $e->getMessage());
                        echo '<div class="col-12"><p class="text-danger text-center">Could not load featured products at this time.</p></div>';
                    }
                    ?>
                </div><!-- End .row -->
                <div class="text-center mt-3">
                    <a href="shop.php" class="btn btn-outline-primary-2"><span>View More Products</span><i
                            class="icon-long-arrow-right"></i></a>
                </div>
            </div>
            <!-- ========================= FEATURED PRODUCTS END ========================= -->


            <!-- ========================= ELECTRONICS PRODUCTS ========================= -->
            <div class="container electronics-products pt-4 pb-2"> <?php // Added class for potential styling ?>
                <h2 class="title-lg text-center mb-4">Shop Electronics</h2>
                <div class="row">
                    <?php
                    try {
                        // --- SQL QUERY FOR ELECTRONICS ---
                        $limitElectronics = 8; // Number of electronics products to show
                        // $electronicsCategoryId = 'ELECTRONICS'; // No longer needed as a separate variable for binding
                    
                        $sql_electronics = "
                        SELECT
                            ii.InventoryItemID, ii.cost, ii.description, ii.date_added,
                            iii.image_path,
                            p.productID,
                            cn.name AS cat_name, cn.category_id AS cat_id,
                            COALESCE(po_item.promoPrice, po_prod.promoPrice) AS promoPrice,
                            COALESCE(po_item.regularPrice, po_prod.regularPrice) AS regularPrice
                        FROM
                            inventoryitem ii
                        JOIN productitem p ON ii.productItemID = p.productID
                        JOIN categories cn ON p.category = cn.category_id
                        LEFT JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1

                        LEFT JOIN promooffering po_item ON ii.InventoryItemID = po_item.inventory_item_id
                                                        AND po_item.start_date <= NOW() AND po_item.end_date >= NOW()

                        LEFT JOIN promooffering po_prod ON p.productID = po_prod.product_id
                                                        AND po_prod.inventory_item_id IS NULL
                                                        AND po_prod.start_date <= NOW() AND po_prod.end_date >= NOW()
                        WHERE
                            ii.status = 'active'
                            AND iii.image_path IS NOT NULL AND iii.image_path != ''
                            AND cn.category_id IN (
                                -- Subquery to get direct children and grandchildren of 'Electronics'
                                SELECT category_id FROM (
                                    -- Direct children
                                    SELECT c.category_id
                                    FROM categories c
                                    WHERE c.parent_id = (SELECT category_id FROM categories WHERE name = 'Electronics' LIMIT 1)

                                    UNION ALL

                                    -- Grandchildren
                                    SELECT grandchild.category_id
                                    FROM categories AS grandchild
                                    INNER JOIN categories AS child ON grandchild.parent_id = child.category_id
                                    INNER JOIN categories AS grandparent ON child.parent_id = grandparent.category_id
                                    WHERE grandparent.name = 'Electronics'
                                ) AS electronics_subcategories
                            )
                        ORDER BY
                            ii.date_added DESC -- Or RAND() if you prefer random
                        LIMIT :limit
                        ";
                        $stmt_electronics = $pdo->prepare($sql_electronics);
                        $stmt_electronics->bindParam(':limit', $limitElectronics, PDO::PARAM_INT);
                        $stmt_electronics->execute();

                        if ($stmt_electronics->rowCount() > 0) {
                            while ($row_elec = $stmt_electronics->fetch(PDO::FETCH_ASSOC)) {
                                $itemId = $row_elec['InventoryItemID'];
                                $imagePath = $row_elec['image_path'];
                                $categoryName = $row_elec['cat_name'] ?? 'Electronics'; // Default if name missing
                                $categoryId = $row_elec['cat_id'];
                                $isPromo = !empty($row_elec['promoPrice']);
                                $isNew = (strtotime($row_elec['date_added']) > strtotime('-30 days'));

                                // Get ratings separately
                                $ratingWidth = $Orvi->get_rating_($itemId);
                                $reviewCount = $Orvi->get_rating_review_number($itemId);
                                ?>
                                <div class="col-6 col-md-4 col-lg-3 product-grid-item">
                                    <div class="product text-center">
                                        <figure class="product-media">
                                            <?php if ($isPromo): ?><span class="product-label label-sale">Sale</span><?php endif; ?>
                                            <?php if ($isNew): ?><span class="product-label label-new">New</span><?php endif; ?>
                                            <a href="product-detail.php?itemid=<?= $itemId ?>">
                                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                                    alt="<?= htmlspecialchars($row_elec['description']) ?>" class="product-image"
                                                    loading="lazy">
                                            </a>
                                            <div class="product-action-vertical">
                                                <a href="#" data-product-id="<?= $itemId ?>"
                                                    class="btn-product-icon btn-wishlist btn-expandable"
                                                    title="Add to Wishlist"><span>add to wishlist</span></a>
                                                <?php /* Quickview button removed for simplicity */ ?>
                                            </div>
                                            <div class="product-action">
                                                <a href="#" product-info="<?= $itemId ?>" class="submit-cart btn-product btn-cart"
                                                    title="Add to cart"><span>add to cart</span></a>
                                            </div>
                                        </figure>
                                        <div class="product-body">
                                            <div class="product-cat font-weight-normal">
                                                <?php // Link to the specific sub-category if available, else Electronics ?>
                                                <a
                                                    href="category.php?catid=<?= $categoryId ?>"><?= htmlspecialchars($categoryName) ?></a>
                                            </div>
                                            <h3 class="product-title truncate-2-lines">
                                                <a
                                                    href="product-detail.php?itemid=<?= $itemId ?>"><?= htmlspecialchars($row_elec['description']) ?></a>
                                            </h3>
                                            <?php if ($isPromo): ?>
                                                <div class="product-price">
                                                    <span
                                                        class="new-price">&#8358;<?= number_format((float) $row_elec['promoPrice'], 2) ?></span>
                                                    <?php if (!empty($row_elec['regularPrice'])): ?>
                                                        <span class="old-price">Was
                                                            &#8358;<?= number_format((float) $row_elec['regularPrice'], 2) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="product-price">
                                                    <span class="price">N<?= number_format((float) $row_elec['cost'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ratings-container justify-content-center">
                                                <div class="ratings">
                                                    <div class="ratings-val" style="width: <?= $ratingWidth ?>%;"></div>
                                                </div>
                                                <span class="ratings-text">( <?= $reviewCount ?> Reviews )</span>
                                            </div>
                                        </div>
                                    </div><!-- End .product -->
                                </div><!-- End Col -->
                                <?php
                            } // end while
                        } else {
                            echo '<div class="col-12"><p class="text-info text-center">No electronics products found matching the criteria.</p></div>';
                        }
                    } catch (PDOException $e) {
                        error_log("Error fetching electronics products: " . $e->getMessage());
                        echo '<div class="col-12"><p class="text-danger text-center">Could not load electronics products at this time.</p></div>';
                    }
                    ?>
                </div><!-- End .row -->
                <div class="text-center mt-3">
                    <?php // Link to the main Electronics category page ?>
                    <a href="category.php?catid=ELECTRONICS" <?php // Link still goes to the top-level Electronics category ?>
                        class="btn btn-outline-primary-2"><span>View More Electronics</span><i
                            class="icon-long-arrow-right"></i></a>
                </div>
            </div>
            <!-- ========================= ELECTRONICS PRODUCTS END ========================= -->




        </main><!-- End .main -->

        <footer class="footer footer-2">
            <?php /* Icon boxes can be kept if desired, ensure 4 for alignment */ ?>
            <div class="icon-boxes-container">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side"> <span class="icon-box-icon"><i
                                        class="icon-rocket"></i></span>
                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">Free Shipping</h3>
                                    <p>Orders 100K+</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side"> <span class="icon-box-icon"><i
                                        class="icon-rotate-left"></i></span>
                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">Free Returns</h3>
                                    <p>Within 10 days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side"> <span class="icon-box-icon"><i
                                        class="icon-life-ring"></i></span>
                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">We Support</h3>
                                    <p>24/7 services</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side"> <span class="icon-box-icon"><i
                                        class="icon-secure-payment"></i></span>
                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">Secure Payment</h3>
                                    <p>100% secure</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <?php include "mobile-menue-index-page.php"; // Or the standard mobile-menue.php ?>
    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>

    <?php include "jsfile.php"; ?>

    <!-- Add to Cart / Wishlist AJAX Script -->
    <script>
        $(document).ready(function () {
            // Add to Cart Button Handler
            $('.page-wrapper').on('click', '.submit-cart', function (e) {
                e.preventDefault();
                var $button = $(this);
                var inventoryItemId = $button.attr('product-info');
                if (!inventoryItemId || $button.prop('disabled')) return;

                $button.prop('disabled', true).find('span').text('Adding...');
                $.ajax({
                    type: "POST", url: 'cart.php',
                    data: { inventory_product_id: inventoryItemId, qty: 1 },
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            if (typeof response.cartCount !== 'undefined') {
                                $('.cart-count').text(response.cartCount);
                                $('.cart-dropdown > a').addClass('cart-updated-animation');
                                setTimeout(function () { $('.cart-dropdown > a').removeClass('cart-updated-animation'); }, 1000);
                            }
                            $button.find('span').text('Added!');
                            setTimeout(function () { $button.prop('disabled', false).find('span').text('add to cart'); }, 1500);
                        } else {
                            alert(response.message || "Could not add item.");
                            $button.prop('disabled', false).find('span').text('add to cart');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Add to Cart AJAX Error:", status, error, xhr.responseText);
                        alert("Error adding item. Please try again.");
                        $button.prop('disabled', false).find('span').text('add to cart');
                    }
                });
            });

            // Wishlist Button Handler
            $('.page-wrapper').on('click', '.btn-wishlist', function (e) {
                e.preventDefault();
                var $button = $(this);
                var productId = $button.data('product-id');
                if (!productId || $button.prop('disabled') || $button.hasClass('added-to-wishlist')) return;

                $button.prop('disabled', true).addClass('load-more-loading').find('span').text('Adding...');
                $.ajax({
                    type: 'POST', url: 'add_to_wishlist.php',
                    data: { product_id: productId }, // Ensure backend expects 'product_id'
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            if (typeof response.wishlistCount !== 'undefined') {
                                $('.wishlist-count').text(response.wishlistCount);
                            }
                            $button.removeClass('load-more-loading').addClass('added-to-wishlist')
                                .prop('disabled', false).attr('title', 'In Wishlist').find('span').text('In Wishlist');
                        } else {
                            alert(response.message || 'Could not add item.');
                            $button.removeClass('load-more-loading').prop('disabled', false).find('span').text('add to wishlist');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Wishlist AJAX Error:", status, error);
                        alert('Error adding to wishlist.');
                        $button.removeClass('load-more-loading').prop('disabled', false).find('span').text('add to wishlist');
                    }
                });
            });
        });
    </script>

</body>

</html>