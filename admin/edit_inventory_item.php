<?php
require_once "../includes.php";
require_once __DIR__ . '/../class/Variation.php';
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/InventoryItem.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$inventoryItemId = isset($_GET['inventoryItemId']) ? (int) $_GET['inventoryItemId'] : 0;
if (!$inventoryItemId) {
    header("Location: products.php");
    exit();
}

$variationObj = new Variation($pdo);
$productItemObj = new ProductItem($pdo);
$inventoryItemObj = new InventoryItem($pdo);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $cost = (float) ($_POST['cost'] ?? 0.0);
    $price = (float) ($_POST['price'] ?? 0.0);
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_percentage = (float) ($_POST['discount_percentage'] ?? 0.0);
    $status = isset($_POST['status']) ? 1 : 0; // Convert checkbox to 1/0
    $is_on_discount = isset($_POST['is_on_discount']) ? 1 : 0; // Convert checkbox to 1/0
    $tax_rate = (float) ($_POST['tax_rate'] ?? 0.0);
    $barcode = trim($_POST['barcode'] ?? '');

    if (empty($sku) || empty($color)) {
        $message = '<div class="alert alert-danger">SKU and Color are required.</div>';
    } else {
        $data = [
            'inventory_item_id' => $inventoryItemId,
            'sku' => $sku,
            'quantity' => $quantity,
            'cost' => $cost,
            'price' => $price,
            'color' => $color,
            'size' => $size,
            'description' => $description,
            'discount_percentage' => $discount_percentage,
            'status' => $status,
            'is_on_discount' => $is_on_discount,
            'tax_rate' => $tax_rate,
            'barcode' => $barcode,
        ];

        if ($variationObj->updateVariant($data)) {
            $message = '<div class="alert alert-success">Inventory item updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to update inventory item. Please try again.</div>';
        }
    }
}

$inventoryItem = $variationObj->getVariantById($inventoryItemId);
if (!$inventoryItem) {
    echo "<h3>Inventory item not found.</h3>";
    exit();
}

$product = $productItemObj->getProductById($inventoryItem['productItemID']);

// Fetch images for the inventory item
$images = $inventoryItemObj->getImagesForInventoryItem($inventoryItemId);

