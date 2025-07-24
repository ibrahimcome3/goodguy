<?php
// filepath: c:\wamp64\www\goodguy\admin\view-all-products.php
session_start();
require_once '../includes.php';
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/Admin.php';

// Redirect if not logged in
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productItem = new ProductItem($pdo);
$categoryObj = new Category($pdo);
$vendorObj = new Vendor($pdo);
$adminObj = new Admin($pdo);

// Get view type (all products or just mine)
$viewType = isset($_GET['view']) ? $_GET['view'] : 'all';

// Get filter values if present
$categoryFilter = isset($_GET['category']) ? (int) $_GET['category'] : null;
$vendorFilter = isset($_GET['vendor']) ? (int) $_GET['vendor'] : null;

// Get all products based on view type and filters
$products = [];
$totalProducts = $productItem->getTotalProductCount();
$myVendorId = null;

// Check if the current admin is a vendor
$currentUserId = $_SESSION['admin_id'] ?? null;
if ($currentUserId) {
    $vendor = $vendorObj->getVendorByUserId($currentUserId);
    if ($vendor) {
        $myVendorId = $vendor['vendor_id'];
    }
}

// Fetch products based on view type and filters
if ($viewType === 'mine' && $myVendorId) {
    $products = $productItem->getAllProductsByVendorId($myVendorId);
    $myProductCount = count($products);
} else {
    // Get all products (with optional filters)
    if ($categoryFilter && $vendorFilter) {
        $products = $productItem->getProductsByCategoryAndVendor($categoryFilter, $vendorFilter);
    } elseif ($categoryFilter) {
        $products = $productItem->getProductsByCategory($categoryFilter);
    } elseif ($vendorFilter) {
        $products = $productItem->getAllProductsByVendorId($vendorFilter);
    } else {
        $products = $productItem->getAllProducts();
    }
}

// Get lists for dropdowns
$allCategories = $categoryObj->getAllCategories();
$allVendors = $vendorObj->getAllVendors();

// Count products
$displayedProductCount = count($products);
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <?php include 'admin-header.php'; ?>
    <title>All Products - Admin Dashboard</title>
</head>

