<?php
// filepath: c:\wamp64\www\goodguy\admin\view-single-inventory-item.php
include "../includes.php";
require_once __DIR__ . '/../class/Admin.php';
require_once __DIR__ . '/../class/InventoryItem.php';
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Helper function to render error messages within the admin template
function render_error_page($message)
{
    // This is a simplified version. In a real app, you'd have a proper template function.
    include 'admin-header.php';
    echo '<body><main class="main" id="top">';
    include 'includes/admin_navbar.php'; // Assuming this path is correct
    echo '<div class="content mt-5"><div class="container-fluid"><div class="alert alert-danger" role="alert">' . htmlspecialchars($message) . '</div></div>';
    include 'includes/admin_footer.php'; // Assuming this path is correct
    echo '</div></main></body></html>';
    exit();
}

$inventoryItemId = isset($_GET['inventoryItemId']) ? (int) $_GET['inventoryItemId'] : 0;
if (!$inventoryItemId) {
    render_error_page("No Inventory Item ID provided.");
}

$inventoryItemObj = new InventoryItem($pdo);
$productItemObj = new ProductItem($pdo);
$categoryObj = new Category($pdo);
$adminObj = new Admin($pdo);

// Get inventory item details
$inventoryItem = $inventoryItemObj->getInventoryItemById($inventoryItemId);
if (!$inventoryItem) {
    render_error_page("Inventory Item not found.");
}

// Get parent product details
$product = $productItemObj->getProductById($inventoryItem['productItemID']);
if (!$product) {
    render_error_page("Parent product not found for this inventory item.");
}

// Get all images for this specific inventory item
$images = $inventoryItemObj->getImagesForInventoryItem($inventoryItemId);

// Get other variants for the same product
$otherVariants = $inventoryItemObj->getOtherInventoryItemsForProduct($product['productID'], $inventoryItemId);

// Decode SKU for display
$skuData = json_decode($inventoryItem['sku'], true) ?: [];

// Calculate discounted price
$price = (float) ($inventoryItem['price'] ?? 0.00);
$discountPercentage = (float) ($inventoryItem['discount_percentage'] ?? 0.00);
$discountedPrice = $price - ($price * ($discountPercentage / 100));