// Calculate discounted price
$discountedPrice = 0;
if (!empty($inventoryItem['is_on_discount']) && !empty($inventoryItem['discount_percentage'])) {
    $discountedPrice = $inventoryItem['price'] * (1 - ($inventoryItem['discount_percentage'] / 100));
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Edit Inventory Item - <?= htmlspecialchars($product['product_name'] ?? 'Item') ?></title>
    <?php include 'admin-header.php'; ?>
    <style>
        .inventory-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .inventory-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: .25rem;
            border: 1px solid var(--phoenix-border-color);
        }

        .inventory-images {
            cursor: pointer;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .price-display {
            font-size: 1.1em;
            font-weight: bold;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
        }

        .discounted-price {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content mt-5">
            <div class="container-small">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-4">
                            <h3>Edit Inventory Item</h3>
                            <p class="text-body-tertiary">For product: <a
                                    href="view-single-product.php?id=<?= (int) ($product['product_id'] ?? 0) ?>"><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></a>
                            </p>
                        </div>

                        <?= $message ?>

                        <!-- Display Inventory Item Images -->
                        <h4>Inventory Item Images</h4>
                        <div class="inventory-images">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $image): ?>
                                    <img src="../<?= htmlspecialchars($image['image_path']) ?>"
                                        alt="<?= htmlspecialchars($image['image_name']) ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No images available for this item.</p>
                            <?php endif; ?>
                        </div>
                        <a href="manage-product-images.php?inventoryItemId=<?= $inventoryItemId ?>"
                            class="btn btn-phoenix-primary btn-sm">Edit Images</a>
                        <br /><br />

                        <form method="POST" action="edit_inventory_item.php?inventoryItemId=<?= $inventoryItemId ?>">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- SKU -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="sku">SKU</label>
                                            <div class="input-group">
                                                <input class="form-control" id="sku" name="sku" type="text"
                                                    value="<?= htmlspecialchars($inventoryItem['sku'] ?? '') ?>"
                                                    required="required" />
                                                <a href="manage-sku-properties.php?inventoryItemId=<?= $inventoryItemId ?>"
                                                    class="btn btn-outline-secondary" target="_blank">
                                                    Manage Properties
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Barcode -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="barcode">Barcode</label>
                                            <input class="form-control" id="barcode" name="barcode" type="text"
                                                value="<?= htmlspecialchars($inventoryItem['barcode'] ?? '') ?>" />
                                        </div>

                                        <!-- Quantity -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="quantity">Quantity</label>
                                            <input class="form-control" id="quantity" name="quantity" type="number"
                                                value="<?= (int) ($inventoryItem['quantity'] ?? 0) ?>"
                                                required="required" />
                                        </div>

                                        <!-- Status Toggle -->
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <div class="d-flex align-items-center">
                                                <label class="toggle-switch me-3">
                                                    <input type="checkbox" id="status" name="status"
                                                        <?= !empty($inventoryItem['status']) ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                                <span
                                                    id="status-text"><?= !empty($inventoryItem['status']) ? 'Active' : 'Inactive' ?></span>
                                            </div>
                                        </div>

                                        <!-- Cost Price -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="cost">Cost Price ($)</label>
                                            <input class="form-control" id="cost" name="cost" type="number" step="0.01"
                                                value="<?= htmlspecialchars($inventoryItem['cost'] ?? '0.00') ?>"
                                                required="required" />
                                        </div>

                                        <!-- Selling Price -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="price">Selling Price ($)</label>
                                            <input class="form-control" id="price" name="price" type="number"
                                                step="0.01"
                                                value="<?= htmlspecialchars($inventoryItem['price'] ?? '0.00') ?>"
                                                required="required" />
                                        </div>

                                        <!-- Discount Toggle -->
                                        <div class="col-md-6">
                                            <label class="form-label">Discount</label>
                                            <div class="d-flex align-items-center">
                                                <label class="toggle-switch me-3">
                                                    <input type="checkbox" id="is_on_discount" name="is_on_discount"
                                                        <?= !empty($inventoryItem['is_on_discount']) ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                                <span
                                                    id="discount-text"><?= !empty($inventoryItem['is_on_discount']) ? 'On Discount' : 'No Discount' ?></span>
                                            </div>
                                        </div>

                                        <!-- Discount Percentage -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="discount_percentage">Discount Percentage
                                                (%)</label>
                                            <input class="form-control" id="discount_percentage"
                                                name="discount_percentage" type="number" step="0.01" min="0" max="100"
                                                value="<?= htmlspecialchars($inventoryItem['discount_percentage'] ?? '0.00') ?>" />
                                        </div>

                                        <!-- Price Display -->
                                        <div class="col-md-12">
                                            <div class="price-display" id="price-display">
                                                <?php if (!empty($inventoryItem['is_on_discount']) && !empty($inventoryItem['discount_percentage'])): ?>
                                                    <span
                                                        class="original-price">$<?= number_format($inventoryItem['price'], 2) ?></span>
                                                    <span
                                                        class="discounted-price">$<?= number_format($discountedPrice, 2) ?></span>
                                                    <small class="text-muted">(<?= $inventoryItem['discount_percentage'] ?>%
                                                        off)</small>
                                                <?php else: ?>
                                                    <span>$<?= number_format($inventoryItem['price'], 2) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Tax Rate -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="tax_rate">Tax Rate (%)</label>
                                            <input class="form-control" id="tax_rate" name="tax_rate" type="number"
                                                step="0.01" min="0" max="100"
                                                value="<?= htmlspecialchars($inventoryItem['tax_rate'] ?? '0.00') ?>" />
                                        </div>

                                        <!-- Color -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="color">Color</label>
                                            <div class="d-flex align-items-center">
                                                <input class="form-control me-2" id="color" name="color" type="color"
                                                    value="<?= htmlspecialchars($inventoryItem['color'] ?? '#000000') ?>" />
                                                <input class="form-control form-control-sm" type="text"
                                                    placeholder="or color code"
                                                    value="<?= htmlspecialchars($inventoryItem['color'] ?? '') ?>"
                                                    oninput="this.previousElementSibling.value = this.value" />
                                            </div>
                                        </div>

                                        <!-- Size -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="size">Size</label>
                                            <input class="form-control" id="size" name="size" type="text"
                                                value="<?= htmlspecialchars($inventoryItem['size'] ?? '') ?>" />
                                        </div>

                                        <!-- Description -->
                                        <div class="col-md-12">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description"
                                                rows="3"><?= htmlspecialchars($inventoryItem['description'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="view-single-product.php?id=<?= (int) ($product['product_id'] ?? 0) ?>"
                                        class="btn btn-phoenix-secondary">Cancel</a>
                                    <button class="btn btn-primary" type="submit">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/lodash/lodash.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/feather-icons/feather.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dayjs/dayjs.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const imageContainer = document.querySelector('.inventory-images');
            if (imageContainer) {
                imageContainer.addEventListener('dblclick', function () {
                    window.location.href = 'manage-product-images.php?inventoryItemId=<?= $inventoryItemId ?>';
                });
            }

            // Status toggle functionality
            const statusToggle = document.getElementById('status');
            const statusText = document.getElementById('status-text');

            statusToggle.addEventListener('change', function () {
                statusText.textContent = this.checked ? 'Active' : 'Inactive';
            });

            // Discount toggle functionality
            const discountToggle = document.getElementById('is_on_discount');
            const discountText = document.getElementById('discount-text');
            const discountPercentage = document.getElementById('discount_percentage');

            discountToggle.addEventListener('change', function () {
                discountText.textContent = this.checked ? 'On Discount' : 'No Discount';
                if (!this.checked) {
                    discountPercentage.value = '0.00';
                }
                updatePriceDisplay();
            });

            // Price and discount calculation
            const priceInput = document.getElementById('price');

            priceInput.addEventListener('input', updatePriceDisplay);
            discountPercentage.addEventListener('input', updatePriceDisplay);

            function updatePriceDisplay() {
                const price = parseFloat(priceInput.value) || 0;
                const discount = parseFloat(discountPercentage.value) || 0;
                const isOnDiscount = discountToggle.checked;
                const priceDisplay = document.getElementById('price-display');

                if (isOnDiscount && discount > 0) {
                    const discountedPrice = price * (1 - (discount / 100));
                    priceDisplay.innerHTML = `
                        <span class="original-price">$${price.toFixed(2)}</span>
                        <span class="discounted-price">$${discountedPrice.toFixed(2)}</span>
                        <small class="text-muted">(${discount}% off)</small>
                    `;
                } else {
                    priceDisplay.innerHTML = `<span>$${price.toFixed(2)}</span>`;
                }
            }
        });
    </script>
</body>

</html>