<body>
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Products</li>
                </ol>
            </nav>
            <div class="mb-9">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0">Products <span
                                class="text-body-tertiary fw-normal">(<?= $totalProducts ?>)</span></h2>
                    </div>
                </div>
                <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
                    <li class="nav-item">
                        <a class="nav-link <?= $viewType === 'all' ? 'active' : '' ?>" href="?view=all">
                            <span>All Products</span>
                            <span class="text-body-tertiary fw-semibold">(<?= $totalProducts ?>)</span>
                        </a>
                    </li>
                    <?php if ($myVendorId): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $viewType === 'mine' ? 'active' : '' ?>" href="?view=mine">
                                <span>My Products</span>
                                <span class="text-body-tertiary fw-semibold">(<?= $myProductCount ?? 0 ?>)</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div id="products"
                    data-list='{"valueNames":["product","price","category","tags","vendor","time"],"page":10,"pagination":true}'>
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="search-box">
                                <form class="position-relative">
                                    <input class="form-control search-input search" type="search"
                                        placeholder="Search products" aria-label="Search">
                                    <svg class="svg-inline--fa fa-magnifying-glass search-box-icon" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="magnifying-glass" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                        <path fill="currentColor"
                                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                        </path>
                                    </svg>
                                </form>
                            </div>
                            <div class="scrollbar overflow-hidden-y">
                                <div class="btn-group position-static" role="group">
                                    <!-- Category Filter Dropdown -->
                                    <div class="btn-group position-static text-nowrap">
                                        <button class="btn btn-phoenix-secondary px-7 flex-shrink-0" type="button"
                                            data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
                                            aria-expanded="false" data-bs-reference="parent">
                                            Category<svg class="svg-inline--fa fa-angle-down ms-2" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="angle-down" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                <path fill="currentColor"
                                                    d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z">
                                                </path>
                                            </svg>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?view=<?= $viewType ?>">All
                                                    Categories</a></li>
                                            <?php foreach ($allCategories as $category): ?>
                                                <li><a class="dropdown-item"
                                                        href="?view=<?= $viewType ?>&category=<?= $category['category_id'] ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?>"><?= htmlspecialchars($category['name']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <!-- Vendor Filter Dropdown -->
                                    <div class="btn-group position-static text-nowrap">
                                        <button class="btn btn-sm btn-phoenix-secondary px-7 flex-shrink-0"
                                            type="button" data-bs-toggle="dropdown" data-boundary="window"
                                            aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                            Vendor<svg class="svg-inline--fa fa-angle-down ms-2" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="angle-down" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                <path fill="currentColor"
                                                    d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z">
                                                </path>
                                            </svg>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?view=<?= $viewType ?>">All Vendors</a>
                                            </li>
                                            <?php foreach ($allVendors as $vendor): ?>
                                                <li><a class="dropdown-item"
                                                        href="?view=<?= $viewType ?>&vendor=<?= $vendor['vendor_id'] ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?>"><?= htmlspecialchars($vendor['business_name']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php if ($categoryFilter || $vendorFilter): ?>
                                        <a href="?view=<?= $viewType ?>"
                                            class="btn btn-sm btn-phoenix-secondary px-7 flex-shrink-0">Clear Filters</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ms-xxl-auto">
                                <button class="btn btn-link text-body me-4 px-0">
                                    <svg class="svg-inline--fa fa-file-export fs-9 me-2" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="file-export" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                        <path fill="currentColor"
                                            d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V288H216c-13.3 0-24 10.7-24 24s10.7 24 24 24H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM384 336V288H494.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H384zm0-208H256V0L384 128z">
                                        </path>
                                    </svg>Export
                                </button>
                                <button class="btn btn-primary" id="addBtn"
                                    onclick="window.location.href='add-product.php'">
                                    <svg class="svg-inline--fa fa-plus me-2" aria-hidden="true" focusable="false"
                                        data-prefix="fas" data-icon="plus" role="img" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512">
                                        <path fill="currentColor"
                                            d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z">
                                        </path>
                                    </svg>Add product
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
                        <div class="table-responsive scrollbar mx-n1 px-1">
                            <table class="table fs-9 mb-0">
                                <thead>
                                    <tr>
                                        <th class="white-space-nowrap fs-9 align-middle ps-0"
                                            style="max-width:20px; width:18px;">
                                            <div class="form-check mb-0 fs-8">
                                                <input class="form-check-input" id="checkbox-bulk-products-select"
                                                    type="checkbox" data-bulk-select='{"body":"products-table-body"}'>
                                            </div>
                                        </th>
                                        <th class="sort white-space-nowrap align-middle fs-10" scope="col"
                                            style="width:70px;"></th>
                                        <th class="sort white-space-nowrap align-middle ps-4" scope="col"
                                            style="width:350px;" data-sort="product">PRODUCT NAME</th>
                                        <th class="sort align-middle text-end ps-4" scope="col" data-sort="price"
                                            style="width:150px;">PRICE</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="category"
                                            style="width:150px;">CATEGORY</th>
                                        <th class="sort align-middle ps-3" scope="col" data-sort="tags"
                                            style="width:250px;">TAGS</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="vendor"
                                            style="width:200px;">VENDOR</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="time"
                                            style="width:150px;">PUBLISHED ON</th>
                                        <th class="sort text-end align-middle pe-0 ps-4" scope="col">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody class="list" id="products-table-body">
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <h5 class="text-body-tertiary">No products found</h5>
                                                <?php if ($viewType === 'all'): ?>
                                                    <p>There are no products in the system. <a href="add-product.php">Add one
                                                            now</a>.</p>
                                                <?php else: ?>
                                                    <p>You haven't added any products yet. <a href="add-product.php">Add one
                                                            now</a>.</p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product):
                                            $tags = $productItem->getTagsByProductId($product['productID']);
                                            $isOwner = ($myVendorId && $product['vendor_id'] == $myVendorId) || ($product['admin_id'] == $currentUserId);
                                            ?>
                                            <tr class="position-static">
                                                <td class="fs-9 align-middle">
                                                    <div class="form-check mb-0 fs-8">
                                                        <input class="form-check-input" type="checkbox"
                                                            data-bulk-select-row='{"product":"<?= htmlspecialchars($product["product_name"]) ?>"}'>
                                                    </div>
                                                </td>
                                                <td class="align-middle white-space-nowrap py-0">
                                                    <a class="d-block border border-translucent rounded-2"
                                                        href="product-details.php?id=<?= $product['productID'] ?>">
                                                        <img src="<?= htmlspecialchars($product["product_image_path"] ?? "../assets/img/products/default-product.png") ?>"
                                                            alt="" width="53">
                                                    </a>
                                                </td>
                                                <td class="product align-middle ps-4">
                                                    <a class="fw-semibold line-clamp-3 mb-0"
                                                        href="product-details.php?id=<?= $product['productID'] ?>">
                                                        <?= htmlspecialchars($product["product_name"]) ?>
                                                    </a>
                                                </td>
                                                <td
                                                    class="price align-middle white-space-nowrap text-end fw-bold text-body-tertiary ps-4">
                                                    $<?= $productItem->getBasePrice($product['productID']) ?? 0 ?>
                                                </td>
                                                <td
                                                    class="category align-middle white-space-nowrap text-body-quaternary fs-9 ps-4 fw-semibold">
                                                    <?php
                                                    $categories = $categoryObj->getAllCategoriesOfProduct($product['productID']);
                                                    if (!empty($categories)) {
                                                        foreach ($categories as $cat) {
                                                            echo '<span class="badge bg-info me-1">' . htmlspecialchars($cat['name']) . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-secondary">No Category</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="tags align-middle review pb-2 ps-3" style="min-width:225px;">
                                                    <?php foreach ($tags as $tag): ?>
                                                        <span class="badge badge-tag me-2 mb-2">
                                                            <?= htmlspecialchars($tag['name'] ?? $tag['tag_name'] ?? '') ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td class="vendor align-middle text-start fw-semibold ps-4">
                                                    <?php
                                                    $vendorInfo = $vendorObj->getVendorById($product['vendor_id'] ?? null);
                                                    echo '<a href="vendor-profile.php?id=' . ($vendorInfo['vendor_id'] ?? '') . '">' .
                                                        htmlspecialchars($vendorInfo['business_name'] ?? 'Admin Product') .
                                                        '</a>';
                                                    ?>
                                                </td>
                                                <td
                                                    class="time align-middle white-space-nowrap text-body-tertiary text-opacity-85 ps-4">
                                                    <?= date("M d, Y", strtotime($product["date_added"])) ?>
                                                </td>
                                                <td
                                                    class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                                    <div class="btn-group">
                                                        <a href="product-details.php?id=<?= $product['productID'] ?>"
                                                            class="btn btn-sm btn-info me-2">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>

                                                        <?php if ($isOwner): ?>
                                                            <a href="edit-product.php?id=<?= $product['productID'] ?>"
                                                                class="btn btn-sm btn-warning me-2">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <a href="delete-product.php?id=<?= $product['productID'] ?>"
                                                                class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this product?');">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                            <div class="col-auto d-flex">
                                <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body"
                                    data-list-info="data-list-info">
                                    Showing <span><?= min(10, $displayedProductCount) ?></span> of
                                    <span><?= $displayedProductCount ?></span> results
                                </p>
                                <a class="fw-semibold" href="#!" data-list-view="*">View all<span
                                        class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                <a class="fw-semibold d-none" href="#!" data-list-view="less">View Less<span
                                        class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                            </div>
                            <div class="col-auto d-flex">
                                <button class="page-link" data-list-pagination="prev">
                                    <span class="fas fa-chevron-left"></span>
                                </button>
                                <ul class="mb-0 pagination"></ul>
                                <button class="page-link pe-0" data-list-pagination="next">
                                    <span class="fas fa-chevron-right"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>

    <!-- JavaScript dependencies -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        // Initialize list.js functionality for product filtering
        document.addEventListener('DOMContentLoaded', function () {
            const productList = new List('products', {
                valueNames: ['product', 'price', 'category', 'tags', 'vendor', 'time'],
                page: 10,
                pagination: true
            });
        });
    </script>
</body>

</html>