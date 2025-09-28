<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productId = isset($_GET['productId']) ? (int) $_GET['productId'] : 0;

if (!$productId) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'No Product ID was provided.'];
    header("Location: view-all-products.php");
    exit();
}

$productItemObj = new ProductItem($pdo);
$product = $productItemObj->getProductById($productId);

if (!$product) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => "Product with ID {$productId} could not be found."];
    header("Location: view-all-products.php");
    exit();
}

// Fetch existing sizes for suggestions
$stmt = $pdo->query("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(sku, '$.size')) AS size FROM inventoryitem WHERE JSON_UNQUOTE(JSON_EXTRACT(sku, '$.size')) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(sku, '$.size')) != '' ORDER BY size ASC");
$existing_sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);


$message = '';
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    $message = '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['text']) . '</div>';
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Add Variants to <?= htmlspecialchars($product['product_name']) ?></title>
    <?php include 'admin-header.php'; ?>
    <style>
        .variant-group {
            position: relative;
        }

        .remove-variant-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                    <li class="breadcrumb-item"><a
                            href="view-single-product.php?id=<?= $productId ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Add Variants</li>
                </ol>
            </nav>

            <?= $message ?>

            <div class="mb-5">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0">Add Variants to: <?= htmlspecialchars($product['product_name']) ?></h2>
                        <p class="text-body-tertiary">Add one or more new variants (inventory items) to this product.
                        </p>
                    </div>
                </div>

                <form action="process-add-inventory.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">

                    <div id="variants-container">
                        <!-- Variant groups will be added here by JavaScript -->
                    </div>

                    <div class="mt-3">
                        <button type="button" id="add-variant-btn" class="btn btn-phoenix-secondary me-2">
                            <span class="fas fa-plus me-2"></span>Add Another Variant
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="fas fa-save me-2"></span>Save All Variants
                        </button>
                    </div>
                </form>
            </div>

            <!-- Datalist for size suggestions -->
            <datalist id="size-suggestions">
                <?php foreach ($existing_sizes as $size): ?>
                    <option value="<?= htmlspecialchars($size) ?>">
                    <?php endforeach; ?>
            </datalist>

            <!-- Template for a variant group (hidden) -->
            <template id="variant-template">
                <div class="variant-group card mb-3">
                    <div class="card-body">
                        <button type="button" class="btn-close float-end remove-variant-btn" aria-label="Close"
                            title="Remove this variant"></button>
                        <h5 class="card-title mb-3">New Variant</h5>
                        <div class="row gx-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="description___INDEX__">Variant Description*</label>
                                <input type="text" name="variants[__INDEX__][description]" id="description___INDEX__"
                                    class="form-control" placeholder="e.g., Red, Large" required>
                                <small class="form-text">This is the main name for the variant, like "Blue Cotton
                                    T-Shirt".</small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="cost___INDEX__">Cost Price*</label>
                                <input type="number" step="0.01" name="variants[__INDEX__][cost]" id="cost___INDEX__"
                                    class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="price___INDEX__">Selling Price*</label>
                                <input type="number" step="0.01" name="variants[__INDEX__][price]" id="price___INDEX__"
                                    class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="quantity___INDEX__">Quantity*</label>
                                <input type="number" name="variants[__INDEX__][quantity]" id="quantity___INDEX__"
                                    class="form-control" required>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="discount_percentage___INDEX__">Discount (%)</label>
                                <input type="number" step="0.01" name="variants[__INDEX__][discount_percentage]"
                                    id="discount_percentage___INDEX__" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="barcode___INDEX__">SKU Code / Barcode</label>
                                <input type="text" name="variants[__INDEX__][barcode]" id="barcode___INDEX__"
                                    class="form-control">
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="tax_rate___INDEX__">Tax Rate (%)</label>
                                <input type="number" step="0.01" name="variants[__INDEX__][tax_rate]"
                                    id="tax_rate___INDEX__" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="status___INDEX__">Status</label>
                                <select name="variants[__INDEX__][status]" id="status___INDEX__" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <p class="fw-bold fs-8 text-body-tertiary">PROPERTIES (for filtering)</p>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="form-label" for="color_text___INDEX__">Color</label>
                                <div class="d-flex align-items-center">
                                    <input class="form-control form-control-color me-2" id="color_picker___INDEX__"
                                        type="color" value="#5e6e82" title="Choose a color"
                                        oninput="this.nextElementSibling.value = this.value">
                                    <input class="form-control" id="color_text___INDEX__" type="text"
                                        name="variants[__INDEX__][sku][color]" placeholder="e.g., Red or #FF0000"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="form-label" for="size___INDEX__">Size</label>
                                <input type="text" name="variants[__INDEX__][sku][size]" id="size___INDEX__"
                                    class="form-control" placeholder="e.g., Large, 42, 10.5" list="size-suggestions">
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="form-label" for="expiry_date___INDEX__">Expiry Date</label>
                                <input type="date" name="variants[__INDEX__][sku][expiry_date]"
                                    id="expiry_date___INDEX__" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="images___INDEX__">Images for this variant
                                    (Thumbnail)</label>
                                <input type="file" name="variants[__INDEX__][images][]" id="images___INDEX__"
                                    class="form-control" multiple accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <?php include 'includes/admin_footer.php'; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const variantsContainer = document.getElementById('variants-container');
            const addVariantBtn = document.getElementById('add-variant-btn');
            const variantTemplate = document.getElementById('variant-template');
            let variantIndex = 0;

            function addVariant() {
                const templateContent = variantTemplate.innerHTML.replace(/__INDEX__/g, variantIndex);
                const newVariantContainer = document.createElement('div');
                newVariantContainer.innerHTML = templateContent;
                const newVariantElement = newVariantContainer.firstElementChild;
                variantsContainer.appendChild(newVariantElement);
                variantIndex++;
            }

            addVariantBtn.addEventListener('click', addVariant);

            variantsContainer.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('remove-variant-btn')) {
                    e.target.closest('.variant-group').remove();
                }
            });

            // Add the first variant group automatically on page load
            addVariant();
        });
    </script>
</body>

</html>