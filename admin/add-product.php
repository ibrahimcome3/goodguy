<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Tag.php';
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/InventoryItem.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$categoryObj = new Category($pdo);
$vendorObj = new Vendor($pdo);
$tagObj = new Tag($pdo);

$categories = $categoryObj->getAllCategories() ?? [];
$vendors = $vendorObj->getAllVendors() ?? [];
$tags = $tagObj->getAllTags() ?? [];

// Add this line to fetch all collection names
$collections = $pdo->query("SELECT name FROM collections ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$errorMessage = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_categories = $_POST['categories'] ?? []; // Changed from 'category'
    $vendor_id = (int) ($_POST['vendor_id'] ?? 0);
    $collection_name = trim($_POST['collection'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $cost = (float) ($_POST['cost'] ?? 0);
    $qty = (int) ($_POST['quantity'] ?? 0);
    $status = ($_POST['save_action'] ?? '') === 'publish' ? 'active' : 'draft';
    $product_tags = $_POST['tags'] ?? [];

    // --- Enhanced Validation ---
    if ($product_name === '' || empty($product_categories) || $vendor_id <= 0) {
        $errorMessage = "Name, at least one Category, and Vendor are required.";
    } else if (!is_numeric($_POST['price']) || $price < 0) {
        $errorMessage = "A valid, non-negative Price is required.";
    } else if (!is_numeric($_POST['quantity']) || $qty < 0) {
        $errorMessage = "A valid, non-negative Initial Quantity is required.";
    } else if (isset($_POST['cost']) && $_POST['cost'] !== '' && !is_numeric($_POST['cost'])) {
        $errorMessage = "If provided, Cost must be a valid number.";
    }
    // --- End Enhanced Validation ---
    else {
        try {
            // The function definitions for ensureDirectoryExists and resizeImage have been moved to the ProductItem class.
            // They are no longer needed here.

            $pdo->beginTransaction();

            $collection_id = null;
            // If a collection name was provided, get or create it.
            if (!empty($collection_name)) {
                // 1. Check if the collection already exists
                $stmt = $pdo->prepare("SELECT collection_id FROM collections WHERE name = ?");
                $stmt->execute([$collection_name]);
                $collection_id = $stmt->fetchColumn();

                // 2. If it doesn't exist, create it
                if (!$collection_id) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection_name)));
                    $stmt = $pdo->prepare("INSERT INTO collections (name, slug) VALUES (?, ?)");
                    $stmt->execute([$collection_name, $slug]);
                    $collection_id = $pdo->lastInsertId();
                }
            }

            $productItemObj = new ProductItem($pdo);
            // Pass the full categories array to the addProduct method
            $newId = $productItemObj->addProduct([
                'product_name' => $product_name,
                'categories' => $product_categories, // Pass the whole array
                'vendor_id' => $vendor_id,
                'collection_id' => $collection_id,
                'status' => $status,
                'description' => $description
            ]);

            if (!$newId) {
                throw new Exception("Insert product failed. The addProduct method returned false.");
            }

            // Create the first inventory item (variant) for this product
            $stmtInv = $pdo->prepare(
                "INSERT INTO inventoryitem (
                    productItemID, description, price, cost, quantity, sku, status, date_added
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $skuJson = '{}'; // Default empty SKU for a simple product

            $stmtInv->execute([
                $newId,
                $product_name, // Use product name as default variant description
                $price,
                $cost,
                $qty,
                $skuJson,
                $status // Use the same status as the product
            ]);

            $inventoryItemId = $pdo->lastInsertId();
            if (!$inventoryItemId) {
                throw new Exception("Failed to create the initial inventory item for the product.");
            }

            if (!empty($product_tags)) {
                $tagObj->addProductTags($newId, $product_tags);
            }

            // Define the directory paths
            $productDir = realpath(__DIR__ . "/..") . "/products/product-$newId";
            $imageParentDir = $productDir . "/product-$newId-image";
            $resizedImageDir = $imageParentDir . "/resized"; // The deepest path we need

            // Use the new static method from the ProductItem class.
            if (!ProductItem::ensureDirectoryExists($resizedImageDir)) {
                throw new Exception("Failed to create image directory: " . $resizedImageDir);
            }

            if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // Generate a unique filename using a timestamp
                    $fname = time() . '_' . uniqid() . '.' . $ext;

                    // Save the original uploaded file to the parent directory
                    $dest = $imageParentDir . "/$fname";
                    if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $dest)) {

                        // Use the new static method from the ProductItem class.
                        $resizedDest = $resizedImageDir . "/$fname";
                        ProductItem::resizeImage($dest, $resizedDest, 600); // Create a 600x600 version

                        $relPath = "products/product-$newId/product-$newId-image/resized/$fname";

                        // Insert into the correct table for inventory item images
                        $stmtImg = $pdo->prepare("INSERT INTO inventory_item_image (inventory_item_id, image_name, image_path, is_primary, sort_order, created_at) VALUES (?, ?, ?, 1, 0, NOW())");
                        $stmtImg->execute([$inventoryItemId, $fname, $relPath]);

                        // Update the product's primary image with the FILENAME only for consistency
                        $pdo->prepare("UPDATE productitem SET primary_image = ? WHERE productID = ?")->execute([$fname, $newId]);
                    }
                }
            }

            if (!empty($_FILES['gallery_images']['name'][0])) {
                $stmtGal = $pdo->prepare("INSERT INTO inventory_item_image (inventory_item_id, image_name, image_path, is_primary, sort_order, created_at) VALUES (?, ?, ?, 0, ?, NOW())");
                $sortOrder = 1;
                foreach ($_FILES['gallery_images']['name'] as $i => $n) {
                    if ($_FILES['gallery_images']['error'][$i] === 0) {
                        $ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            continue;

                        // Generate a unique filename for each gallery image
                        $gName = time() . '_' . uniqid() . '.' . $ext;

                        // Save the original gallery image to the parent directory
                        $dst = $imageParentDir . "/$gName";
                        if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $dst)) {

                            // Create a resized version in the 'resized' subfolder
                            $resizedDst = $resizedImageDir . "/$gName";
                            ProductItem::resizeImage($dst, $resizedDst, 600);

                            $relPath = "products/product-$newId/product-$newId-image/resized/$gName";
                            $stmtGal->execute([$inventoryItemId, $gName, $relPath, $sortOrder++]);
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: view-single-product.php?id=$newId&created=1");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            error_log("Add product error: " . $e->getMessage());
            $errorMessage = "Failed to save product. Check logs for details.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Add Product</title>
    <?php include 'admin-header.php'; ?>
    <link rel="stylesheet" href="phoenix-v1.20.1/vendors/choices/choices.min.css">
    <style>
        .dropzone-container {
            position: relative;
            border: 2px dashed var(--phoenix-border-color);
            border-radius: .5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s ease-in-out;
        }

        .dropzone-container:hover,
        .dropzone-container.dz-drag-hover {
            border-color: var(--phoenix-primary);
        }

        .image-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: .375rem;
            border: 1px solid var(--phoenix-border-color);
        }

        .image-preview-item .remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background-color: var(--phoenix-danger);
            color: white;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-xl-11 d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                                <li class="breadcrumb-item active">Add Product</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0"><span class="fa-solid fa-plus-circle me-2"></span>New Product</h2>
                        <p class="text-body-tertiary fs-7 mb-0">Create and publish a product</p>
                    </div>
                    <a href="view-all-products.php" class="btn btn-phoenix-secondary"><span
                            class="fa-solid fa-arrow-left me-1"></span>Back</a>
                </div>
            </div>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger"><span
                        class="fa-solid fa-circle-exclamation me-2"></span><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-12 col-xl-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3"><span class="fa-solid fa-circle-info me-2"></span>Basic Info</h5>
                                <div class="mb-3"><label class="form-label"><span
                                            class="fa-solid fa-tag me-2 text-body-tertiary"></span>Product
                                        Name</label><input type="text" name="product_name" class="form-control" required
                                        value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"></div>
                                <div class="mb-3"><label class="form-label"><span
                                            class="fa-solid fa-align-left me-2 text-body-tertiary"></span>Description</label><textarea
                                        class="form-control" rows="6"
                                        name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>
                                <div class="row">

                                    <div class="col-md-4 mb-3"><label class="form-label"><span
                                                class="fa-solid fa-dollar-sign me-2 text-body-tertiary"></span>Price</label><input
                                            type="number" step="0.01" name="price" class="form-control" min="0"
                                            value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label"><span
                                                class="fa-solid fa-money-bill-wave me-2 text-body-tertiary"></span>Cost</label><input
                                            type="number" step="0.01" name="cost" class="form-control" min="0"
                                            value="<?= htmlspecialchars($_POST['cost'] ?? '') ?>"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label"><span
                                                class="fa-solid fa-boxes-stacked me-2 text-body-tertiary"></span>Initial
                                            Quantity</label><input type="number" name="quantity" class="form-control"
                                            min="0" value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>"></div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label" for="collection-input">
                                            <span
                                                class="fa-solid fa-layer-group me-2 text-body-tertiary"></span>Collection
                                        </label>
                                        <input type="text" name="collection" class="form-control" id="collection-input"
                                            list="collection-list"
                                            value="<?= htmlspecialchars($_POST['collection'] ?? '') ?>"
                                            autocomplete="off">
                                        <datalist id="collection-list">
                                            <?php foreach ($collections as $collection_name): ?>
                                                <option value="<?= htmlspecialchars($collection_name) ?>">
                                                <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3"><span class="fa-solid fa-images me-2"></span>Images</h5>
                                <div class="mb-3"><label class="form-label" for="primary-image-upload"><span
                                            class="fa-solid fa-image me-2 text-body-tertiary"></span>Primary
                                        Image</label><input class="form-control" id="primary-image-upload" type="file"
                                        name="primary_image" accept="image/*" required></div>
                                <div class="mb-0">
                                    <label class="form-label" for="gallery-images-upload"><span
                                            class="fa-solid fa-photo-film me-2 text-body-tertiary"></span>Gallery
                                        Images</label>
                                    <div class="dropzone-container" data-input-id="gallery-images-upload">
                                        <input class="form-control d-none" id="gallery-images-upload" type="file"
                                            name="gallery_images[]" accept="image/*" multiple>
                                        <div class="dz-message text-body-tertiary"><span
                                                class="fa-solid fa-cloud-arrow-up fs-4 mb-2"></span><br>Drag your files
                                            here<span class="text-body-secondary"> or </span><button
                                                class="btn btn-link p-0" type="button">Browse</button></div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-2" id="image-preview-container"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mb-5">
                            <button class="btn btn-phoenix-secondary" type="submit" name="save_action"
                                value="draft"><span class="fa-solid fa-save me-1"></span>Save Draft</button>
                            <button class="btn btn-primary" type="submit" name="save_action" value="publish"><span
                                    class="fa-solid fa-cloud-arrow-up me-1"></span>Publish Product</button>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3"><span class="fa-solid fa-sitemap me-2"></span>Organization</h5>
                                <div class="mb-3">
                                    <label class="form-label"><span
                                            class="fa-solid fa-folder-tree me-2 text-body-tertiary"></span>Category</label>
                                    <select name="categories[]" class="form-select" required multiple
                                        data-choices='{"removeItemButton":true}'>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['category_id'] ?>" <?= (!empty($_POST['categories']) && in_array($c['category_id'], $_POST['categories'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-body-secondary">Hold Ctrl (Cmd) to select multiple.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><span
                                            class="fa-solid fa-store me-2 text-body-tertiary"></span>Vendor</label>
                                    <select name="vendor_id" class="form-select" required>
                                        <option value="">Select...</option>
                                        <?php foreach ($vendors as $v): ?>
                                            <option value="<?= $v['vendor_id'] ?>" <?= (($_POST['vendor_id'] ?? '') == $v['vendor_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($v['business_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label"><span
                                            class="fa-solid fa-tags me-2 text-body-tertiary"></span>Tags</label>
                                    <select class="form-select" name="tags[]" multiple
                                        data-choices='{"removeItemButton":true}'>
                                        <?php foreach ($tags as $t): ?>
                                            <option value="<?= $t['tag_id'] ?>" <?= (!empty($_POST['tags']) && in_array($t['tag_id'], $_POST['tags'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-body-secondary">Hold Ctrl (Cmd) to select multiple.</small>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3"><span class="fa-solid fa-toggle-on me-2"></span>Status</h5>
                                <p class="mb-2">Draft: product not visible. Publish: product active.</p>
                                <div class="form-check"><input class="form-check-input" type="radio" id="stDraft"
                                        name="save_action" value="draft" <?= (($_POST['save_action'] ?? '') !== 'publish') ? 'checked' : '' ?>><label class="form-check-label" for="stDraft">Draft</label>
                                </div>
                                <div class="form-check mb-0"><input class="form-check-input" type="radio" id="stPublish"
                                        name="save_action" value="publish" <?= (($_POST['save_action'] ?? '') === 'publish') ? 'checked' : '' ?>><label class="form-check-label"
                                        for="stPublish">Publish</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
    <?php include 'includes/admin_footer.php'; ?>
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/choices/choices.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const primaryInput = document.getElementById('primary-image-upload');
            const galleryInput = document.getElementById('gallery-images-upload');
            const previewContainer = document.getElementById('image-preview-container');
            const dropzone = document.querySelector('.dropzone-container');
            const browseBtn = dropzone.querySelector('button');
            let galleryFiles = new DataTransfer();
            browseBtn.addEventListener('click', () => galleryInput.click());
            dropzone.addEventListener('click', (e) => { if (e.target === dropzone || e.target.classList.contains('dz-message')) { galleryInput.click(); } });
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => { dropzone.addEventListener(eventName, preventDefaults, false); });
            ['dragenter', 'dragover'].forEach(eventName => { dropzone.addEventListener(eventName, () => dropzone.classList.add('dz-drag-hover'), false); });
            ['dragleave', 'drop'].forEach(eventName => { dropzone.addEventListener(eventName, () => dropzone.classList.remove('dz-drag-hover'), false); });
            dropzone.addEventListener('drop', handleDrop, false);
            primaryInput.addEventListener('change', handleFiles);
            galleryInput.addEventListener('change', handleFiles);
            function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
            function handleDrop(e) { handleFiles({ target: { files: e.dataTransfer.files, multiple: true } }); }
            function handleFiles(event) {
                const isPrimary = event.target.id === 'primary-image-upload';
                if (isPrimary && event.target.files.length > 0) {
                    const existingPrimary = previewContainer.querySelector('.preview-primary');
                    if (existingPrimary) existingPrimary.remove();
                    createPreview(event.target.files[0], true);
                } else {
                    Array.from(event.target.files).forEach(file => { galleryFiles.items.add(file); createPreview(file, false); });
                    galleryInput.files = galleryFiles.files;
                }
            }
            function createPreview(file, isPrimary) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'image-preview-item';
                    if (isPrimary) wrapper.classList.add('preview-primary');
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-btn';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.type = 'button';
                    removeBtn.addEventListener('click', function () {
                        wrapper.remove();
                        if (!isPrimary) {
                            const updatedFiles = new DataTransfer();
                            Array.from(galleryFiles.files).forEach(f => { if (f.name !== file.name) { updatedFiles.items.add(f); } });
                            galleryFiles = updatedFiles;
                            galleryInput.files = galleryFiles.files;
                        } else {
                            primaryInput.value = "";
                        }
                    });
                    wrapper.appendChild(img);
                    wrapper.appendChild(removeBtn);
                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>