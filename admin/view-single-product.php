<?php
// filepath: c:\wamp64\www\goodguy\admin\view-single-product.php
include "../includes.php";


include "../class/Vendor.php";

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$productId) {
    echo "<h3>Product not found.</h3>";
    exit();
}

$productItem = new ProductItem($pdo);
$categoryObj = new Category($pdo);
$vendorObj = new Vendor($pdo);

// Get product details
$product = $productItem->getProductById($productId);
if (!$product) {
    echo "<h3>Product not found.</h3>";
    exit();
}

// Get related inventory items
$inventoryItems = $productItem->getInventoryItemsByProductId($productId);

// Get vendor and category info
$vendor = $vendorObj->getVendorById($product['vendor_id']);
$category = $categoryObj->getCategoryById($product['category']);

// Get product images
$productImages = $productItem->getProductImages($productId);
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
    <title>View Product - <?= htmlspecialchars($product['product_name']) ?></title>
    <?php include 'admin-header.php'; ?>
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <style>
        .swiper-container {
            width: 100%;
            height: 300px;
            /* Adjust as needed */
        }

        .swiper-slide {
            text-align: center;
            font-size: 18px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .swiper-slide img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: #000;
        }
    </style>
</head>

<body>

    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content mt-5">
            <section class="py-0">

                <div class="container-small">
                    <nav class="mb-3" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="#">Fashion</a></li>
                            <li class="breadcrumb-item"><a href="#">Womens fashion</a></li>
                            <li class="breadcrumb-item"><a href="#">Footwear</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Hills</li>
                        </ol>
                    </nav>
                    <div class="row g-5 mb-5 mb-lg-8" data-product-details="data-product-details">
                        <div class="col-12 col-lg-6">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-2 col-lg-12 col-xl-2">
                                    <div class="swiper-products-thumb swiper theme-slider overflow-visible swiper-initialized swiper-vertical swiper-backface-hidden swiper-thumbs"
                                        id="swiper-products-thumb">
                                        <div class="swiper-wrapper" id="swiper-wrapper-3769d29105efad810f"
                                            aria-live="polite"
                                            style="transform: translate3d(0px, 0px, 0px); transition-duration: 0ms; transition-delay: 0ms;">



                                        </div><span class="swiper-notification" aria-live="assertive"
                                            aria-atomic="true"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-10 col-lg-12 col-xl-10">
                                    <div
                                        class="d-flex align-items-center border border-translucent rounded-3 text-center p-5 h-100">
                                        <div class="swiper theme-slider swiper-initialized swiper-horizontal swiper-backface-hidden"
                                            data-thumb-target="swiper-products-thumb"
                                            data-products-swiper="{&quot;slidesPerView&quot;:1,&quot;spaceBetween&quot;:16,&quot;thumbsEl&quot;:&quot;.swiper-products-thumb&quot;}">
                                            <div class="swiper-wrapper" id="swiper-wrapper-713a865fa3ebae6f"
                                                aria-live="polite"
                                                style="transition-duration: 0ms; transform: translate3d(-824px, 0px, 0px); transition-delay: 0ms;">

                                            </div><span class="swiper-notification" aria-live="assertive"
                                                aria-atomic="true"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="d-flex flex-column justify-content-between h-100">
                                <div>
                                    <div class="d-flex flex-wrap">
                                        <div class="me-2"><svg class="svg-inline--fa fa-star text-warning"
                                                aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star"
                                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z">
                                                </path>
                                            </svg><!-- <span class="fa fa-star text-warning"></span> Font Awesome fontawesome.com --><svg
                                                class="svg-inline--fa fa-star text-warning" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="star" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z">
                                                </path>
                                            </svg><!-- <span class="fa fa-star text-warning"></span> Font Awesome fontawesome.com --><svg
                                                class="svg-inline--fa fa-star text-warning" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="star" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z">
                                                </path>
                                            </svg><!-- <span class="fa fa-star text-warning"></span> Font Awesome fontawesome.com --><svg
                                                class="svg-inline--fa fa-star text-warning" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="star" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z">
                                                </path>
                                            </svg><!-- <span class="fa fa-star text-warning"></span> Font Awesome fontawesome.com --><svg
                                                class="svg-inline--fa fa-star text-warning" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="star" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z">
                                                </path>
                                            </svg><!-- <span class="fa fa-star text-warning"></span> Font Awesome fontawesome.com -->
                                        </div>
                                        <p class="text-primary fw-semibold mb-2">6548 People rated and reviewed </p>
                                    </div>
                                    <h3 class="mb-3 lh-sm">  <?= htmlspecialchars($product['product_name']) ?></h3>
                                    <div class="d-flex flex-wrap align-items-start mb-3"><span
                                            class="badge text-bg-success fs-9 rounded-pill me-2 fw-semibold">#1 Best
                                            seller</span><a class="fw-semibold" href="#!">in Phoenix sell analytics
                                            2021</a></div>
                                    <div class="d-flex flex-wrap align-items-center">
                                        <?php
                                                    // … after loading $productItem, add:
                                                    $costRange = $productItem->getCostRange($productId);
                                                    ?>
                                                                                            <?php if ($costRange): ?>
                                                            <h1 class="me-3">$<?= htmlspecialchars($costRange) ?></h1>
                                                        <?php else: ?>
                                                            <h1 class="me-3">N/A</h1>
                                                        <?php endif; ?>
                                                        <!-- optional: strike-through original MSRP or show discount -->
                                                        <?php if (!empty($product['msrp'])): ?>
                                                            <p class="text-body-quaternary text-decoration-line-through fs-6 mb-0 me-3">
                                                                $<?= number_format($product['msrp'], 2) ?>
                                                            </p>
                                                        <?php endif; ?>
                                        <p class="text-warning fw-bolder fs-6 mb-0">10% off</p>
                                    </div>
                                    <p class="text-success fw-semibold fs-7 mb-2">In stock</p>
                                 
                                    <div class="mb-3">
                                        <p class="fw-semibold mb-2 text-body">Status:</p>
                                        <select class="form-select w-auto" id="productStatus">
                                            <option value="active"
                                                <?= ($product['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active
                                            </option>
                                            <option value="inactive"
                                                <?= ($product['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>
                                                Inactive</option>
                                            <option value="draft"
                                                <?= ($product['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft
                                            </option>
                                        </select>
                                    </div>
                                    <p class="text-danger-dark fw-bold mb-5 mb-lg-0">Special offer ends in 23:00:45
                                        hours</p>
                                </div>
                                <div>
                                    <?php
                                    require_once __DIR__ . '/../class/Variation.php';
                                    $variantObj = new Variation($pdo);
                             

// … after loading $productItem …
$colorVariants = $productItem->getColorVariationsWithImages($productId);


?>
<div class="mb-3">
    <p class="fw-semibold mb-2">Color:
        <span class="text-body-emphasis" data-product-color>
            <?= htmlspecialchars($colorVariants[0]['color'] ?? '') ?>
        </span>
    </p>
    <div class="d-flex product-color-variants" data-product-color-variants>
       <?php foreach ($colorVariants as $i => $v): ?>
    <div class="rounded-1 border border-translucent me-2 <?= $i===0 ? 'active' : '' ?>"
         data-variant="<?= htmlspecialchars($v['color']) ?>"
         data-products-images='<?= json_encode(
             array_map(fn($path) => "../{$path}", $v['images']),
             JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES
         ) ?>'>
        <img src="../<?= htmlspecialchars($v['thumbnail']) ?>"
             alt="<?= htmlspecialchars($v['color']) ?>" width="38">
    </div>
<?php endforeach; ?>
    </div>
    <div class="d-flex align-items-center mt-2">

        <a href="manage-product-images.php?id=<?= $productId ?>&color=<?= urlencode($colorVariants[0]['color']) ?>" class="btn btn-phoenix-primary px-3" data-product-color-select>Delete/Edit image</a>
    </div>
</div>

                                
                                    <div class="row g-3 g-sm-5 align-items-end">
                                        <div class="col-12 col-sm-auto">
                                            <p class="fw-semibold mb-2 text-body">Size : </p>
                                            <div class="d-flex align-items-center">
                                                <select class="form-select w-auto">
                                                    <option value="44">44</option>
                                                    <option value="22">22</option>
                                                    <option value="18">18</option>
                                                </select><a class="ms-2 fs-9 fw-semibold" href="#!">Size chart</a>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm">
                                            <p class="fw-semibold mb-2 text-body">Quantity : </p>
                                            <div class="d-flex justify-content-between align-items-end">
                                                <div class="d-flex flex-between-center" data-quantity="data-quantity">
                                                    <button class="btn btn-phoenix-primary px-3" data-type="minus"><svg
                                                            class="svg-inline--fa fa-minus" aria-hidden="true"
                                                            focusable="false" data-prefix="fas" data-icon="minus"
                                                            role="img" xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 448 512" data-fa-i2svg="">
                                                            <path fill="currentColor"
                                                                d="M432 256c0 17.7-14.3 32-32 32L48 288c-17.7 0-32-14.3-32-32s14.3-32 32-32l352 0c17.7 0 32 14.3 32 32z">
                                                            </path>
                                                        </svg><!-- <span class="fas fa-minus"></span> Font Awesome fontawesome.com --></button>
                                                    <input
                                                        class="form-control text-center input-spin-none bg-transparent border-0 outline-none"
                                                        style="width:50px;" type="number" min="1" value="2">
                                                    <button class="btn btn-phoenix-primary px-3" data-type="plus"><svg
                                                            class="svg-inline--fa fa-plus" aria-hidden="true"
                                                            focusable="false" data-prefix="fas" data-icon="plus"
                                                            role="img" xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 448 512" data-fa-i2svg="">
                                                            <path fill="currentColor"
                                                                d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z">
                                                            </path>
                                                        </svg><!-- <span class="fas fa-plus"></span> Font Awesome fontawesome.com --></button>
                                                </div>
                                                <button class="btn btn-phoenix-primary px-3 border-0"><svg
                                                        class="svg-inline--fa fa-share-nodes fs-7" aria-hidden="true"
                                                        focusable="false" data-prefix="fas" data-icon="share-nodes"
                                                        role="img" xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 448 512" data-fa-i2svg="">
                                                        <path fill="currentColor"
                                                            d="M352 224c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96c0 4 .2 8 .7 11.9l-94.1 47C145.4 170.2 121.9 160 96 160c-53 0-96 43-96 96s43 96 96 96c25.9 0 49.4-10.2 66.6-26.9l94.1 47c-.5 3.9-.7 7.8-.7 11.9c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-25.9 0-49.4 10.2-66.6 26.9l-94.1-47c.5-3.9 .7-7.8 .7-11.9s-.2-8-.7-11.9l94.1-47C302.6 213.8 326.1 224 352 224z">
                                                        </path>
                                                    </svg><!-- <span class="fas fa-share-alt fs-7"></span> Font Awesome fontawesome.com --></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end of .container-->

            </section>

        </div>
    </main>


    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/lodash/lodash.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/feather-icons/feather.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dayjs/dayjs.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/swiper/swiper-bundle.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dropzone/dropzone-min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/rater-js/index.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/glightbox/glightbox.min.js"> </script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
</body>

</html>