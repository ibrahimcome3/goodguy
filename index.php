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
    <title>Welcome to GoodGuyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    x and
    <link rel="stylesheet" href="node_modules/swiper/swiper-bundle.min.css">


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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
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

        .intro-slide {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .intro-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(109, 108, 108, 0.4);
            /* Dark overlay for better text readability */
            z-index: 1;
        }

        .intro-content {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 2rem;
        }

        .intro-title {
            font-size: 3rem;
            font-weight: 700;

        }

        .intro-subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            color: #c96 !important;
        }

        .intro-text {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .intro-title {
                font-size: 2rem;
            }

            .intro-subtitle {
                font-size: 1.2rem;
            }

            .intro-slide {
                min-height: 300px;
            }
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

        /* Add this to your assets/css/demos/demo-13.css or a custom CSS file */
        .new-slider-section .swiper-slide {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            /* background-color: #f0f0f0; /* Optional placeholder background */
            position: relative;
            /* For absolute positioning of captions */
        }

        .new-slider-section .swiper-slide img {
            display: block;
            width: 100%;
            height: 400px;
            /* Or your desired height */
            object-fit: cover;
            /* Ensures image covers the slide area */
        }

        .new-slider-section .swiper-slide-caption {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.2em;
        }

        /* Style Swiper navigation buttons if needed */
        .new-slider-section .swiper-button-next,
        .new-slider-section .swiper-button-prev {
            color: #08C;
            /* Example color */
        }

        .new-slider-section .swiper-pagination-bullet-active {
            background: #08C;
            /* Example color for active pagination dot */
        }

        .cat-group-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 16px 18px 14px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .cat-group-card .group-title {
            font-size: 1.25rem;
            margin: 0 0 12px;
            font-weight: 600;
        }

        .cat-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 14px;
            flex-grow: 1;
        }

        .cat-mini {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cat-mini .thumb {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #f7f7f7;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 4px;
        }

        .cat-mini img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .thumb-fallback {
            font-size: 1.6rem;
            font-weight: 600;
            color: #000000ff;
        }

        .cat-mini .label {
            font-size: .8rem;
            line-height: 1.1;
            color: #000000ff;
            font-weight: 500;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cat-mini:hover .label {
            color: #c96;
        }

        .see-more-link {
            margin-top: 14px;
            font-size: .85rem;
            color: #007185;
            text-decoration: none;
        }

        .see-more-link:hover {
            text-decoration: underline;
        }

        .categoryGroupSwiper .swiper-slide {
            width: auto;
        }

        .categoryGroupSwiper .swiper-button-prev,
        .categoryGroupSwiper .swiper-button-next {
            color: #333;
        }

        @media (min-width: 768px) {
            .cat-group-card {
                padding: 18px 20px 16px;
            }

            .cat-mini .label {
                font-size: .82rem;
            }
        }

        .cat-individual-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px 15px;
            height: 100%;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .cat-individual-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .cat-individual-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .cat-individual-thumb {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .cat-individual-card:hover .cat-individual-thumb {
            border-color: #c96;
            background: #fff5f5;
        }

        .cat-individual-thumb img {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }

        .thumb-fallback {
            font-size: 2rem;
            font-weight: 600;
            color: #666;
        }

        .cat-individual-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 10px;
            color: #333;
            line-height: 1.3;
        }

        .shop-now-text {
            font-size: 0.9rem;
            color: #c96;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .cat-individual-card:hover .shop-now-text {
            opacity: 1;
        }

        .cat-individual-card:hover .cat-individual-title {
            color: #c96;
        }

        .categoryIndividualSwiper .swiper-slide {
            height: auto;
        }

        .categoryIndividualSwiper .swiper-button-prev,
        .categoryIndividualSwiper .swiper-button-next {
            color: #c96;
            background: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-top: -20px;
        }

        .categoryIndividualSwiper .swiper-button-prev:after,
        .categoryIndividualSwiper .swiper-button-next:after {
            font-size: 16px;
        }

        .categoryIndividualSwiper .swiper-pagination-bullet {
            background: #c96;
            opacity: 0.3;
        }

        .categoryIndividualSwiper .swiper-pagination-bullet-active {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .cat-individual-card {
                padding: 15px 10px;
            }

            .cat-individual-thumb {
                width: 60px;
                height: 60px;
            }

            .cat-individual-thumb img {
                max-width: 45px;
                max-height: 45px;
            }

            .cat-individual-title {
                font-size: 1rem;
            }
        }
    </style>

</head>

<body>
    <div class="page-wrapper">

        <?php

        include "header_main.php";
        ?>

        <main class="main">

            <div class="intro-slider-container mb-5">
                <div class="intro-slider swiper-container swiper-theme nav-inner pg-inner" data-swiper-options='{
                        "slidesPerView": 1,
                        "spaceBetween": 0,
                        "loop": true,
                        "nav": false,
                        "autoplay": {
                            "delay": 6000,
                            "disableOnInteraction": false
                        },
                        "pagination": {
                            "el": ".swiper-pagination",
                            "clickable": true
                        },
                        "navigation": {
                            "nextEl": ".swiper-button-next",
                            "prevEl": ".swiper-button-prev"
                        }
                    }'>
                    <div class="swiper-wrapper">
                        <div class="swiper-slide intro-slide"
                            style="background-image: url(banner/c.png); background-size: cover; background-position: center; min-height: 400px;">
                            <div class="intro-content d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-white">
                                    <h1 class="intro-title mb-3">Welcome to GoodGuy</h1>
                                    <h3 class="intro-subtitle text-primary mb-3">Your One-Stop Shop</h3>
                                    <h4 class="intro-text mb-4">Discover amazing products at unbeatable prices</h4>
                                    <a href="shop.php" class="btn btn-primary btn-lg">Shop Now</a>
                                </div>
                            </div>
                        </div><!-- End .swiper-slide -->

                        <div class="swiper-slide intro-slide"
                            style="background-image: url(banner/d.png); background-size: cover; background-position: center; min-height: 400px;">
                            <div class="intro-content d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-white">
                                    <h1 class="intro-title mb-3">Quality Products</h1>
                                    <h3 class="intro-subtitle text-primary mb-3">Best Deals</h3>
                                    <p class="intro-text mb-4">Shop electronics, clothing, books and more</p>
                                    <a href="shop.php" class="btn btn-primary btn-lg">Explore Now</a>
                                </div>
                            </div>
                        </div><!-- End .swiper-slide -->
                    </div><!-- End .swiper-wrapper -->

                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>


            <!-- ========================= POPULAR CATEGORIES ========================= -->

            <div class="container mb-5">
                <h2 class="title-lg text-center mb-4">Popular Categories </h2>

                <div class="swiper categoryGroupSwiper ">
                    <div class="swiper-wrapper">
                        <?php
                        try {
                            // Fetch top-level categories to create a slide for each
                            $top_level_cats_stmt = $pdo->query("SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                            $top_level_categories = $top_level_cats_stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($top_level_categories as $top_cat) {
                                // For each top-level category, fetch up to 4 child categories with product images
                                $sub_cat_sql = "
                                    SELECT c.category_id, c.name, (
                                        SELECT iii.image_path
                                        FROM inventoryitem ii
                                        JOIN product_categories pc ON ii.productItemID = pc.product_id
                                        JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1
                                        WHERE pc.category_id = c.category_id AND ii.status = 'active' AND iii.image_path IS NOT NULL AND iii.image_path != ''
                                        ORDER BY ii.date_added DESC
                                        LIMIT 1
                                    ) as image_path
                                    FROM categories c
                                    WHERE c.parent_id = :parent_id
                                    HAVING image_path IS NOT NULL
                                    ORDER BY c.name ASC
                                    LIMIT 4
                                ";
                                $sub_cat_stmt = $pdo->prepare($sub_cat_sql);
                                $sub_cat_stmt->execute([':parent_id' => $top_cat['category_id']]);
                                $sub_categories = $sub_cat_stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Only create a slide if there are sub-categories with images
                                if (!empty($sub_categories)) {
                                    ?>
                                    <div class="swiper-slide">
                                        <div class="cat-group-card">
                                            <h3 class="group-title"><?= htmlspecialchars($top_cat['name']) ?></h3>
                                            <div class="cat-mini-grid">
                                                <?php foreach ($sub_categories as $sub_cat): ?>
                                                    <a href="shop.php?category=<?= $sub_cat['category_id'] ?>" class="cat-mini">
                                                        <div class="thumb">
                                                            <img src="<?= htmlspecialchars($sub_cat['image_path']) ?>"
                                                                alt="<?= htmlspecialchars($sub_cat['name']) ?>">
                                                        </div>
                                                        <span class="label"><?= htmlspecialchars($sub_cat['name']) ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <a href="shop.php?category=<?= $top_cat['category_id'] ?>" class="see-more-link">Shop
                                                all in <?= htmlspecialchars($top_cat['name']) ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching category groups for slider: " . $e->getMessage());
                        }
                        ?>
                    </div>
                    <div class="swiper-button-prev cat-group-prev"></div>
                    <div class="swiper-button-next cat-group-next"></div>
                    <div class="swiper-pagination cat-group-pagination d-md-none"></div>
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
                        LEFT JOIN product_categories pc ON p.productID = pc.product_id
                        LEFT JOIN categories cn ON pc.category_id = cn.category_id
                        LEFT JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1

                        LEFT JOIN promooffering po_item ON ii.InventoryItemID = po_item.inventory_item_id
                                                        AND po_item.start_date <= NOW() AND po_item.end_date >= NOW()

                        LEFT JOIN promooffering po_prod ON p.productID = po_prod.product_id
                                                        AND po_prod.inventory_item_id IS NULL -- Crucial: Only match product-wide
                                                        AND po_prod.start_date <= NOW() AND po_prod.end_date >= NOW()
                        WHERE
                            ii.status = 'active'
                            AND iii.image_path IS NOT NULL AND iii.image_path != ''
                        GROUP BY
                            ii.InventoryItemID
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
                                            <a href="shop.php?category=<?= $categoryId ?>">
                                                <?= htmlspecialchars($categoryName) ?></a>
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
                        JOIN product_categories pc ON p.productID = pc.product_id
                        JOIN categories cn ON pc.category_id = cn.category_id
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
                        GROUP BY
                            ii.InventoryItemID
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
                                                <a href="shop.php?category=<?= $categoryId ?>">
                                                    <?= htmlspecialchars($categoryName) ?></a>
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
                    <?php // Link to shop.php, passing the 'Electronics' category identifier ?>
                    <a href="shop.php?category=1" class="btn btn-outline-primary-2"><span>View More Electronics</span><i
                            class="icon-long-arrow-right"></i></a>
                </div>
            </div>
            <!-- ========================= ELECTRONICS PRODUCTS END ========================= -->




            <!-- ========================= CATEGORY GROUP SLIDER ========================= -->
            <?php
            // Fetch top-level categories with an image/icon (adjust column if different)
            $catLimit = 12; // Number of individual category slides
            $catRows = [];
            try {
                $stmt = $pdo->query("
                    SELECT category_id, name, icon_class 
                    FROM categories 
                    WHERE parent_id IS NULL 
                    ORDER BY name ASC 
                    LIMIT {$catLimit}");
                $catRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('Category individual slider: ' . $e->getMessage());
            }
            ?>



            <style>
                .cat-individual-card {
                    background: #fff;
                    border: 1px solid #e5e5e5;
                    border-radius: 8px;
                    padding: 20px 15px;
                    height: 100%;
                    text-align: center;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .cat-individual-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                }

                .cat-individual-link {
                    text-decoration: none;
                    color: inherit;
                    display: block;
                    height: 100%;
                }

                .cat-individual-thumb {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 15px;
                    border-radius: 50%;
                    background: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    border: 2px solid #e9ecef;
                    transition: all 0.3s ease;
                }

                .cat-individual-card:hover .cat-individual-thumb {
                    border-color: #c96;
                    background: #fff5f5;
                }

                .cat-individual-thumb img {
                    max-width: 60px;
                    max-height: 60px;
                    object-fit: contain;
                }

                .thumb-fallback {
                    font-size: 2rem;
                    font-weight: 600;
                    color: #666;
                }

                .cat-individual-title {
                    font-size: 1.1rem;
                    font-weight: 600;
                    margin: 0 0 10px;
                    color: #333;
                    line-height: 1.3;
                }

                .shop-now-text {
                    font-size: 0.9rem;
                    color: #c96;
                    font-weight: 500;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }

                .cat-individual-card:hover .shop-now-text {
                    opacity: 1;
                }

                .cat-individual-card:hover .cat-individual-title {
                    color: #c96;
                }

                .categoryIndividualSwiper .swiper-slide {
                    height: auto;
                }

                .categoryIndividualSwiper .swiper-button-prev,
                .categoryIndividualSwiper .swiper-button-next {
                    color: #c96;
                    background: #fff;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                    margin-top: -20px;
                }

                .categoryIndividualSwiper .swiper-button-prev:after,
                .categoryIndividualSwiper .swiper-button-next:after {
                    font-size: 16px;
                }

                .categoryIndividualSwiper .swiper-pagination-bullet {
                    background: #c96;
                    opacity: 0.3;
                }

                .categoryIndividualSwiper .swiper-pagination-bullet-active {
                    opacity: 1;
                }

                @media (max-width: 768px) {
                    .cat-individual-card {
                        padding: 15px 10px;
                    }

                    .cat-individual-thumb {
                        width: 60px;
                        height: 60px;
                    }

                    .cat-individual-thumb img {
                        max-width: 45px;
                        max-height: 45px;
                    }

                    .cat-individual-title {
                        font-size: 1rem;
                    }
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new Swiper('.categoryIndividualSwiper', {
                        slidesPerView: 2,
                        spaceBetween: 16,
                        loop: false,
                        autoplay: {
                            delay: 3000,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true
                        },
                        navigation: {
                            nextEl: '.cat-individual-next',
                            prevEl: '.cat-individual-prev'
                        },
                        pagination: {
                            el: '.cat-individual-pagination',
                            clickable: true
                        },
                        breakpoints: {
                            480: {
                                slidesPerView: 3,
                                spaceBetween: 16
                            },
                            768: {
                                slidesPerView: 4,
                                spaceBetween: 20
                            },
                            992: {
                                slidesPerView: 5,
                                spaceBetween: 24
                            },
                            1200: {
                                slidesPerView: 6,
                                spaceBetween: 24
                            }
                        }
                    });
                });
            </script>
            <!-- ========================= CATEGORY INDIVIDUAL SLIDER END ========================= -->
            <div class="container mb-5">
                <div class="swiper categoryGroupSwiper ">
                    <div class="swiper-wrapper">
                        <?php
                        try {
                            // Fetch top-level categories to create a slide for each
                            $top_level_cats_stmt = $pdo->query("SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                            $top_level_categories = $top_level_cats_stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($top_level_categories as $top_cat) {
                                // For each top-level category, fetch up to 4 child categories with product images
                                $sub_cat_sql = "
                                    SELECT c.category_id, c.name, (
                                        SELECT iii.image_path
                                        FROM inventoryitem ii
                                        JOIN product_categories pc ON ii.productItemID = pc.product_id
                                        JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1
                                        WHERE pc.category_id = c.category_id AND ii.status = 'active' AND iii.image_path IS NOT NULL AND iii.image_path != ''
                                        ORDER BY ii.date_added DESC
                                        LIMIT 1
                                    ) as image_path
                                    FROM categories c
                                    WHERE c.parent_id = :parent_id
                                    HAVING image_path IS NOT NULL
                                    ORDER BY c.name ASC
                                    LIMIT 4
                                ";
                                $sub_cat_stmt = $pdo->prepare($sub_cat_sql);
                                $sub_cat_stmt->execute([':parent_id' => $top_cat['category_id']]);
                                $sub_categories = $sub_cat_stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Only create a slide if there are sub-categories with images
                                if (!empty($sub_categories)) {
                                    ?>
                                    <div class="swiper-slide">
                                        <div class="cat-group-card">
                                            <h3 class="group-title"><?= htmlspecialchars($top_cat['name']) ?></h3>
                                            <div class="cat-mini-grid">
                                                <?php foreach ($sub_categories as $sub_cat): ?>
                                                    <a href="shop.php?category=<?= $sub_cat['category_id'] ?>" class="cat-mini">
                                                        <div class="thumb">
                                                            <img src="<?= htmlspecialchars($sub_cat['image_path']) ?>"
                                                                alt="<?= htmlspecialchars($sub_cat['name']) ?>">
                                                        </div>
                                                        <span class="label"><?= htmlspecialchars($sub_cat['name']) ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <a href="shop.php?category=<?= $top_cat['category_id'] ?>" class="see-more-link">Shop
                                                all in <?= htmlspecialchars($top_cat['name']) ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching category groups for slider: " . $e->getMessage());
                        }
                        ?>
                    </div>
                    <div class="swiper-button-prev cat-group-prev"></div>
                    <div class="swiper-button-next cat-group-next"></div>
                    <div class="swiper-pagination cat-group-pagination d-md-none"></div>
                </div>
            </div>



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
    <!-- Sign in / Register Modal -->


    <?php include "jsfile.php"; ?>
    <script src="node_modules/swiper/swiper-bundle.min.js"></script>
    <script src="js/add-to-cart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the main banner slider
            var bannerSwiper = new Swiper('.intro-slider', {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: true,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false
                },
                pagination: {
                    el: '.intro-slider .swiper-pagination',
                    clickable: true
                },
                navigation: {
                    nextEl: '.intro-slider .swiper-button-next',
                    prevEl: '.intro-slider .swiper-button-prev'
                }
            });

            // Initialize the category group slider
            var categoryGroupSwiper = new Swiper('.categoryGroupSwiper', {
                slidesPerView: 1,
                spaceBetween: 24,
                loop: false,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                navigation: {
                    nextEl: '.cat-group-next',
                    prevEl: '.cat-group-prev'
                },
                pagination: {
                    el: '.cat-group-pagination',
                    clickable: true
                },
                breakpoints: {
                    768: { slidesPerView: 2 },
                    1200: { slidesPerView: 3 }
                }
            });

            // Initialize individual category slider (if you add it)
            var categoryIndividualSwiper = new Swiper('.categoryIndividualSwiper', {
                slidesPerView: 2,
                spaceBetween: 16,
                loop: false,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                navigation: {
                    nextEl: '.cat-individual-next',
                    prevEl: '.cat-individual-prev'
                },
                pagination: {
                    el: '.cat-individual-pagination',
                    clickable: true
                },
                breakpoints: {
                    480: {
                        slidesPerView: 3,
                        spaceBetween: 16
                    },
                    768: {
                        slidesPerView: 4,
                        spaceBetween: 20
                    },
                    992: {
                        slidesPerView: 5,
                        spaceBetween: 24
                    },
                    1200: {
                        slidesPerView: 6,
                        spaceBetween: 24
                    }
                }
            });
        });
    </script>

</body>

</html>