?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>View Variant: <?= htmlspecialchars($inventoryItem['description'] ?: 'Item #' . $inventoryItemId) ?></title>
    <?php include 'admin-header.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <style>
        .product-images-container .swiper-slide {
            height: auto;
        }

        .gallery-thumbs .swiper-slide {
            cursor: pointer;
            opacity: 0.6;
            border: 2px solid transparent;
            border-radius: .375rem;
            transition: opacity 0.2s ease-in-out, border-color 0.2s ease-in-out;
        }

        .gallery-thumbs .swiper-slide-thumb-active {
            opacity: 1;
            border-color: var(--phoenix-primary);
        }

        .other-variant-card {
            transition: all 0.2s ease-in-out;
        }

        .other-variant-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--phoenix-box-shadow-sm);
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="view-all-products.php?view=all">Products</a></li>
                    <li class="breadcrumb-item"><a
                            href="view-single-product.php?id=<?= $product['productID'] ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">View Variant</li>
                </ol>
            </nav>

            <div class="row g-3 mb-4 align-items-center">
                <div class="col-auto">
                    <h2 class="mb-0">
                        <?= htmlspecialchars($inventoryItem['description'] ?: 'Variant Details') ?>
                    </h2>
                    <p class="text-body-tertiary mb-0">Part of: <a
                            href="view-single-product.php?id=<?= $product['productID'] ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                    </p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="d-flex gap-2">
                        <a href="edit_inventory_item.php?inventoryItemId=<?= $inventoryItemId ?>"
                            class="btn btn-phoenix-primary"><span class="fas fa-edit me-2"></span>Edit Details</a>
                        <a href="manage-product-images.php?inventoryItemId=<?= $inventoryItemId ?>"
                            class="btn btn-phoenix-secondary"><span class="fas fa-images me-2"></span>Manage Images</a>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-12 col-lg-8">
                    <!-- Image Gallery Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="product-images-container">
                                <!-- Main Swiper -->
                                <div class="swiper gallery-top mb-3">
                                    <div class="swiper-wrapper">
                                        <?php if (!empty($images)): ?>
                                            <?php foreach ($images as $image): ?>
                                                <div class="swiper-slide">
                                                    <a href="../<?= htmlspecialchars($image['image_path']) ?>"
                                                        data-gallery="gallery-item">
                                                        <img class="img-fluid rounded"
                                                            src="../<?= htmlspecialchars($image['image_path']) ?>"
                                                            alt="<?= htmlspecialchars($inventoryItem['description']) ?>" />
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="swiper-slide">
                                                <img class="img-fluid rounded"
                                                    src="../assets/img/products/default-product.png" alt="No Image" />
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="swiper-button-next"></div>
                                    <div class="swiper-button-prev"></div>
                                </div>
                                <!-- Thumbs Swiper -->
                                <?php if (count($images) > 1): ?>
                                    <div class="swiper gallery-thumbs">
                                        <div class="swiper-wrapper">
                                            <?php foreach ($images as $image): ?>
                                                <div class="swiper-slide">
                                                    <img class="img-fluid rounded"
                                                        src="../<?= htmlspecialchars($image['image_path']) ?>" alt="" />
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SKU Properties Card -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">SKU Properties</h5>
                            <a href="manage-sku-properties.php?inventoryItemId=<?= $inventoryItemId ?>"
                                class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-tags me-2"></span>Manage
                                Properties</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($skuData)): ?>
                                <div class="row">
                                    <?php foreach ($skuData as $key => $value): ?>
                                        <div class="col-sm-6 col-md-4 mb-3">
                                            <div class="text-body-tertiary fs-9 text-uppercase">
                                                <?= htmlspecialchars(str_replace('_', ' ', $key)) ?>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <?php if ($key === 'color' && filter_var($value, FILTER_VALIDATE_URL) === false): ?>
                                                    <div class="me-2"
                                                        style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($value) ?>; border: 1px solid #ccc; border-radius: 50%;">
                                                    </div>
                                                <?php endif; ?>
                                                <span class="fw-semibold text-body-emphasis">
                                                    <?= htmlspecialchars($value) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-body-tertiary">No SKU properties have been defined for this item.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Other Variants Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Other Variants of this Product</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($otherVariants)): ?>
                                <div class="row g-3">
                                    <?php foreach ($otherVariants as $variantId => $variant): ?>
                                        <div class="col-6 col-sm-4 col-md-3">
                                            <a href="view-single-inventory-item.php?inventoryItemId=<?= $variantId ?>"
                                                class="text-decoration-none">
                                                <div class="card h-100 other-variant-card">
                                                    <img src="../<?= htmlspecialchars($variant['image_path'] ?? 'assets/img/products/default-product.png') ?>"
                                                        alt="Variant Image" class="card-img-top"
                                                        style="height: 100px; object-fit: cover;">
                                                    <div class="card-body p-2 text-center">
                                                        <h6 class="fs-9 mb-0 text-body-emphasis">
                                                            <?= htmlspecialchars($variant['sku']['color'] ?? 'N/A') ?>
                                                        </h6>
                                                        <p class="fs-10 text-body-tertiary mb-0">
                                                            <?= htmlspecialchars($variant['sku']['size'] ?? 'N/A') ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-body-tertiary">No other variants found for this product.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <div class="col-12 col-lg-4">
                    <div class="sticky-top" style="top: 80px;">
                        <!-- Pricing Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Pricing</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-body-tertiary">Selling Price</h6>
                                    <h6 class="text-body-emphasis fw-bold">
                                        $<?= number_format($price, 2) ?>
                                    </h6>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-body-tertiary">Cost Price</h6>
                                    <h6 class="text-body-emphasis fw-bold text-success">
                                        $<?= number_format((float) $inventoryItem['cost'], 2) ?>
                                    </h6>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-body-tertiary">Discount</h6>
                                    <h6 class="text-body-emphasis fw-bold">
                                        <?= number_format($discountPercentage, 2) ?>%
                                    </h6>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h6 class="text-body-tertiary">Tax Rate</h6>
                                    <h6 class="text-body-emphasis fw-bold">
                                        <?= number_format((float) $inventoryItem['tax_rate'], 2) ?>%
                                    </h6>
                                </div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between">
                                    <h5 class="text-body-tertiary">Final Price</h5>
                                    <h5 class="text-body-emphasis fw-bolder text-primary">
                                        $<?= number_format($discountedPrice, 2) ?>
                                    </h5>
                                </div>
                            </div>
                        </div>

                        <!-- Stock & Status Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Stock & Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-body-tertiary">Quantity on Hand</h6>
                                    <h6 class="text-body-emphasis fw-bold">
                                        <?= (int) ($inventoryItem['quantity'] ?? 0) ?>
                                    </h6>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-body-tertiary">Barcode / SKU</h6>
                                    <h6 class="text-body-emphasis fw-bold">
                                        <?= htmlspecialchars($inventoryItem['barcode'] ?: 'N/A') ?>
                                    </h6>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h6 class="text-body-tertiary">Status</h6>
                                    <?php
                                    $status = $inventoryItem['status'] ?? 'inactive';
                                    $badgeClass = 'badge-phoenix-secondary';
                                    if ($status === 'active') {
                                        $badgeClass = 'badge-phoenix-success';
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>

    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lightbox = GLightbox({ selector: '[data-gallery="gallery-item"]' });

            const galleryThumbs = new Swiper('.gallery-thumbs', {
                spaceBetween: 10,
                slidesPerView: 'auto',
                freeMode: true,
                watchSlidesProgress: true,
                centerInsufficientSlides: true
            });
            const galleryTop = new Swiper('.gallery-top', {
                spaceBetween: 10,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                thumbs: {
                    swiper: galleryThumbs,
                },
            });
        });
    </script>
</body>

</html>