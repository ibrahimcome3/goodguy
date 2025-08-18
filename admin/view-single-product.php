<?php
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/Variation.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$productId) {
    die("Product not found.");
}

$productItemObj = new ProductItem($pdo);
$categoryObj = new Category($pdo);
$vendorObj = new Vendor($pdo);
$variantObj = new Variation($pdo);

// Get product details
$product = $productItemObj->getProductById($productId);
if (!$product) {
    die("Product not found.");
}



// Get all variants (inventory items) for this product
$allVariants = $variantObj->getVariantsByProductId($productId);

// Get vendor and category info
$vendor = $vendorObj->getVendorById($product['vendor_id']);
$category = $categoryObj->getCategoryById($product['category']);

// Calculate total stock and price range from variants
$totalStock = 0;
$priceRange = $productItemObj->getPriceRange($productId);
foreach ($allVariants as $variant) {
    $totalStock += $variant['quantity'];
}

?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>View Product - <?= htmlspecialchars($product['product_name']) ?></title>
    <?php include 'admin-header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <style>
        .product-color-variants .rounded-1 {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .product-color-variants .rounded-1.active {
            border-color: var(--phoenix-primary) !important;
            box-shadow: 0 0 0 2px var(--phoenix-primary);
        }

        .variant-image {
            width: 38px;
            height: 38px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <section class="py-0">
                <div class="container-small">
                    <nav class="mb-3" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                            <?php if ($category): ?>
                                <li class="breadcrumb-item"><a
                                        href="view-all-products.php?category=<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </li>
                        </ol>
                    </nav>
                    <div class="row g-5 mb-5 mb-lg-8" data-product-details="data-product-details">
                        <div class="col-12 col-lg-6">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-2 col-lg-12 col-xl-2">
                                    <div class="swiper-products-thumb swiper theme-slider overflow-visible"
                                        id="swiper-products-thumb">
                                        <div class="swiper-wrapper" aria-live="polite">
                                            <!-- Thumbnails will be populated by JS -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-10 col-lg-12 col-xl-10">
                                    <div
                                        class="d-flex align-items-center border border-translucent rounded-3 text-center p-5 h-100">
                                        <div class="swiper theme-slider" data-thumb-target="swiper-products-thumb"
                                            data-products-swiper='{"slidesPerView":1,"spaceBetween":16,"thumbsEl":".swiper-products-thumb"}'>
                                            <div class="swiper-wrapper" aria-live="polite">
                                                <!-- Main images will be populated by JS -->
                                            </div>
                                            <div class="swiper-button-next"></div>
                                            <div class="swiper-button-prev"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="d-flex flex-column justify-content-between h-100">
                                <div>
                                    <h3 class="mb-3 lh-sm"><?= htmlspecialchars($product['product_name']) ?></h3>
                                    <div class="d-flex flex-wrap align-items-center">
                                        <h1 class="me-3"><?= htmlspecialchars($priceRange) ?></h1>
                                    </div>
                                    <p class="text-success fw-semibold fs-7 mb-2">In stock (<?= $totalStock ?>
                                        available)</p>
                                    <div class="mb-3">
                                        <p class="fw-semibold mb-2 text-body">Status:</p>
                                        <select class="form-select w-auto" id="productStatus"
                                            data-product-id="<?= $productId ?>">
                                            <option value="active" <?= ($product['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= ($product['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="draft" <?= ($product['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                        </select>
                                        <span id="status-feedback" class="ms-2"></span>
                                    </div>
                                </div>
                                <div>
                                    <?php if (!empty($allVariants)): ?>
                                        <div class="mb-3">
                                            <p class="fw-semibold mb-2">Variants: <span class="text-body-emphasis"
                                                    data-product-color></span></p>
                                            <div class="d-flex product-color-variants" data-product-color-variants>
                                                <?php foreach ($allVariants as $i => $v): ?>
                                                    <div class="rounded-1 border border-translucent me-2 <?= $i === 0 ? 'active' : '' ?>"
                                                        data-inventory-item-id="<?= (int) $v['InventoryItemID'] ?>"
                                                        data-variant-color="<?= htmlspecialchars($v['color']) ?>"
                                                        data-variant-images='<?= json_encode(array_map(fn($path) => "../{$path}", $v['images']), JSON_UNESCAPED_SLASHES) ?>'>
                                                        <img class="variant-image"
                                                            src="../<?= htmlspecialchars($v['thumbnail']) ?>"
                                                            alt="<?= htmlspecialchars($v['color']) ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-body-tertiary">This product has no variants yet.</p>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center mt-3">
                                        <a href="edit-product.php?id=<?= $productId ?>"
                                            class="btn btn-phoenix-primary px-3 me-2">Edit Product</a>
                                        <a href="add-inventory-item.php?productId=<?= $productId ?>"
                                            class="btn btn-phoenix-secondary px-3 me-2">Add New Variant</a>
                                        <a href="#" class="btn btn-phoenix-secondary px-3"
                                            id="manage-images-link">Manage Images</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include 'admin-footer.php'; ?>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const variantContainer = document.querySelector('[data-product-color-variants]');
            const manageImagesLink = document.getElementById('manage-images-link');
            const colorDisplay = document.querySelector('[data-product-color]');
            const mainSwiperWrapper = document.querySelector('.swiper.theme-slider .swiper-wrapper');
            const thumbSwiperWrapper = document.querySelector('.swiper-products-thumb .swiper-wrapper');

            function updateVariantDetails(selectedVariant) {
                if (!selectedVariant) return;

                // Update active state
                variantContainer.querySelectorAll('.rounded-1').forEach(el => el.classList.remove('active'));
                selectedVariant.classList.add('active');

                const invId = selectedVariant.dataset.inventoryItemId;
                const color = selectedVariant.dataset.variantColor;
                const images = JSON.parse(selectedVariant.dataset.variantImages);

                // Update "Manage Images" link
                manageImagesLink.href = `manage-product-images.php?inventoryItemId=${invId}`;

                // Update color display
                colorDisplay.textContent = color;

                // Update image gallery
                mainSwiperWrapper.innerHTML = '';
                thumbSwiperWrapper.innerHTML = '';

                if (images.length > 0) {
                    images.forEach(imgPath => {
                        mainSwiperWrapper.innerHTML += `<div class="swiper-slide"><img class="img-fluid" src="${imgPath}" alt="${color}"></div>`;
                        thumbSwiperWrapper.innerHTML += `<div class="swiper-slide"><img class="img-fluid" src="${imgPath}" alt="${color}"></div>`;
                    });
                } else {
                    const placeholder = '../assets/img/products/default-product.png';
                    mainSwiperWrapper.innerHTML += `<div class="swiper-slide"><img class="img-fluid" src="${placeholder}" alt="No Image"></div>`;
                }

                // Re-initialize or update swiper instances if Phoenix JS doesn't do it automatically
                if (window.phoenix.swiper) {
                    const mainSwiperEl = document.querySelector('.swiper.theme-slider');
                    const thumbSwiperEl = document.querySelector('.swiper-products-thumb');
                    if (mainSwiperEl.swiper) mainSwiperEl.swiper.update();
                    if (thumbSwiperEl.swiper) thumbSwiperEl.swiper.update();
                }
            }

            // Handle variant selection
            variantContainer.addEventListener('click', function (e) {
                const selectedVariant = e.target.closest('.rounded-1');
                if (selectedVariant) {
                    updateVariantDetails(selectedVariant);
                }
            });

            // Handle double-click to open variant details
            variantContainer.addEventListener('dblclick', function (e) {
                const selectedVariant = e.target.closest('.rounded-1');
                if (selectedVariant) {
                    const invId = selectedVariant.dataset.inventoryItemId;
                    window.open(`view-single-inventory-item.php?inventoryItemId=${invId}`, '_blank');
                }
            });

            // Initial setup for the first variant
            const firstVariant = variantContainer.querySelector('.rounded-1');
            if (firstVariant) {
                updateVariantDetails(firstVariant);
            } else {
                manageImagesLink.style.display = 'none';
            }

            // AJAX for status update
            const statusSelect = document.getElementById('productStatus');
            statusSelect.addEventListener('change', function () {
                const productId = this.dataset.productId;
                const status = this.value;
                const feedbackEl = document.getElementById('status-feedback');
                feedbackEl.textContent = 'Saving...';
                feedbackEl.className = 'ms-2 text-body-tertiary';

                fetch('ajax/update_product_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: productId, status: status })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            feedbackEl.textContent = 'Saved!';
                            feedbackEl.className = 'ms-2 text-success';
                        } else {
                            feedbackEl.textContent = 'Error!';
                            feedbackEl.className = 'ms-2 text-danger';
                            console.error(data.error);
                        }
                        setTimeout(() => { feedbackEl.textContent = ''; }, 2000);
                    })
                    .catch(error => {
                        feedbackEl.textContent = 'Error!';
                        feedbackEl.className = 'ms-2 text-danger';
                        console.error('Fetch error:', error);
                    });
            });
        });
    </script>
</body>

</html>