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
        }

        .gallery-thumbs .swiper-slide-thumb-active {
            opacity: 1;
            border: 2px solid var(--phoenix-primary);
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

            <div class="row g-5">
                <div class="col-12 col-lg-7">
                    <div class="row">
                        <div class="col-12">
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
                </div>

                <div class="col-12 col-lg-5">
                    <div class="sticky-top" style="top: 80px;">
                        <h2><?= htmlspecialchars($inventoryItem['description'] ?: 'Variant Details') ?></h2>
                        <p class="text-body-tertiary">Part of: <a
                                href="view-single-product.php?id=<?= $product['productID'] ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                        </p>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <p class="text-body-tertiary">SKU:</p>
                            <p class="fw-bold"><?= htmlspecialchars($inventoryItem['barcode']) ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-body-tertiary">Cost:</p>
                            <p class="fw-bold text-success">$<?= number_format((float) $inventoryItem['cost'], 2) ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-body-tertiary">Quantity on Hand:</p>
                            <p class="fw-bold"><?= (int) ($inventoryItem['quantity'] ?? 0) ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-body-tertiary">Tax:</p>
                            <p class="fw-bold"><?= number_format((float) $inventoryItem['tax_rate'], 2) ?>%</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-body-tertiary">Discount:</p>
                            <p class="fw-bold"><?= number_format((float) $inventoryItem['discount_percentage'], 2) ?>%
                            </p>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="edit_inventory_item.php?inventoryItemId=<?= $inventoryItemId ?>"
                                class="btn btn-primary"><span class="fas fa-edit me-2"></span>Edit Item Details</a>
                            <a href="manage-product-images.php?inventoryItemId=<?= $inventoryItemId ?>"
                                class="btn btn-outline-primary"><span class="fas fa-images me-2"></span>Manage
                                Images</a>
                            <a href="manage-sku.php?inventory_item_id=<?= $inventoryItemId ?>"
                                class="btn btn-outline-secondary"><span class="fas fa-tags me-2"></span>Manage SKU
                                Properties</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">SKU Properties</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($skuData)): ?>
                                <table class="table table-bordered fs-9">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Property</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($skuData as $key => $value): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(ucfirst($key)) ?></td>
                                                <td>
                                                    <?php if ($key === 'color' && filter_var($value, FILTER_VALIDATE_URL) === false): ?>
                                                        <div
                                                            style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($value) ?>; border: 1px solid #ccc; display: inline-block; vertical-align: middle; margin-right: 8px;">
                                                        </div>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($value) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-body-tertiary">No SKU properties have been defined for this item.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Variants Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Other Variants of this Product</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($otherVariants)): ?>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($otherVariants as $variantId => $variant): ?>
                                        <div class="text-center me-3 mb-3">
                                            <a href="view-single-inventory-item.php?inventoryItemId=<?= $variantId ?>">
                                                <img src="../<?= htmlspecialchars($variant['image_path'] ?? 'assets/img/products/default-product.png') ?>"
                                                    alt="Variant Image" class="img-thumbnail"
                                                    style="width: 80px; height: 80px; object-fit: cover;">
                                            </a>
                                            <div class="fs-9 mt-1">
                                                <span><?= htmlspecialchars($variant['sku']['color'] ?? 'N/A') ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-body-tertiary">No other variants found for this product.</p>
                            <?php endif; ?>
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