<?php
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/Brand.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$productId) {
    header("Location: view-all-products.php");
    exit();
}

// Instantiate objects
$productItemObj = new ProductItem($pdo);
$categoryObj = new Category($pdo);
$vendorObj = new Vendor($pdo);
$brandObj = new Brand($pdo);

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_id' => (int) $productId, // Add product_id to the data array
        'product_name' => trim($_POST['product_name'] ?? ''),
        'category' => (int) ($_POST['category'] ?? 0),
        'vendor_id' => (int) ($_POST['vendor_id'] ?? 0),
        'status' => trim($_POST['status'] ?? 'draft'),
        'brand' => (int) ($_POST['brand'] ?? 0),
        'product_information' => trim($_POST['product_information'] ?? ''),
        'shipping_returns' => (int) ($_POST['shipping_returns'] ?? 0),
        'admin_id' => $_SESSION['admin_id'] ?? null
    ];

    if (empty($data['product_name']) || empty($data['category'])) {
        $message = '<div class="alert alert-danger">Product Name and Category are required.</div>';
    } else {
        if ($productItemObj->updateProduct($data)) {
            $_SESSION['flash_message'] = 'Product updated successfully!';
            header("Location: edit-product.php?id=" . $productId);
            exit();
        } else {
            $message = '<div class="alert alert-danger">Failed to update product. Please try again.</div>';
        }
    }
}

// Fetch data for the form
$product = $productItemObj->getProductById($productId);
if (!$product) {
    die("Product not found.");
}

// Fetch lists for dropdowns
$allCategories = $categoryObj->getAllCategories();
$allVendors = $vendorObj->getAllVendors();
$allBrands = $brandObj->getAllBrands();

$stmt_policies = $pdo->query("SELECT shipping_policy_id, shipping_policy FROM shipping_policy ORDER BY shipping_policy_id ASC");
$allReturnPolicies = $stmt_policies->fetchAll(PDO::FETCH_ASSOC);

