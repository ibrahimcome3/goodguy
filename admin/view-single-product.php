<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/Variation.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Data Fetching ---
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pageData = [
    'product' => null,
    'vendor' => null,
    'categories' => [],
    'baseImages' => [],
    'variants' => [],
    'totalStock' => 0,
    'priceRange' => '',
    'initialGallery' => [
        'images' => [],
        'alt' => 'Product Image'
    ],
    'jsVariantData' => [],
    'errorMessage' => null,
];

if (!$productId) {
    $pageData['errorMessage'] = "No Product ID was provided.";
} else {
    try {
        $productItemObj = new ProductItem($pdo);
        $product = $productItemObj->getProductById($productId);

        if ($product) {
            $pageData['product'] = $product;

            $categoryObj = new Category($pdo);
            $vendorObj = new Vendor($pdo);
            $variantObj = new Variation($pdo);

            // Fetch all related data
            $pageData['vendor'] = $vendorObj->getVendorById($product['vendor_id']);
            $pageData['categories'] = $categoryObj->getCategoriesByProductId($productId);
            $pageData['baseImages'] = $productItemObj->getImagesByProductId($productId);
            var_dump($pageData['baseImages']);
            //exit;
            $pageData['variants'] = $variantObj->getVariantsByProductId($productId);
            $pageData['priceRange'] = $productItemObj->getPriceRange($productId);

            // Process data
            $pageData['totalStock'] = array_sum(array_column($pageData['variants'], 'quantity'));

            // Prepare data for JS and initial gallery view
            $pageData['initialGallery']['alt'] = $product['product_name'];
            $baseImagePaths = array_map(fn($img) => '../' . $img['image_path'], $pageData['baseImages']);

            if (!empty($pageData['variants'])) {
                // Set initial gallery to first variant's images
                $firstVariant = $pageData['variants'][0];
                $firstVariantImages = array_map(fn($path) => "../{$path}", $firstVariant['images']);
                $pageData['initialGallery']['images'] = !empty($firstVariantImages) ? $firstVariantImages : $baseImagePaths;
                $pageData['initialGallery']['alt'] = $firstVariant['color'];

                // Prepare data for JS to handle variant switching
                foreach ($pageData['variants'] as $variant) {
                    $variantImages = array_map(fn($path) => "../{$path}", $variant['images']);
                    $pageData['jsVariantData'][$variant['InventoryItemID']] = [
                        'color' => $variant['color'],
                        'images' => !empty($variantImages) ? $variantImages : $baseImagePaths,
                    ];
                }
            } else {
                // No variants, use base product images for gallery
                $pageData['initialGallery']['images'] = $baseImagePaths;
            }

            // Add default placeholder if no images are found at all
            if (empty($pageData['initialGallery']['images'])) {
                $pageData['initialGallery']['images'][] = '../assets/img/products/default-product.png';
            }

        } else {
            $pageData['errorMessage'] = "Product with ID " . htmlspecialchars($productId) . " could not be found.";
        }
    } catch (Exception $e) {
        error_log("Error fetching product details for ID $productId: " . $e->getMessage());
        $pageData['errorMessage'] = "An unexpected error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title><?= $pageData['product'] ? htmlspecialchars($pageData['product']['product_name']) : 'Product Not Found' ?>
    </title>
    <?php include 'admin-header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <style>
        .variant-thumbnail {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
        }

        .variant-thumbnail.active {
            border-color: var(--phoenix-primary) !important;
            box-shadow: 0 0 0 2px var(--phoenix-primary);
        }

        .variant-image {
            width: 42px;
            height: 42px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <?php if ($pageData['errorMessage']): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($pageData['errorMessage']) ?></div>
            <?php elseif ($pageData['product']): ?>
                <div id="product-details-page"
                    data-variants-data='<?= json_encode($pageData['jsVariantData'], JSON_UNESCAPED_SLASHES) ?>'>
                    <section class="py-0">
                        <div class="container-small">
                            <nav class="mb-3" aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                                    <?php foreach ($pageData['categories'] as $cat): ?>
                                        <li class="breadcrumb-item">
                                            <a
                                                href="view-all-products.php?category=<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?= htmlspecialchars($pageData['product']['product_name']) ?>
                                    </li>
                                </ol>
                            </nav>
                            <div class="row g-5 mb-5 mb-lg-8" data-product-details="data-product-details">
                                <div class="col-12 col-lg-6">
                                    <div class="d-flex align-items-center border border-translucent rounded-3 text-center p-5 h-100"
                                        id="main-product-slider-container">
                                        <div class="swiper theme-slider" id="main-product-swiper">
                                            <div class="swiper-wrapper" aria-live="polite">
                                                <?php foreach ($pageData['initialGallery']['images'] as $imagePath): ?>

                                                    <div class="swiper-slide">
                                                        <a href="<?= htmlspecialchars($imagePath) ?>"
                                                            data-gallery="product-gallery"
                                                            data-glightbox="title: <?= htmlspecialchars($pageData['initialGallery']['alt']) ?>">
                                                            <img class="img-fluid" src="<?= htmlspecialchars($imagePath) ?>"
                                                                alt="<?= htmlspecialchars($pageData['initialGallery']['alt']) ?>" />
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="swiper-button-next"></div>
                                            <div class="swiper-button-prev"></div>
                                            <div class="swiper-pagination"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="d-flex flex-column justify-content-between h-100">
                                        <div>
                                            <h3 class="mb-3 lh-sm">
                                                <?= htmlspecialchars($pageData['product']['product_name']) ?>
                                            </h3>
                                            <div class="d-flex flex-wrap align-items-center">
                                                <h1 class="me-3"><?= htmlspecialchars($pageData['priceRange']) ?></h1>
                                            </div>
                                            <p class="text-success fw-semibold fs-7 mb-2">In stock
                                                (<?= $pageData['totalStock'] ?>
                                                available)</p>
                                            <div class="mb-3">
                                                <p class="fw-semibold mb-2 text-body">Status:</p>
                                                <select class="form-select w-auto" id="productStatus"
                                                    data-product-id="<?= $productId ?>">
                                                    <option value="active" <?= ($pageData['product']['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= ($pageData['product']['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                    <option value="draft" <?= ($pageData['product']['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                </select>
                                                <span id="status-feedback" class="ms-2"></span>
                                            </div>
                                            <p class="mb-2">
                                                <?= nl2br(htmlspecialchars($pageData['product']['product_information'])) ?>
                                            </p>
                                        </div>
                                        <div>
                                            <?php if (!empty($pageData['variants'])): ?>
                                                <div class="mb-3">
                                                    <p class="fw-semibold mb-2">Variants: <span class="text-body-emphasis"
                                                            data-product-color><?= htmlspecialchars($pageData['variants'][0]['color']) ?></span>
                                                    </p>
                                                    <div class="d-flex flex-wrap gap-2" id="variant-container">
                                                        <?php foreach ($pageData['variants'] as $i => $v): ?>
                                                            <div class="variant-thumbnail rounded-1 <?= $i === 0 ? 'active' : '' ?>"
                                                                data-inventory-item-id="<?= (int) $v['InventoryItemID'] ?>">
                                                                <img class="variant-image"
                                                                    src="<?= !empty($v['thumbnail']) ? '../' . htmlspecialchars($v['thumbnail']) : '../assets/img/products/default-product.png' ?>"
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
                                                <a href="manage-product-images.php?<?= !empty($pageData['variants']) ? 'inventoryItemId=' . $pageData['variants'][0]['InventoryItemID'] : 'productId=' . $productId ?>"
                                                    class="btn btn-phoenix-secondary px-3" id="manage-images-link">Manage
                                                    Images</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </section>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pageContainer = document.getElementById('product-details-page');
            if (!pageContainer) return;

            // --- State and Data ---
            const variantsData = JSON.parse(pageContainer.dataset.variantsData || '{}');

            // --- DOM Elements ---
            const variantContainer = document.getElementById('variant-container');
            const manageImagesLink = document.getElementById('manage-images-link');
            const colorDisplay = document.querySelector('[data-product-color]');
            const mainSwiperEl = document.getElementById('main-product-swiper');
            const statusSelect = document.getElementById('productStatus');

            // --- Initialize Libraries ---
            let lightbox = GLightbox({
                selector: '[data-gallery="product-gallery"]'
            });

            const mainSwiper = new Swiper(mainSwiperEl, {
                spaceBetween: 16,
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                }
            });

            /**
             * Updates the Swiper gallery with a new set of images.
             * @param {string[]} images - An array of image paths.
             * @param {string} altText - The alt text for the images.
             */
            function updateSwiper(images, altText) {
                if (!mainSwiper) return;

                const mainSwiperWrapper = mainSwiper.el.querySelector('.swiper-wrapper');

                let mainSlidesHTML = '';

                images.forEach(imgPath => {
                    mainSlidesHTML += `<div class="swiper-slide">
                        <a href="${imgPath}" data-gallery="product-gallery" data-glightbox="title: ${altText}">
                            <img class="img-fluid" src="${imgPath}" alt="${altText}">
                        </a>
                    </div>`;
                });

                mainSwiperWrapper.innerHTML = mainSlidesHTML;

                mainSwiper.update();
                mainSwiper.slideTo(0, 0);
                lightbox.reload();
            }

            /**
             * Updates the UI based on the selected variant.
             * @param {HTMLElement} selectedVariantEl - The selected variant thumbnail element.
             */
            function updateVariantDetails(selectedVariantEl) {
                if (!selectedVariantEl) return;

                // Update active state on thumbnails
                variantContainer.querySelectorAll('.variant-thumbnail').forEach(el => el.classList.remove('active'));
                selectedVariantEl.classList.add('active');

                // Get data for the selected variant
                const invId = selectedVariantEl.dataset.inventoryItemId;
                const variantInfo = variantsData[invId];
                if (!variantInfo) return;

                // Update UI elements
                if (colorDisplay) colorDisplay.textContent = variantInfo.color;
                if (manageImagesLink) manageImagesLink.href = `manage-product-images.php?inventoryItemId=${invId}`;

                // Update the image gallery
                updateSwiper(variantInfo.images, variantInfo.color);
            }

            // --- Event Listeners ---
            if (variantContainer) {
                variantContainer.addEventListener('click', (e) => {
                    const selectedVariant = e.target.closest('.variant-thumbnail');
                    if (selectedVariant && !selectedVariant.classList.contains('active')) {
                        updateVariantDetails(selectedVariant);
                    }
                });

                variantContainer.addEventListener('dblclick', (e) => {
                    const selectedVariant = e.target.closest('.variant-thumbnail');
                    if (selectedVariant) {
                        const invId = selectedVariant.dataset.inventoryItemId;
                        window.open(`view-single-inventory-item.php?inventoryItemId=${invId}`, '_blank');
                    }
                });
            }

            if (statusSelect) {
                // AJAX for status update (this logic is good, can be kept as is)
                statusSelect.addEventListener('change', function () {
                    const productId = this.dataset.productId;
                    const status = this.value;
                    const feedbackEl = document.getElementById('status-feedback');

                    this.disabled = true;
                    feedbackEl.innerHTML = 'Saving... <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
                    feedbackEl.className = 'ms-2 text-info';

                    fetch('ajax/update_product_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ productId: productId, status: status })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                feedbackEl.textContent = 'Saved!';
                                feedbackEl.className = 'ms-2 text-success fw-bold';
                            } else {
                                feedbackEl.textContent = 'Error saving!';
                                feedbackEl.className = 'ms-2 text-danger fw-bold';
                                console.error('API Error:', data.error || 'Unknown error');
                            }
                        })
                        .catch(error => {
                            feedbackEl.textContent = 'Request failed!';
                            feedbackEl.className = 'ms-2 text-danger fw-bold';
                            console.error('Fetch error:', error);
                        })
                        .finally(() => {
                            this.disabled = false;
                            setTimeout(() => { feedbackEl.innerHTML = ''; }, 3000);
                        });
                });
            }

            // No initial setup call is needed for `updateVariantDetails` because
            // the correct initial state is now rendered by PHP. The JS only needs
            // to handle the *changes*.
        });
    </script>
</body>

</html>