// Display session flash message if it exists
if (isset($_SESSION['flash_message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['flash_message'] . '</div>';
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Edit Product - <?= htmlspecialchars($product['product_name'] ?? 'Item') ?></title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
    <style>
        /* Hide default select arrow when Choices.js is active */
        .choices .form-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none !important;
            padding-right: 0.75rem !important;
        }

        /* Remove the form-select class from container to avoid double styling */
        .choices.form-select {
            padding: 0;
            background-image: none;
            border: none;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <!-- Header Section - Outside the card -->
            <div class="row justify-content-center mb-5">
                <div class="col-12 col-lg-11 col-xxl-10">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="view-single-product.php?id=<?= $productId ?>" class="btn btn-phoenix-secondary btn-sm">
                            <span class="fas fa-arrow-left me-2"></span>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mb-5">
                <div class="col-12 col-lg-11 col-xxl-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Product</li>
                            </ol>
                        </nav>
                        <!-- Add New Product Link -->
                        <a href="add-product.php" class="btn btn-primary">
                            <span class="fas fa-plus me-2"></span>Add New Product
                        </a>
                    </div>
                    <h2 class="mb-1"><?= htmlspecialchars($product['product_name'] ?? 'Product') ?></h2>
                    <p class="text-700 fs-7">Last updated:
                        <?= date('M j, Y', strtotime($product['updated_at'] ?? 'now')) ?>
                    </p>
                </div>
            </div>

            <!-- Form with card background -->
            <form class="mb-9" method="post" action="edit-product.php?id=<?= $productId ?>">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-11 col-xxl-10">
                        <div class="card">
                            <div class="card-body">
                                <?= $message ?>

                                <!-- Product Details Section -->
                                <div class="mb-5">
                                    <h4 class="mb-3"><span class="fas fa-edit me-2"></span>Product Details</h4>
                                    <div class="row g-4">
                                        <!-- Full width for product name -->
                                        <div class="col-12">
                                            <label class="form-label fw-semi-bold" for="product_name">Product
                                                Name</label>
                                            <input class="form-control" id="product_name" name="product_name"
                                                type="text" placeholder="Enter product name"
                                                value="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semi-bold" for="description">Short
                                                Description</label>
                                            <textarea class="form-control" id="description" name="description"
                                                placeholder="Brief product description"
                                                rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semi-bold" for="product_information">
                                                <span class="fas fa-file-alt me-2"></span>Full Product Information
                                            </label>
                                            <textarea class="tinymce" id="product_information"
                                                name="product_information"
                                                data-tinymce='{"height":"400",
                                                              "menubar": true,
                                                              "plugins": ["advlist", "autolink", "lists", "link", "image", "charmap", "preview", "anchor", 
                                                                        "searchreplace", "visualblocks", "code", "fullscreen", "insertdatetime", 
                                                                        "media", "table", "help", "wordcount"],
                                                              "toolbar": "undo redo | formatselect | bold italic backcolor | \
                                                                        alignleft aligncenter alignright alignjustify | \
                                                                        bullist numlist outdent indent | removeformat | help",
                                                              "content_style": "body { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; font-size: 16px; }",
                                                              "placeholder": "Enter detailed product description, features, specifications..."}'><?= htmlspecialchars($product['product_information'] ?? '') ?></textarea>
                                            <div class="fs--1 text-600 mt-1">
                                                <span class="fas fa-info-circle me-1"></span>
                                                Use formatting tools above to add headings, lists, and other styling to
                                                your product description
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <!-- Product Images Section -->
                                <div class="mb-5">
                                    <h4 class="mb-3"><span class="fas fa-images me-2"></span>Product Images</h4>
                                    <div class="row g-4">
                                        <?php
                                        $imageDir = "../products/product-{$productId}/product-{$productId}-image/resized";
                                        $relativeImagePath = "products/product-{$productId}/product-{$productId}-image/resized";

                                        if (is_dir($imageDir)) {
                                            $images = glob("{$imageDir}/*.{jpg,jpeg,png,gif}", GLOB_BRACE);

                                            if (!empty($images)) {
                                                foreach ($images as $image) {
                                                    $filename = basename($image);

                                                    $isPrimary = ($product['primary_image'] ?? '') === $filename;
                                                    ?>
                                                    <div class="col-6 col-md-4 col-lg-3">
                                                        <div class="card h-100 image-card"
                                                            data-filename="<?= htmlspecialchars($filename) ?>">
                                                            <div class="card-img-top position-relative">
                                                                <img src="../<?= $relativeImagePath ?>/<?= htmlspecialchars($filename) ?>"
                                                                    alt="Product image" class="img-fluid"
                                                                    style="height: 180px; width: 100%; object-fit: contain;">
                                                                <?php if ($isPrimary): ?>
                                                                    <span
                                                                        class="position-absolute top-0 start-0 badge bg-primary m-2">Primary</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-footer p-2">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small
                                                                        class="text-body-tertiary"><?= htmlspecialchars(substr($filename, 0, 15) . (strlen($filename) > 15 ? '...' : '')) ?></small>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button type="button" class="btn btn-falcon-default"
                                                                            data-bs-toggle="tooltip" title="Set as primary image"
                                                                            onclick="setPrimaryImage('<?= htmlspecialchars($filename) ?>')">
                                                                            <span
                                                                                class="fas fa-star primary-star <?= $isPrimary ? 'text-warning' : 'text-muted' ?>"></span>
                                                                        </button>
                                                                        <button type="button" class="btn btn-falcon-default"
                                                                            data-bs-toggle="tooltip" title="Delete image"
                                                                            onclick="deleteImage('<?= htmlspecialchars($filename) ?>')">
                                                                            <span class="fas fa-trash-alt"></span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                <div class="col-12">
                                                    <div class="alert alert-info">
                                                        <span class="fas fa-info-circle me-2"></span>
                                                        No images found for this product.
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <span class="fas fa-folder-open me-2"></span>
                                                    Image directory not found. Directory path:
                                                    <?= htmlspecialchars($imageDir) ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                        <!-- Upload section -->
                                        <div class="col-12 mt-3">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h5 class="mb-3">Upload New Images</h5>
                                                    <div class="dropzone-area p-5 border border-dashed rounded-3 text-center cursor-pointer"
                                                        id="dropzoneArea">
                                                        <div>
                                                            <span
                                                                class="fas fa-cloud-upload-alt fs-2 text-primary mb-3"></span>
                                                            <h6>Drag & Drop files here</h6>
                                                            <p class="mb-0 text-700 fs--1">or click to browse</p>
                                                        </div>
                                                        <input type="file" id="fileUpload" multiple accept="image/*"
                                                            class="d-none">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-4">

                                <!-- Organization Section -->
                                <div class="mb-5">
                                    <h4 class="mb-3"><span class="fas fa-folder me-2"></span>Organization</h4>
                                    <div class="row g-4">
                                        <!-- Adjusted column sizes for better spacing -->
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <label class="form-label fw-semi-bold" for="status">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" <?= ($product['status'] ?? '') == 'active' ? 'selected' : '' ?>>
                                                    <span class="fas fa-circle text-success me-2"></span>Active
                                                </option>
                                                <option value="inactive" <?= ($product['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>
                                                    <span class="fas fa-circle text-warning me-2"></span>Inactive
                                                </option>
                                                <option value="draft" <?= ($product['status'] ?? '') == 'draft' ? 'selected' : '' ?>>
                                                    <span class="fas fa-circle text-secondary me-2"></span>Draft
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <label class="form-label fw-semi-bold" for="vendor_id">Vendor</label>
                                            <select class="form-select" id="vendor_id" name="vendor_id">
                                                <option value="">Select a vendor</option>
                                                <?php foreach ($allVendors as $vendor): ?>
                                                    <option value="<?= $vendor['vendor_id'] ?>" <?= ($product['vendor_id'] ?? 0) == $vendor['vendor_id'] ? 'selected' : '' ?>>
                                                        business_name </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="mt-1">
                                                <a href="add-vendor.php" class="fs--1 text-primary" target="_blank">
                                                    <span class="fas fa-plus-circle me-1"></span>Add new vendor
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 col-lg-4">
                                            <label class="form-label fw-semi-bold" for="category">Category</label>
                                            <select class="form-select" id="category" name="category" required>
                                                <option value="">Select a category</option>
                                                <?php foreach ($allCategories as $cat): ?>
                                                    <option value="<?= $cat['category_id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['category_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="mt-1">
                                                <a href="manage_categories.php" class="fs--1 text-primary"
                                                    target="_blank">
                                                    <span class="fas fa-cog me-1"></span>Manage categories
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <label class="form-label fw-semi-bold" for="brand">Brand</label>
                                            <select class="form-select" id="brand" name="brand">
                                                <option value="">Select a brand</option>
                                                <?php foreach ($allBrands as $brand): ?>
                                                    <option value="<?= $brand['brand_id'] ?>" <?= ($product['brand_id'] ?? 0) == $brand['brand_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($brand['Name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="mt-1">
                                                <a href="#" class="fs--1 text-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addBrandModal">
                                                    <span class="fas fa-plus-circle me-1"></span>Add new brand
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-8">
                                            <label class="form-label fw-semi-bold" for="shipping_returns">Shipping &
                                                Returns Policy</label>
                                            <select class="form-select" id="shipping_returns" name="shipping_returns">
                                                <option value="">Select a policy</option>
                                                <?php foreach ($allReturnPolicies as $policy): ?>
                                                    <option value="<?= $policy['shipping_policy_id'] ?>"
                                                        <?= ($product['shipping_returns'] ?? 0) == $policy['shipping_policy_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($policy['shipping_policy']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-phoenix-secondary"
                                        onclick="window.location.href='view-single-product.php?id=<?= $productId ?>'">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fas fa-save me-2"></span>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>
    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/tinymce/tinymce.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/choices/choices.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

    <script>

        // Initialize TinyMCE with custom styling
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#product_information',
                    height: 400,
                    menubar: true,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                        'bold italic backcolor | alignleft aligncenter ' +
                        'alignright alignjustify | bullist numlist outdent indent | ' +
                        'removeformat | help',
                    content_style: `
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                            font-size: 16px;
                            line-height: 1.5;
                            padding: 1rem;
                        }
                        h1, h2, h3, h4, h5, h6 {
                            margin-top: 0;
                            margin-bottom: 0.5rem;
                            font-weight: 600;
                            color: #344050;
                        }
                        p { margin-bottom: 1rem; }
                        ul, ol { padding-left: 2rem; margin-bottom: 1rem; }
                    `,
                    setup: function (editor) {
                        editor.on('change', function () {
                            editor.save(); // Ensures form submission includes the content
                        });
                    }
                });
            }
        });

        let autoSaveTimeout;
        const autoSaveDelay = 30000; // 30 seconds

        function autoSave() {
            const form = document.querySelector('form');
            const formData = new FormData(form);
            formData.append('auto_save', '1');

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const timestamp = new Date().toLocaleTimeString();
                        document.querySelector('.auto-save-status').textContent = `Draft saved at ${timestamp}`;
                    }
                });
        }

        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('change', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(autoSave, autoSaveDelay);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const requiredFields = form.querySelectorAll('[required]');

            form.addEventListener('submit', function (e) {
                let hasError = false;
                const errorMessages = [];

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        hasError = true;
                        field.classList.add('is-invalid');
                        errorMessages.push(`${field.getAttribute('placeholder') || field.name} is required`);
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (hasError) {
                    e.preventDefault();
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <h5>Please fix the following errors:</h5>
                        <ul class="mb-0">
                            ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                        </ul>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.insertBefore(alertDiv, form.firstChild);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Choices.js for all select elements
            const selects = {
                status: {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Select status'
                },
                vendor_id: {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Select a vendor'
                },
                category: {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Select a category'
                },
                brand: {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Select a brand'
                },
                shipping_returns: {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Select a policy'
                }
            };

            // Initialize each select with Choices.js
            Object.keys(selects).forEach(selectId => {
                const element = document.getElementById(selectId);
                if (element) {
                    const choices = new Choices(element, {
                        ...selects[selectId],
                        searchEnabled: true,
                        searchPlaceholderValue: 'Type to search...',
                        itemSelectText: 'Press to select',
                        classNames: {
                            containerOuter: 'choices', // Removed form-select class
                            containerInner: 'choices__inner',
                            input: 'choices__input',
                            inputCloned: 'choices__input--cloned',
                            list: 'choices__list',
                            listItems: 'choices__list--multiple',
                            listSingle: 'choices__list--single',
                            listDropdown: 'choices__list--dropdown',
                            item: 'choices__item',
                            itemSelectable: 'choices__item--selectable',
                            itemDisabled: 'choices__item--disabled',
                            itemChoice: 'choices__item--choice',
                            placeholder: 'choices__placeholder',
                            group: 'choices__group',
                            groupHeading: 'choices__heading',
                            button: 'choices__button'
                        }
                    });

                    // Store the Choices instance on the element for later use
                    element.choicesInstance = choices;
                }
            });

            // Update the category change event listener to use the stored Choices instance
            const categorySelect = document.getElementById('category');
            const brandSelect = document.getElementById('brand');

            if (categorySelect && brandSelect && brandSelect.choicesInstance) {
                categorySelect.addEventListener('change', function () {
                    const categoryId = this.value;
                    const currentBrandId = brandSelect.value;
                    const brandChoices = brandSelect.choicesInstance;

                    // Show loading state
                    brandChoices.clearStore();
                    brandChoices.setChoices([{ value: '', label: 'Loading brands...', disabled: true }], 'value', 'label', true);

                    // Fetch filtered brands
                    fetch(`ajax/get_brands_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const brands = data.brands;
                                let newChoices = [{ value: '', label: 'Select a brand', disabled: false }];

                                brands.forEach(brand => {
                                    newChoices.push({
                                        value: brand.brandID,
                                        label: brand.Name,
                                        selected: brand.brandID == currentBrandId
                                    });
                                });

                                brandChoices.setChoices(newChoices, 'value', 'label', true);
                            } else {
                                brandChoices.setChoices([{ value: '', label: 'Error loading brands' }], 'value', 'label', true);
                                console.error('Failed to load brands:', data.error);
                            }
                        })
                        .catch(error => {
                            brandChoices.setChoices([{ value: '', label: 'Error loading brands' }], 'value', 'label', true);
                            console.error('Fetch error:', error);
                        });
                });
            }
        });

        // Add to the bottom of your script section
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });

            // Handle image upload functionality
            const dropzoneArea = document.getElementById('dropzoneArea');
            const fileInput = document.getElementById('fileUpload');

            if (dropzoneArea && fileInput) {
                // Click on dropzone to open file browser
                dropzoneArea.addEventListener('click', () => {
                    fileInput.click();
                });

                // Highlight dropzone on drag over
                dropzoneArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzoneArea.classList.add('border-primary');
                    dropzoneArea.classList.add('bg-primary-soft');
                });

                // Remove highlight on drag leave
                dropzoneArea.addEventListener('dragleave', () => {
                    dropzoneArea.classList.remove('border-primary');
                    dropzoneArea.classList.remove('bg-primary-soft');
                });

                // Handle file drop
                dropzoneArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzoneArea.classList.remove('border-primary');
                    dropzoneArea.classList.remove('bg-primary-soft');

                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        handleFiles(e.dataTransfer.files);
                    }
                });

                // Handle file selection via input
                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length) {
                        handleFiles(fileInput.files);
                    }
                });
            }

            function handleFiles(files) {
                const formData = new FormData();
                formData.append('product_id', '<?= $productId ?>');

                // Add loading indicator
                const loadingAlert = document.createElement('div');
                loadingAlert.className = 'alert alert-info';
                loadingAlert.innerHTML = '<span class="fas fa-spinner fa-spin me-2"></span>Uploading images...';

                const cardBody = document.querySelector('.dropzone-area').closest('.card-body');
                cardBody.prepend(loadingAlert);

                // Add all files to form data
                for (let i = 0; i < files.length; i++) {
                    formData.append('images[]', files[i]);
                }

                // Send AJAX request
                fetch('ajax/upload-product-images.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        // Remove loading indicator
                        loadingAlert.remove();

                        if (data.success) {
                            // Show success message
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success alert-dismissible fade show';
                            successAlert.innerHTML = `
                <span class="fas fa-check-circle me-2"></span>
                ${data.files.length} file(s) uploaded successfully.
                <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
                            cardBody.prepend(successAlert);

                            // Refresh the page after a short delay to show the new images
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                            errorAlert.innerHTML = `
                <span class="fas fa-exclamation-circle me-2"></span>
                <strong>Upload failed:</strong>
                <ul class="mb-0 mt-1">
                    ${data.errors.map(err => `<li>${err}</li>`).join('')}
                </ul>
                <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
                            cardBody.prepend(errorAlert);
                        }
                    })
                    .catch(error => {
                        // Remove loading indicator
                        loadingAlert.remove();

                        // Show error message
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                        errorAlert.innerHTML = `
            <span class="fas fa-exclamation-circle me-2"></span>
            <strong>Upload failed:</strong> ${error.message}
            <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
                        cardBody.prepend(errorAlert);
                    });
            }

            // Add to your script section in edit-product.php


        });

        function deleteImage(filename) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            fetch('ajax/delete-product-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: <?= $productId ?>,
                    filename: filename
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find the image card and remove it with animation
                        const imageCard = document.querySelector(`[data-filename="${filename}"]`).closest('.col-6');
                        imageCard.style.opacity = '0';
                        imageCard.style.transform = 'scale(0.8)';
                        imageCard.style.transition = 'all 0.3s ease';

                        setTimeout(() => {
                            imageCard.remove();

                            // Check if there are any images left
                            const remainingImages = document.querySelectorAll('.image-card');
                            if (remainingImages.length === 0) {
                                // Show "no images" message
                                const noImagesDiv = document.createElement('div');
                                noImagesDiv.className = 'col-12';
                                noImagesDiv.innerHTML = `
                        <div class="alert alert-info">
                            <span class="fas fa-info-circle me-2"></span>
                            No images found for this product.
                        </div>
                    `;
                                document.querySelector('.image-grid').appendChild(noImagesDiv);
                            }
                        }, 300);
                    } else {
                        alert('Failed to delete image: ' + (data.errors.join(', ') || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete image: ' + error.message);
                });
        }

        function setPrimaryImage(filename) {
            alert('Setting primary image: ' + filename);
            fetch('ajax/set-primary-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: <?= $productId ?>,
                    filename: filename
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI to show the new primary image
                        document.querySelectorAll('.primary-star').forEach(star => {
                            star.classList.remove('text-warning');
                            star.classList.add('text-muted');
                        });

                        const clickedStar = document.querySelector(`[data-filename="${filename}"] .primary-star`);
                        if (clickedStar) {
                            clickedStar.classList.remove('text-muted');
                            clickedStar.classList.add('text-warning');
                        }

                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                <span class="fas fa-check-circle me-2"></span>
                Primary image updated.
                <button class="btn-close" data-bs-dismiss="alert"></button>
            `;
                        document.querySelector('.card-body').prepend(alertDiv);

                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            alertDiv.classList.remove('show');
                            setTimeout(() => alertDiv.remove(), 150);
                        }, 3000);
                    } else {
                        alert('Failed to set primary image: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to set primary image: ' + error.message);
                });
        }
        // Add this to your existing script section
        document.addEventListener('DOMContentLoaded', function () {
            // Add Brand functionality
            const saveBrandBtn = document.getElementById('saveBrandBtn');
            if (saveBrandBtn) {
                saveBrandBtn.addEventListener('click', function () {
                    const brandName = document.getElementById('brandName').value;
                    const brandDescription = document.getElementById('brandDescription').value;
                    const messageContainer = document.getElementById('brandFormMessage');

                    if (!brandName.trim()) {
                        messageContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <span class="fas fa-exclamation-circle me-2"></span>
                        Brand name is required.
                    </div>
                `;
                        return;
                    }

                    messageContainer.innerHTML = `
                <div class="alert alert-info">
                    <span class="fas fa-spinner fa-spin me-2"></span>
                    Adding brand...
                </div>
            `;

                    fetch('ajax/add-brand.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            name: brandName,
                            description: brandDescription
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messageContainer.innerHTML = `
                        <div class="alert alert-success">
                            <span class="fas fa-check-circle me-2"></span>
                            Brand added successfully!
                        </div>
                    `;

                                // Add the new brand to the dropdown
                                const brandSelect = document.getElementById('brand');
                                if (brandSelect && brandSelect.choicesInstance) {
                                    brandSelect.choicesInstance.setChoices([{
                                        value: data.brand_id,
                                        label: brandName,
                                        selected: true
                                    }], 'value', 'label', false);
                                } else {
                                    const option = new Option(brandName, data.brand_id, true, true);
                                    brandSelect.appendChild(option);
                                }

                                // Close the modal after a short delay
                                setTimeout(() => {
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('addBrandModal'));
                                    modal.hide();

                                    // Reset the form
                                    document.getElementById('addBrandForm').reset();
                                    messageContainer.innerHTML = '';
                                }, 1500);
                            } else {
                                messageContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            ${data.error || 'Failed to add brand. Please try again.'}
                        </div>
                    `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            messageContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <span class="fas fa-exclamation-circle me-2"></span>
                        An error occurred. Please try again.
                    </div>
                `;
                        });
                });
            }
        });

    </script>
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModalLabel">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBrandForm">
                        <div class="mb-3">
                            <label for="brandName" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="brandName" required>
                        </div>
                        <div class="mb-3">
                            <label for="brandDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="brandDescription" rows="3"></textarea>
                        </div>
                    </form>
                    <div id="brandFormMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBrandBtn">Save Brand</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>