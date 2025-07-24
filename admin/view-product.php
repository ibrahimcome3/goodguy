<?php
// filepath: c:\wamp64\www\goodguy\admin\view-product.php
session_start();
require_once '../includes.php';

require_once __DIR__ . '/../class/ProductItem.php';
$productItem = new ProductItem($pdo);
$totalProducts = $productItem->getTotalProductCount();

// if (empty($_SESSION['admin_id'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// // Include database connection
// require_once '../includes.php';
// // Check if the user is an admin
// if ($_SESSION['user_type'] !== 'admin') {
//     header("Location: admin_login.php");
//     exit();
// }


// ...existing code...

?>
<?php
// Fetch 5 most recent products added by the current user (replace with your user ID logic)
$currentUserId = $_SESSION['admin_id'] ?? null; // Make sure user_id is set in session
require_once __DIR__ . '/../class/Vendor.php';
$vendorObj = new Vendor($pdo);

if ($currentUserId) {
    $vendor = $vendorObj->getVendorByUserId($currentUserId);
    if (!$vendor) {
        echo "<h2>You are not a vendor. Please register as a vendor first.</h2>";
        exit;

    }
}


if ($vendor['vendor_id']) {
    $stmt = $pdo->prepare("
    SELECT pi.productID, pi.product_name, pi.date_added, img.image, v.vendor_id, v.business_name
    FROM productitem pi
    LEFT JOIN product_images img ON img.product_id = pi.productID
    LEFT JOIN vendors v ON pi.vendor_id = v.vendor_id
    WHERE pi.admin_id = ?
    GROUP BY pi.productID
    ORDER BY pi.date_added DESC
");
    $stmt->execute([$vendor['vendor_id']]);
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $recentProducts = [];
}



?>

<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default"
    class="opera chrome windows fontawesome-i2svg-active fontawesome-i2svg-complete">

<head>
    <?php include 'admin-header.php'; ?>
</head>


<body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#!">Page 1</a></li>
                    <li class="breadcrumb-item"><a href="#!">Page 2</a></li>
                    <li class="breadcrumb-item active">Default</li>
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page"
                            href="view-all-products.php"><span>All </span><span
                                class="text-body-tertiary fw-semibold">(<?= $totalProducts ?>)</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><span>Published </span><span
                                class="text-body-tertiary fw-semibold">(70348)</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><span>Drafts </span><span
                                class="text-body-tertiary fw-semibold">(17)</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><span>On discount </span><span
                                class="text-body-tertiary fw-semibold">(810)</span></a></li>
                </ul>
                <div id="products"
                    data-list="{&quot;valueNames&quot;:[&quot;product&quot;,&quot;price&quot;,&quot;category&quot;,&quot;tags&quot;,&quot;vendor&quot;,&quot;time&quot;],&quot;page&quot;:10,&quot;pagination&quot;:true}">
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="search-box">
                                <form class="position-relative">
                                    <input class="form-control search-input search" type="search"
                                        placeholder="Search products" aria-label="Search">
                                    <svg class="svg-inline--fa fa-magnifying-glass search-box-icon" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="magnifying-glass" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                        </path>
                                    </svg><!-- <span class="fas fa-search search-box-icon"></span> Font Awesome fontawesome.com -->

                                </form>
                            </div>
                            <div class="scrollbar overflow-hidden-y">
                                <div class="btn-group position-static" role="group">
                                    <div class="btn-group position-static text-nowrap">
                                        <button class="btn btn-phoenix-secondary px-7 flex-shrink-0" type="button"
                                            data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
                                            aria-expanded="false" data-bs-reference="parent">
                                            Category<svg class="svg-inline--fa fa-angle-down ms-2" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="angle-down" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z">
                                                </path>
                                            </svg><!-- <span class="fas fa-angle-down ms-2"></span> Font Awesome fontawesome.com --></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Action</a></li>
                                            <li><a class="dropdown-item" href="#">Another action</a></li>
                                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                                        </ul>
                                    </div>
                                    <div class="btn-group position-static text-nowrap">
                                        <button class="btn btn-sm btn-phoenix-secondary px-7 flex-shrink-0"
                                            type="button" data-bs-toggle="dropdown" data-boundary="window"
                                            aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                            Vendor<svg class="svg-inline--fa fa-angle-down ms-2" aria-hidden="true"
                                                focusable="false" data-prefix="fas" data-icon="angle-down" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                                data-fa-i2svg="">
                                                <path fill="currentColor"
                                                    d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z">
                                                </path>
                                            </svg><!-- <span class="fas fa-angle-down ms-2"></span> Font Awesome fontawesome.com --></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Action</a></li>
                                            <li><a class="dropdown-item" href="#">Another action</a></li>
                                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                                        </ul>
                                    </div>
                                    <button class="btn btn-sm btn-phoenix-secondary px-7 flex-shrink-0">More
                                        filters</button>
                                </div>
                            </div>
                            <div class="ms-xxl-auto">
                                <button class="btn btn-link text-body me-4 px-0"><svg
                                        class="svg-inline--fa fa-file-export fs-9 me-2" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="file-export" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V288H216c-13.3 0-24 10.7-24 24s10.7 24 24 24H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM384 336V288H494.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H384zm0-208H256V0L384 128z">
                                        </path>
                                    </svg><!-- <span class="fa-solid fa-file-export fs-9 me-2"></span> Font Awesome fontawesome.com -->Export</button>
                                <button class="btn btn-primary" id="addBtn"
                                    onclick="window.location.href='add-product.php'"><svg
                                        class="svg-inline--fa fa-plus me-2" aria-hidden="true" focusable="false"
                                        data-prefix="fas" data-icon="plus" role="img" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z">
                                        </path>
                                    </svg><!-- <span class="fas fa-plus me-2"></span> Font Awesome fontawesome.com -->Add
                                    product</button>
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
                                                    type="checkbox"
                                                    data-bulk-select="{&quot;body&quot;:&quot;products-table-body&quot;}">
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
                                        <th class="sort align-middle fs-8 text-center ps-4" scope="col"
                                            style="width:125px;"></th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="vendor"
                                            style="width:200px;">VENDOR</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="time"
                                            style="width:50px;">PUBLISHED ON</th>
                                        <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                                    </tr>
                                </thead>

                                // ...existing code...
                                <tbody class="list" id="products-table-body">
                                    <?php

                                    // Replace with your actual user ID logic
                                    $vendor_id = $vendor['vendor_id'] ?? 1; // Assuming $vendor['vendor_id'] contains the vendor_id
                                    
                                    // Fetch products for the user
                                    $products = $productItem->getAllProductsByAdminId($vendor_id);

                                    foreach ($products as $product):
                                        $tags = $productItem->getTagsByProductId($product['productID']);
                                        ?>
                                        <tr class="position-static">
                                            <td class="fs-9 align-middle">
                                                <div class="form-check mb-0 fs-8">
                                                    <input class="form-check-input" type="checkbox" data-bulk-select-row='<?= htmlspecialchars(json_encode([
                                                        "product" => $product["product_name"],
                                                        "productImage" => $product["product_image_path"] ?? "",
                                                        "price" => $product["price"] ?? "",
                                                        "category" => $product["category"],
                                                        "tags" => $tags,
                                                        "star" => $product["star"] ?? false,
                                                        //"vendor" => $product["vendor"],
                                                        "publishedOn" => $product["date_added"] ?? ""
                                                    ])) ?>'>
                                                </div>
                                            </td>
                                            <td class="align-middle white-space-nowrap py-0">
                                                <a class="d-block border border-translucent rounded-2"
                                                    href="product-details.php?id=<?= $product['productID'] ?>">
                                                    <img src="<?= htmlspecialchars($product["product_image_path"] ?? "") ?>"
                                                        alt="" width="53">
                                                </a>
                                            </td>
                                            <td class="product align-middle ps-4">
                                                <a class="fw-semibold line-clamp-3 mb-0"
                                                    href="view-single-product.php?id=<?= $product['productID'] ?>">
                                                    <?= htmlspecialchars($product["product_name"]) ?>
                                                </a>
                                            </td>
                                            <td
                                                class="price align-middle white-space-nowrap text-end fw-bold text-body-tertiary ps-4">
                                                <?= htmlspecialchars($product["price"] ?? "") ?>
                                            </td>
                                            <td
                                                class="category align-middle white-space-nowrap text-body-quaternary fs-9 ps-4 fw-semibold">
                                                <?php
                                                require_once __DIR__ . '/../class/Category.php';
                                                $categoryObj = new Category($pdo);
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
                                            </td>
                                            <td class="tags align-middle review pb-2 ps-3" style="min-width:225px;">
                                                <?php foreach ($tags as $tag): ?>
                                                    <a class="text-decoration-none" href="#!">
                                                        <span class="badge badge-tag me-2 mb-2">
                                                            <?= htmlspecialchars($tag['name'] ?? $tag['tag_name'] ?? '') ?>
                                                        </span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="align-middle review fs-8 text-center ps-4">
                                                <!-- Star icon logic if needed -->
                                            </td>
                                            <td class="vendor align-middle text-start fw-semibold ps-4">
                                                <?php
                                                require_once __DIR__ . '/../class/Vendor.php';
                                                $vendorObj = new Vendor($pdo);

                                                // Assuming $product['vendor_id'] contains the vendor_id
                                                $vendor = $vendorObj->getVendorById($product['vendor_id'] ?? $product['vendor']);
                                                // Debugging line, remove in production
                                                echo '<a href="#!">' . htmlspecialchars($vendor['business_name'] ?? 'Unknown Vendor') . '</a>';
                                                ?>
                                            </td>
                                            <td
                                                class="time align-middle white-space-nowrap text-body-tertiary text-opacity-85 ps-4">
                                                <?= htmlspecialchars($product["published_on"] ?? "") ?>
                                            </td>
                                            <td
                                                class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                                <!-- Actions dropdown here -->
                                            </td>

                                            <td
                                                class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                                <div class="btn-group">
                                                    <a href="edit-product.php?id=<?= $product['productID'] ?>"
                                                        class="btn btn-sm btn-warning me-2">
                                                        Modify
                                                    </a>
                                                    <a href="delete-product.php?id=<?= $product['productID'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this product?');">
                                                        Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                // ...existing code...
                            </table>
                        </div>
                        <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                            <div class="col-auto d-flex">
                                <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body"
                                    data-list-info="data-list-info">1 to 10 <span class="text-body-tertiary"> Items of
                                    </span>16</p><a class="fw-semibold" href="#!" data-list-view="*">View all<svg
                                        class="svg-inline--fa fa-angle-right ms-1" data-fa-transform="down-1"
                                        aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-right"
                                        role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
                                        data-fa-i2svg="" style="transform-origin: 0.3125em 0.5625em;">
                                        <g transform="translate(160 256)">
                                            <g transform="translate(0, 32)  scale(1, 1)  rotate(0 0 0)">
                                                <path fill="currentColor"
                                                    d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"
                                                    transform="translate(-160 -256)"></path>
                                            </g>
                                        </g>
                                    </svg><!-- <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span> Font Awesome fontawesome.com --></a><a
                                    class="fw-semibold d-none" href="#!" data-list-view="less">View Less<svg
                                        class="svg-inline--fa fa-angle-right ms-1" data-fa-transform="down-1"
                                        aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-right"
                                        role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
                                        data-fa-i2svg="" style="transform-origin: 0.3125em 0.5625em;">
                                        <g transform="translate(160 256)">
                                            <g transform="translate(0, 32)  scale(1, 1)  rotate(0 0 0)">
                                                <path fill="currentColor"
                                                    d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"
                                                    transform="translate(-160 -256)"></path>
                                            </g>
                                        </g>
                                    </svg><!-- <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span> Font Awesome fontawesome.com --></a>
                            </div>
                            <div class="col-auto d-flex">
                                <button class="page-link disabled" data-list-pagination="prev" disabled=""><svg
                                        class="svg-inline--fa fa-chevron-left" aria-hidden="true" focusable="false"
                                        data-prefix="fas" data-icon="chevron-left" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z">
                                        </path>
                                    </svg><!-- <span class="fas fa-chevron-left"></span> Font Awesome fontawesome.com --></button>
                                <ul class="mb-0 pagination">
                                    <li class="active"><button class="page" type="button" data-i="1"
                                            data-page="10">1</button></li>
                                    <li><button class="page" type="button" data-i="2" data-page="10">2</button></li>
                                </ul>
                                <button class="page-link pe-0" data-list-pagination="next"><svg
                                        class="svg-inline--fa fa-chevron-right" aria-hidden="true" focusable="false"
                                        data-prefix="fas" data-icon="chevron-right" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z">
                                        </path>
                                    </svg><!-- <span class="fas fa-chevron-right"></span> Font Awesome fontawesome.com --></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer position-absolute">
                <div class="row g-0 justify-content-between align-items-center h-100">
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 mt-2 mt-sm-0 text-body">Thank you for creating with Phoenix<span
                                class="d-none d-sm-inline-block"></span><span
                                class="d-none d-sm-inline-block mx-1">|</span><br class="d-sm-none">2024 ©<a
                                class="mx-1" href="https://themewagon.com">Themewagon</a></p>
                    </div>
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 text-body-tertiary text-opacity-85">v1.20.1</p>
                    </div>
                </div>
            </footer>
        </div>
        <div class="modal fade" id="searchBoxModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true"
            data-phoenix-modal="data-phoenix-modal" style="--phoenix-backdrop-opacity: 1;">
            <div class="modal-dialog">
                <div class="modal-content mt-15 rounded-pill">
                    <div class="modal-body p-0">
                        <div class="search-box navbar-top-search-box"
                            data-list="{&quot;valueNames&quot;:[&quot;title&quot;]}" style="width: auto;">
                            <form class="position-relative" data-bs-toggle="search" data-bs-display="static"
                                aria-expanded="false">
                                <input class="form-control search-input fuzzy-search rounded-pill form-control-lg"
                                    type="search" placeholder="Search..." aria-label="Search">
                                <svg class="svg-inline--fa fa-magnifying-glass search-box-icon" aria-hidden="true"
                                    focusable="false" data-prefix="fas" data-icon="magnifying-glass" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                    </path>
                                </svg><!-- <span class="fas fa-search search-box-icon"></span> Font Awesome fontawesome.com -->

                            </form>
                            <div class="btn-close position-absolute end-0 top-50 translate-middle cursor-pointer shadow-none"
                                data-bs-dismiss="search">
                                <button class="btn btn-link p-0" aria-label="Close"></button>
                            </div>
                            <div class="dropdown-menu border start-0 py-0 overflow-hidden w-100">
                                <div class="scrollbar-overlay" style="max-height: 30rem;" data-simplebar="init">
                                    <div class="simplebar-wrapper" style="margin: 0px;">
                                        <div class="simplebar-height-auto-observer-wrapper">
                                            <div class="simplebar-height-auto-observer"></div>
                                        </div>
                                        <div class="simplebar-mask">
                                            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                                <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                    aria-label="scrollable content"
                                                    style="height: auto; overflow: hidden;">
                                                    <div class="simplebar-content" style="padding: 0px;">
                                                        <div class="list pb-3">
                                                            <h6 class="dropdown-header text-body-highlight fs-10 py-2">
                                                                24 <span class="text-body-quaternary">results</span>
                                                            </h6>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Recently Searched </h6>
                                                            <div class="py-2"><a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-clock-rotate-left"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas"
                                                                                data-icon="clock-rotate-left" role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 512 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.5em 0.5em;">
                                                                                <g transform="translate(256 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M75 75L41 41C25.9 25.9 0 36.6 0 57.9V168c0 13.3 10.7 24 24 24H134.1c21.4 0 32.1-25.9 17-41l-30.8-30.8C155 85.5 203 64 256 64c106 0 192 86 192 192s-86 192-192 192c-40.8 0-78.6-12.7-109.7-34.4c-14.5-10.1-34.4-6.6-44.6 7.9s-6.6 34.4 7.9 44.6C151.2 495 201.7 512 256 512c141.4 0 256-114.6 256-256S397.4 0 256 0C185.3 0 121.3 28.7 75 75zm181 53c-13.3 0-24 10.7-24 24V256c0 6.4 2.5 12.5 7 17l72 72c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-65-65V152c0-13.3-10.7-24-24-24z"
                                                                                            transform="translate(-256 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-clock-rotate-left" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Store Macbook
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-clock-rotate-left"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas"
                                                                                data-icon="clock-rotate-left" role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 512 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.5em 0.5em;">
                                                                                <g transform="translate(256 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M75 75L41 41C25.9 25.9 0 36.6 0 57.9V168c0 13.3 10.7 24 24 24H134.1c21.4 0 32.1-25.9 17-41l-30.8-30.8C155 85.5 203 64 256 64c106 0 192 86 192 192s-86 192-192 192c-40.8 0-78.6-12.7-109.7-34.4c-14.5-10.1-34.4-6.6-44.6 7.9s-6.6 34.4 7.9 44.6C151.2 495 201.7 512 256 512c141.4 0 256-114.6 256-256S397.4 0 256 0C185.3 0 121.3 28.7 75 75zm181 53c-13.3 0-24 10.7-24 24V256c0 6.4 2.5 12.5 7 17l72 72c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-65-65V152c0-13.3-10.7-24-24-24z"
                                                                                            transform="translate(-256 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-clock-rotate-left" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            MacBook Air - 13″
                                                                        </div>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Products</h6>
                                                            <div class="py-2"><a
                                                                    class="dropdown-item py-2 d-flex align-items-center"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="file-thumbnail me-2"><img
                                                                            class="h-100 w-100 object-fit-cover rounded-3"
                                                                            src="phoenix-v1.20.1/public/assets/img/products/60x60/3.png"
                                                                            alt=""></div>
                                                                    <div class="flex-1">
                                                                        <h6 class="mb-0 text-body-highlight title">
                                                                            MacBook Air - 13″</h6>
                                                                        <p class="fs-10 mb-0 d-flex text-body-tertiary">
                                                                            <span
                                                                                class="fw-medium text-body-tertiary text-opactity-85">8GB
                                                                                Memory - 1.6GHz - 128GB Storage</span>
                                                                        </p>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item py-2 d-flex align-items-center"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="file-thumbnail me-2"><img
                                                                            class="img-fluid"
                                                                            src="phoenix-v1.20.1/public/assets/img/products/60x60/3.png"
                                                                            alt=""></div>
                                                                    <div class="flex-1">
                                                                        <h6 class="mb-0 text-body-highlight title">
                                                                            MacBook Pro - 13″</h6>
                                                                        <p class="fs-10 mb-0 d-flex text-body-tertiary">
                                                                            <span
                                                                                class="fw-medium text-body-tertiary text-opactity-85">30
                                                                                Sep at 12:30 PM</span>
                                                                        </p>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Quick Links</h6>
                                                            <div class="py-2"><a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-link text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas" data-icon="link"
                                                                                role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 640 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.625em 0.5em;">
                                                                                <g transform="translate(320 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"
                                                                                            transform="translate(-320 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-link text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Support MacBook House
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-link text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas" data-icon="link"
                                                                                role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 640 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.625em 0.5em;">
                                                                                <g transform="translate(320 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"
                                                                                            transform="translate(-320 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-link text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Store MacBook″
                                                                        </div>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Files</h6>
                                                            <div class="py-2"><a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-file-zipper text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas"
                                                                                data-icon="file-zipper" role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 384 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.375em 0.5em;">
                                                                                <g transform="translate(192 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM96 48c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm-6.3 71.8c3.7-14 16.4-23.8 30.9-23.8h14.8c14.5 0 27.2 9.7 30.9 23.8l23.5 88.2c1.4 5.4 2.1 10.9 2.1 16.4c0 35.2-28.8 63.7-64 63.7s-64-28.5-64-63.7c0-5.5 .7-11.1 2.1-16.4l23.5-88.2zM112 336c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H112z"
                                                                                            transform="translate(-192 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-file-zipper text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Library MacBook folder.rar
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-file-lines text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas" data-icon="file-lines"
                                                                                role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 384 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.375em 0.5em;">
                                                                                <g transform="translate(192 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM112 256H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16z"
                                                                                            transform="translate(-192 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-file-lines text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Feature MacBook extensions.txt
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-image text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fas" data-icon="image"
                                                                                role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 512 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.5em 0.5em;">
                                                                                <g transform="translate(256 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"
                                                                                            transform="translate(-256 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-solid fa-image text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            MacBook Pro_13.jpg
                                                                        </div>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Members</h6>
                                                            <div class="py-2"><a
                                                                    class="dropdown-item py-2 d-flex align-items-center"
                                                                    href="phoenix-v1.20.1/public/pages/members.html">
                                                                    <div
                                                                        class="avatar avatar-l status-online  me-2 text-body">
                                                                        <img class="rounded-circle "
                                                                            src="phoenix-v1.20.1/public/assets/img/team/40x40/10.webp"
                                                                            alt="">

                                                                    </div>
                                                                    <div class="flex-1">
                                                                        <h6 class="mb-0 text-body-highlight title">Carry
                                                                            Anna</h6>
                                                                        <p class="fs-10 mb-0 d-flex text-body-tertiary">
                                                                            anna@technext.it</p>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item py-2 d-flex align-items-center"
                                                                    href="phoenix-v1.20.1/public/pages/members.html">
                                                                    <div class="avatar avatar-l  me-2 text-body">
                                                                        <img class="rounded-circle "
                                                                            src="phoenix-v1.20.1/public/assets/img/team/40x40/12.webp"
                                                                            alt="">

                                                                    </div>
                                                                    <div class="flex-1">
                                                                        <h6 class="mb-0 text-body-highlight title">John
                                                                            Smith</h6>
                                                                        <p class="fs-10 mb-0 d-flex text-body-tertiary">
                                                                            smith@technext.it</p>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                            <hr class="my-0">
                                                            <h6
                                                                class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">
                                                                Related Searches</h6>
                                                            <div class="py-2"><a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-firefox-browser text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fab"
                                                                                data-icon="firefox-browser" role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 512 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.5em 0.5em;">
                                                                                <g transform="translate(256 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M130.22 127.548C130.38 127.558 130.3 127.558 130.22 127.548V127.548ZM481.64 172.898C471.03 147.398 449.56 119.898 432.7 111.168C446.42 138.058 454.37 165.048 457.4 185.168C457.405 185.306 457.422 185.443 457.45 185.578C429.87 116.828 383.098 89.1089 344.9 28.7479C329.908 5.05792 333.976 3.51792 331.82 4.08792L331.7 4.15792C284.99 30.1109 256.365 82.5289 249.12 126.898C232.503 127.771 216.219 131.895 201.19 139.035C199.838 139.649 198.736 140.706 198.066 142.031C197.396 143.356 197.199 144.87 197.506 146.323C197.7 147.162 198.068 147.951 198.586 148.639C199.103 149.327 199.76 149.899 200.512 150.318C201.264 150.737 202.096 150.993 202.954 151.071C203.811 151.148 204.676 151.045 205.491 150.768L206.011 150.558C221.511 143.255 238.408 139.393 255.541 139.238C318.369 138.669 352.698 183.262 363.161 201.528C350.161 192.378 326.811 183.338 304.341 187.248C392.081 231.108 368.541 381.784 246.951 376.448C187.487 373.838 149.881 325.467 146.421 285.648C146.421 285.648 157.671 243.698 227.041 243.698C234.541 243.698 255.971 222.778 256.371 216.698C256.281 214.698 213.836 197.822 197.281 181.518C188.434 172.805 184.229 168.611 180.511 165.458C178.499 163.75 176.392 162.158 174.201 160.688C168.638 141.231 168.399 120.638 173.51 101.058C148.45 112.468 128.96 130.508 114.8 146.428H114.68C105.01 134.178 105.68 93.7779 106.25 85.3479C106.13 84.8179 99.022 89.0159 98.1 89.6579C89.5342 95.7103 81.5528 102.55 74.26 110.088C57.969 126.688 30.128 160.242 18.76 211.318C14.224 231.701 12 255.739 12 263.618C12 398.318 121.21 507.508 255.92 507.508C376.56 507.508 478.939 420.281 496.35 304.888C507.922 228.192 481.64 173.82 481.64 172.898Z"
                                                                                            transform="translate(-256 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-brands fa-firefox-browser text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Search in the Web MacBook
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                                <a class="dropdown-item"
                                                                    href="phoenix-v1.20.1/public/apps/e-commerce/landing/product-details.html">
                                                                    <div class="d-flex align-items-center">

                                                                        <div
                                                                            class="fw-normal text-body-highlight title">
                                                                            <svg class="svg-inline--fa fa-chrome text-body"
                                                                                data-fa-transform="shrink-2"
                                                                                aria-hidden="true" focusable="false"
                                                                                data-prefix="fab" data-icon="chrome"
                                                                                role="img"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                viewBox="0 0 512 512" data-fa-i2svg=""
                                                                                style="transform-origin: 0.5em 0.5em;">
                                                                                <g transform="translate(256 256)">
                                                                                    <g
                                                                                        transform="translate(0, 0)  scale(0.875, 0.875)  rotate(0 0 0)">
                                                                                        <path fill="currentColor"
                                                                                            d="M0 256C0 209.4 12.47 165.6 34.27 127.1L144.1 318.3C166 357.5 207.9 384 256 384C270.3 384 283.1 381.7 296.8 377.4L220.5 509.6C95.9 492.3 0 385.3 0 256zM365.1 321.6C377.4 302.4 384 279.1 384 256C384 217.8 367.2 183.5 340.7 160H493.4C505.4 189.6 512 222.1 512 256C512 397.4 397.4 511.1 256 512L365.1 321.6zM477.8 128H256C193.1 128 142.3 172.1 130.5 230.7L54.19 98.47C101 38.53 174 0 256 0C350.8 0 433.5 51.48 477.8 128V128zM168 256C168 207.4 207.4 168 256 168C304.6 168 344 207.4 344 256C344 304.6 304.6 344 256 344C207.4 344 168 304.6 168 256z"
                                                                                            transform="translate(-256 -256)">
                                                                                        </path>
                                                                                    </g>
                                                                                </g>
                                                                            </svg><!-- <span class="fa-brands fa-chrome text-body" data-fa-transform="shrink-2"></span> Font Awesome fontawesome.com -->
                                                                            Store MacBook″
                                                                        </div>
                                                                    </div>
                                                                </a>

                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <p class="fallback fw-bold fs-7 d-none">No Result Found.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="simplebar-placeholder" style="width: 0px; height: 0px;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                                        <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var navbarTopStyle = window.config.config.phoenixNavbarTopStyle;
            var navbarTop = document.querySelector('.navbar-top');
            if (navbarTopStyle === 'darker') {
                navbarTop.setAttribute('data-navbar-appearance', 'darker');
            }

            var navbarVerticalStyle = window.config.config.phoenixNavbarVerticalStyle;
            var navbarVertical = document.querySelector('.navbar-vertical');
            if (navbarVertical && navbarVerticalStyle === 'darker') {
                navbarVertical.setAttribute('data-navbar-appearance', 'darker');
            }
        </script>
        <div class="support-chat-container show">
            <div class="container-fluid support-chat">
                <div class="card bg-body-emphasis">
                    <div class="card-header d-flex flex-between-center px-4 py-3 border-bottom border-translucent">
                        <h5 class="mb-0 d-flex align-items-center gap-2">Demo widget<svg
                                class="svg-inline--fa fa-circle text-success fs-11" aria-hidden="true" focusable="false"
                                data-prefix="fas" data-icon="circle" role="img" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512" data-fa-i2svg="">
                                <path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512z"></path>
                            </svg><!-- <span class="fa-solid fa-circle text-success fs-11"></span> Font Awesome fontawesome.com -->
                        </h5>
                        <div class="btn-reveal-trigger">
                            <button class="btn btn-link p-0 dropdown-toggle dropdown-caret-none transition-none d-flex"
                                type="button" id="support-chat-dropdown" data-bs-toggle="dropdown"
                                data-boundary="window" aria-haspopup="true" aria-expanded="false"
                                data-bs-reference="parent"><svg class="svg-inline--fa fa-ellipsis text-body"
                                    aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis"
                                    role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                    data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z">
                                    </path>
                                </svg><!-- <span class="fas fa-ellipsis-h text-body"></span> Font Awesome fontawesome.com --></button>
                            <div class="dropdown-menu dropdown-menu-end py-2" aria-labelledby="support-chat-dropdown"><a
                                    class="dropdown-item" href="#!">Request a callback</a><a class="dropdown-item"
                                    href="#!">Search in chat</a><a class="dropdown-item" href="#!">Show history</a><a
                                    class="dropdown-item" href="#!">Report to Admin</a><a
                                    class="dropdown-item btn-support-chat" href="#!">Close Support</a></div>
                        </div>
                    </div>
                    <div class="card-body chat p-0">
                        <div class="d-flex flex-column-reverse scrollbar h-100 p-3">
                            <div class="text-end mt-6"><a
                                    class="mb-2 d-inline-flex align-items-center text-decoration-none text-body-emphasis bg-body-hover rounded-pill border border-primary py-2 ps-4 pe-3"
                                    href="#!">
                                    <p class="mb-0 fw-semibold fs-9">I need help with something</p><svg
                                        class="svg-inline--fa fa-paper-plane text-primary fs-9 ms-3" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="paper-plane" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z">
                                        </path>
                                    </svg><!-- <span class="fa-solid fa-paper-plane text-primary fs-9 ms-3"></span> Font Awesome fontawesome.com -->
                                </a><a
                                    class="mb-2 d-inline-flex align-items-center text-decoration-none text-body-emphasis bg-body-hover rounded-pill border border-primary py-2 ps-4 pe-3"
                                    href="#!">
                                    <p class="mb-0 fw-semibold fs-9">I can’t reorder a product I previously ordered</p>
                                    <svg class="svg-inline--fa fa-paper-plane text-primary fs-9 ms-3" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="paper-plane" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z">
                                        </path>
                                    </svg><!-- <span class="fa-solid fa-paper-plane text-primary fs-9 ms-3"></span> Font Awesome fontawesome.com -->
                                </a><a
                                    class="mb-2 d-inline-flex align-items-center text-decoration-none text-body-emphasis bg-body-hover rounded-pill border border-primary py-2 ps-4 pe-3"
                                    href="#!">
                                    <p class="mb-0 fw-semibold fs-9">How do I place an order?</p><svg
                                        class="svg-inline--fa fa-paper-plane text-primary fs-9 ms-3" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="paper-plane" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z">
                                        </path>
                                    </svg><!-- <span class="fa-solid fa-paper-plane text-primary fs-9 ms-3"></span> Font Awesome fontawesome.com -->
                                </a><a
                                    class="false d-inline-flex align-items-center text-decoration-none text-body-emphasis bg-body-hover rounded-pill border border-primary py-2 ps-4 pe-3"
                                    href="#!">
                                    <p class="mb-0 fw-semibold fs-9">My payment method not working</p><svg
                                        class="svg-inline--fa fa-paper-plane text-primary fs-9 ms-3" aria-hidden="true"
                                        focusable="false" data-prefix="fas" data-icon="paper-plane" role="img"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                        <path fill="currentColor"
                                            d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z">
                                        </path>
                                    </svg><!-- <span class="fa-solid fa-paper-plane text-primary fs-9 ms-3"></span> Font Awesome fontawesome.com -->
                                </a>
                            </div>
                            <div class="text-center mt-auto">
                                <div class="avatar avatar-3xl status-online"><img
                                        class="rounded-circle border border-3 border-light-subtle"
                                        src="phoenix-v1.20.1/public/assets/img/team/30.webp" alt=""></div>
                                <h5 class="mt-2 mb-3">Eric</h5>
                                <p class="text-center text-body-emphasis mb-0">Ask us anything – we’ll get back to you
                                    here or by email within 24 hours.</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="card-footer d-flex align-items-center gap-2 border-top border-translucent ps-3 pe-4 py-3">
                        <div class="d-flex align-items-center flex-1 gap-3 border border-translucent rounded-pill px-4">
                            <input class="form-control outline-none border-0 flex-1 fs-9 px-0" type="text"
                                placeholder="Write message">
                            <label class="btn btn-link d-flex p-0 text-body-quaternary fs-9 border-0"
                                for="supportChatPhotos"><svg class="svg-inline--fa fa-image" aria-hidden="true"
                                    focusable="false" data-prefix="fas" data-icon="image" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z">
                                    </path>
                                </svg><!-- <span class="fa-solid fa-image"></span> Font Awesome fontawesome.com --></label>
                            <input class="d-none" type="file" accept="image/*" id="supportChatPhotos">
                            <label class="btn btn-link d-flex p-0 text-body-quaternary fs-9 border-0"
                                for="supportChatAttachment"> <svg class="svg-inline--fa fa-paperclip" aria-hidden="true"
                                    focusable="false" data-prefix="fas" data-icon="paperclip" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z">
                                    </path>
                                </svg><!-- <span class="fa-solid fa-paperclip"></span> Font Awesome fontawesome.com --></label>
                            <input class="d-none" type="file" id="supportChatAttachment">
                        </div>
                        <button class="btn p-0 border-0 send-btn"><svg class="svg-inline--fa fa-paper-plane fs-9"
                                aria-hidden="true" focusable="false" data-prefix="fas" data-icon="paper-plane"
                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                <path fill="currentColor"
                                    d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z">
                                </path>
                            </svg><!-- <span class="fa-solid fa-paper-plane fs-9"></span> Font Awesome fontawesome.com --></button>
                    </div>
                </div>
            </div>
            <button class="btn btn-support-chat p-0 border border-translucent"><span
                    class="fs-8 btn-text text-primary text-nowrap">Chat demo</span><span
                    class="ping-icon-wrapper mt-n4 ms-n6 mt-sm-0 ms-sm-2 position-absolute position-sm-relative"><span
                        class="ping-icon-bg"></span><svg class="svg-inline--fa fa-circle ping-icon" aria-hidden="true"
                        focusable="false" data-prefix="fas" data-icon="circle" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                        <path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512z"></path>
                    </svg><!-- <span class="fa-solid fa-circle ping-icon"></span> Font Awesome fontawesome.com --></span><svg
                    class="svg-inline--fa fa-headset text-primary fs-8 d-sm-none" aria-hidden="true" focusable="false"
                    data-prefix="fas" data-icon="headset" role="img" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512" data-fa-i2svg="">
                    <path fill="currentColor"
                        d="M256 48C141.1 48 48 141.1 48 256v40c0 13.3-10.7 24-24 24s-24-10.7-24-24V256C0 114.6 114.6 0 256 0S512 114.6 512 256V400.1c0 48.6-39.4 88-88.1 88L313.6 488c-8.3 14.3-23.8 24-41.6 24H240c-26.5 0-48-21.5-48-48s21.5-48 48-48h32c17.8 0 33.3 9.7 41.6 24l110.4 .1c22.1 0 40-17.9 40-40V256c0-114.9-93.1-208-208-208zM144 208h16c17.7 0 32 14.3 32 32V352c0 17.7-14.3 32-32 32H144c-35.3 0-64-28.7-64-64V272c0-35.3 28.7-64 64-64zm224 0c35.3 0 64 28.7 64 64v48c0 35.3-28.7 64-64 64H352c-17.7 0-32-14.3-32-32V240c0-17.7 14.3-32 32-32h16z">
                    </path>
                </svg><!-- <span class="fa-solid fa-headset text-primary fs-8 d-sm-none"></span> Font Awesome fontawesome.com --><svg
                    class="svg-inline--fa fa-chevron-down text-primary fs-7" aria-hidden="true" focusable="false"
                    data-prefix="fas" data-icon="chevron-down" role="img" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512" data-fa-i2svg="">
                    <path fill="currentColor"
                        d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z">
                    </path>
                </svg><!-- <span class="fa-solid fa-chevron-down text-primary fs-7"></span> Font Awesome fontawesome.com --></button>
        </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->


    <div class="offcanvas offcanvas-end settings-panel border-0" id="settings-offcanvas" tabindex="-1"
        aria-labelledby="settings-offcanvas">
        <div class="offcanvas-header align-items-start border-bottom flex-column border-translucent">
            <div class="pt-1 w-100 mb-6 d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-2 me-2 lh-sm"><svg class="svg-inline--fa fa-palette me-2 fs-8" aria-hidden="true"
                            focusable="false" data-prefix="fas" data-icon="palette" role="img"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor"
                                d="M512 256c0 .9 0 1.8 0 2.7c-.4 36.5-33.6 61.3-70.1 61.3H344c-26.5 0-48 21.5-48 48c0 3.4 .4 6.7 1 9.9c2.1 10.2 6.5 20 10.8 29.9c6.1 13.8 12.1 27.5 12.1 42c0 31.8-21.6 60.7-53.4 62c-3.5 .1-7 .2-10.6 .2C114.6 512 0 397.4 0 256S114.6 0 256 0S512 114.6 512 256zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm0-96a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM288 96a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm96 96a32 32 0 1 0 0-64 32 32 0 1 0 0 64z">
                            </path>
                        </svg><!-- <span class="fas fa-palette me-2 fs-8"></span> Font Awesome fontawesome.com -->Theme
                        Customizer</h5>
                    <p class="mb-0 fs-9">Explore different styles according to your preferences</p>
                </div>
                <button class="btn p-1 fw-bolder" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><svg
                        class="svg-inline--fa fa-xmark fs-8" aria-hidden="true" focusable="false" data-prefix="fas"
                        data-icon="xmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"
                        data-fa-i2svg="">
                        <path fill="currentColor"
                            d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z">
                        </path>
                    </svg><!-- <span class="fas fa-times fs-8"> </span> Font Awesome fontawesome.com --></button>
            </div>
            <button class="btn btn-phoenix-secondary w-100" data-theme-control="reset"><svg
                    class="svg-inline--fa fa-arrows-rotate me-2 fs-10" aria-hidden="true" focusable="false"
                    data-prefix="fas" data-icon="arrows-rotate" role="img" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512" data-fa-i2svg="">
                    <path fill="currentColor"
                        d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z">
                    </path>
                </svg><!-- <span class="fas fa-arrows-rotate me-2 fs-10"></span> Font Awesome fontawesome.com -->Reset
                to default</button>
        </div>
        <div class="offcanvas-body scrollbar px-card" id="themeController">
            <div class="setting-panel-item mt-0">
                <h5 class="setting-panel-item-title">Color Scheme</h5>
                <div class="row gx-2">
                    <div class="col-4">
                        <input class="btn-check" id="themeSwitcherLight" name="theme-color" type="radio" value="light"
                            data-theme-control="phoenixTheme" checked="true">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="themeSwitcherLight"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/default-light.png"
                                    alt=""></span><span class="label-text">Light</span></label>
                    </div>
                    <div class="col-4">
                        <input class="btn-check" id="themeSwitcherDark" name="theme-color" type="radio" value="dark"
                            data-theme-control="phoenixTheme">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="themeSwitcherDark"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/default-dark.png" alt=""></span><span
                                class="label-text"> Dark</span></label>
                    </div>
                    <div class="col-4">
                        <input class="btn-check" id="themeSwitcherAuto" name="theme-color" type="radio" value="auto"
                            data-theme-control="phoenixTheme">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="themeSwitcherAuto"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/auto.png" alt=""></span><span
                                class="label-text">
                                Auto</span></label>
                    </div>
                </div>
            </div>
            <div class="border border-translucent rounded-3 p-4 setting-panel-item bg-body-emphasis">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="setting-panel-item-title mb-1">RTL </h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input ms-auto" type="checkbox" data-theme-control="phoenixIsRTL">
                    </div>
                </div>
                <p class="mb-0 text-body-tertiary">Change text direction</p>
            </div>
            <div class="border border-translucent rounded-3 p-4 setting-panel-item bg-body-emphasis">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="setting-panel-item-title mb-1">Support Chat </h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input ms-auto" type="checkbox" data-theme-control="phoenixSupportChat"
                            checked="true">
                    </div>
                </div>
                <p class="mb-0 text-body-tertiary">Toggle support chat</p>
            </div>
            <div class="setting-panel-item">
                <h5 class="setting-panel-item-title">Navigation Type</h5>
                <div class="row gx-2">
                    <div class="col-6">
                        <input class="btn-check" id="navbarPositionVertical" name="navigation-type" type="radio"
                            value="vertical" data-theme-control="phoenixNavbarPosition"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/vertical-navbar.html"
                            checked="true">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarPositionVertical"> <span
                                class="rounded d-block"><img class="img-fluid img-prototype d-dark-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/default-light.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/default-dark.png" alt=""></span><span
                                class="label-text">Vertical</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbarPositionHorizontal" name="navigation-type" type="radio"
                            value="horizontal" data-theme-control="phoenixNavbarPosition"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/horizontal-navbar.html">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarPositionHorizontal"> <span
                                class="rounded d-block"><img class="img-fluid img-prototype d-dark-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-default.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-default-dark.png"
                                    alt=""></span><span class="label-text"> Horizontal</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbarPositionCombo" name="navigation-type" type="radio"
                            value="combo" data-theme-control="phoenixNavbarPosition"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/combo-navbar.html">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarPositionCombo"> <span
                                class="rounded d-block"><img class="img-fluid img-prototype d-dark-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/nav-combo-light.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/nav-combo-dark.png"
                                    alt=""></span><span class="label-text"> Combo</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbarPositionTopDouble" name="navigation-type" type="radio"
                            value="dual-nav" data-theme-control="phoenixNavbarPosition"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/dual-nav.html">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarPositionTopDouble"> <span
                                class="rounded d-block"><img class="img-fluid img-prototype d-dark-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/dual-light.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none"
                                    src="phoenix-v1.20.1/public/assets/img/generic/dual-dark.png" alt=""></span><span
                                class="label-text"> Dual nav</span></label>
                    </div>
                </div>
            </div>
            <div class="setting-panel-item">
                <h5 class="setting-panel-item-title">Vertical Navbar Appearance</h5>
                <div class="row gx-2">
                    <div class="col-6">
                        <input class="btn-check" id="navbar-style-default" type="radio" name="config.name"
                            value="default" data-theme-control="phoenixNavbarVerticalStyle" checked="true">
                        <label class="btn d-block w-100 btn-navbar-style fs-9" for="navbar-style-default"> <img
                                class="img-fluid img-prototype d-dark-none"
                                src="phoenix-v1.20.1/public/assets/img/generic/default-light.png" alt=""><img
                                class="img-fluid img-prototype d-light-none"
                                src="phoenix-v1.20.1/public/assets/img/generic/default-dark.png" alt=""><span
                                class="label-text d-dark-none"> Default</span><span
                                class="label-text d-light-none">Default</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbar-style-dark" type="radio" name="config.name" value="darker"
                            data-theme-control="phoenixNavbarVerticalStyle">
                        <label class="btn d-block w-100 btn-navbar-style fs-9" for="navbar-style-dark"> <img
                                class="img-fluid img-prototype d-dark-none"
                                src="phoenix-v1.20.1/public/assets/img/generic/vertical-darker.png" alt=""><img
                                class="img-fluid img-prototype d-light-none"
                                src="phoenix-v1.20.1/public/assets/img/generic/vertical-lighter.png" alt=""><span
                                class="label-text d-dark-none"> Darker</span><span
                                class="label-text d-light-none">Lighter</span></label>
                    </div>
                </div>
            </div>
            <div class="setting-panel-item">
                <h5 class="setting-panel-item-title">Horizontal Navbar Shape</h5>
                <div class="row gx-2">
                    <div class="col-6">
                        <input class="btn-check" id="navbarShapeDefault" name="navbar-shape" type="radio"
                            value="default" data-theme-control="phoenixNavbarTopShape"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/horizontal-navbar.html"
                            checked="true">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarShapeDefault"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype d-dark-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-default.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-default-dark.png"
                                    alt=""></span><span class="label-text">Default</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbarShapeSlim" name="navbar-shape" type="radio" value="slim"
                            data-theme-control="phoenixNavbarTopShape"
                            data-page-url="phoenix-v1.20.1/public/documentation/layouts/horizontal-navbar.html#horizontal-navbar-slim">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarShapeSlim"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype d-dark-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-slim.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-slim-dark.png"
                                    alt=""></span><span class="label-text"> Slim</span></label>
                    </div>
                </div>
            </div>
            <div class="setting-panel-item">
                <h5 class="setting-panel-item-title">Horizontal Navbar Appearance</h5>
                <div class="row gx-2">
                    <div class="col-6">
                        <input class="btn-check" id="navbarTopDefault" name="navbar-top-style" type="radio"
                            value="default" data-theme-control="phoenixNavbarTopStyle">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarTopDefault"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype d-dark-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-default.png" alt=""><img
                                    class="img-fluid img-prototype d-light-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-style-darker.png"
                                    alt=""></span><span class="label-text">Default</span></label>
                    </div>
                    <div class="col-6">
                        <input class="btn-check" id="navbarTopDarker" name="navbar-top-style" type="radio"
                            value="darker" data-theme-control="phoenixNavbarTopStyle" checked="true">
                        <label class="btn d-inline-block btn-navbar-style fs-9" for="navbarTopDarker"> <span
                                class="mb-2 rounded d-block"><img class="img-fluid img-prototype d-dark-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/navbar-top-style-light.png"
                                    alt=""><img class="img-fluid img-prototype d-light-none mb-0"
                                    src="phoenix-v1.20.1/public/assets/img/generic/top-style-lighter.png"
                                    alt=""></span><span class="label-text d-dark-none">Darker</span><span
                                class="label-text d-light-none">Lighter</span></label>
                    </div>
                </div>
            </div><a class="bun btn-primary d-grid mb-3 text-white mt-5 btn btn-primary"
                href="https://themes.getbootstrap.com/product/phoenix-admin-dashboard-webapp-template/"
                target="_blank">Purchase template</a>
        </div>
    </div><a class="card setting-toggle" href="#settings-offcanvas" data-bs-toggle="offcanvas">
        <div class="card-body d-flex align-items-center px-2 py-1">
            <div class="position-relative rounded-start" style="height:34px;width:28px">
                <div class="settings-popover"><span class="ripple"><span
                            class="fa-spin position-absolute all-0 d-flex flex-center"><span
                                class="icon-spin position-absolute all-0 d-flex flex-center">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="#ffffff"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M19.7369 12.3941L19.1989 12.1065C18.4459 11.7041 18.0843 10.8487 18.0843 9.99495C18.0843 9.14118 18.4459 8.28582 19.1989 7.88336L19.7369 7.59581C19.9474 7.47484 20.0316 7.23291 19.9474 7.03131C19.4842 5.57973 18.6843 4.28943 17.6738 3.20075C17.5053 3.03946 17.2527 2.99914 17.0422 3.12011L16.393 3.46714C15.6883 3.84379 14.8377 3.74529 14.1476 3.3427C14.0988 3.31422 14.0496 3.28621 14.0002 3.25868C13.2568 2.84453 12.7055 2.10629 12.7055 1.25525V0.70081C12.7055 0.499202 12.5371 0.297594 12.2845 0.257272C10.7266 -0.105622 9.16879 -0.0653007 7.69516 0.257272C7.44254 0.297594 7.31623 0.499202 7.31623 0.70081V1.23474C7.31623 2.09575 6.74999 2.8362 5.99824 3.25599C5.95774 3.27861 5.91747 3.30159 5.87744 3.32493C5.15643 3.74527 4.26453 3.85902 3.53534 3.45302L2.93743 3.12011C2.72691 2.99914 2.47429 3.03946 2.30587 3.20075C1.29538 4.28943 0.495411 5.57973 0.0322686 7.03131C-0.051939 7.23291 0.0322686 7.47484 0.242788 7.59581L0.784376 7.8853C1.54166 8.29007 1.92694 9.13627 1.92694 9.99495C1.92694 10.8536 1.54166 11.6998 0.784375 12.1046L0.242788 12.3941C0.0322686 12.515 -0.051939 12.757 0.0322686 12.9586C0.495411 14.4102 1.29538 15.7005 2.30587 16.7891C2.47429 16.9504 2.72691 16.9907 2.93743 16.8698L3.58669 16.5227C4.29133 16.1461 5.14131 16.2457 5.8331 16.6455C5.88713 16.6767 5.94159 16.7074 5.99648 16.7375C6.75162 17.1511 7.31623 17.8941 7.31623 18.7552V19.2891C7.31623 19.4425 7.41373 19.5959 7.55309 19.696C7.64066 19.7589 7.74815 19.7843 7.85406 19.8046C9.35884 20.0925 10.8609 20.0456 12.2845 19.7729C12.5371 19.6923 12.7055 19.4907 12.7055 19.2891V18.7346C12.7055 17.8836 13.2568 17.1454 14.0002 16.7312C14.0496 16.7037 14.0988 16.6757 14.1476 16.6472C14.8377 16.2446 15.6883 16.1461 16.393 16.5227L17.0422 16.8698C17.2527 16.9907 17.5053 16.9504 17.6738 16.7891C18.7264 15.7005 19.4842 14.4102 19.9895 12.9586C20.0316 12.757 19.9474 12.515 19.7369 12.3941ZM10.0109 13.2005C8.1162 13.2005 6.64257 11.7893 6.64257 9.97478C6.64257 8.20063 8.1162 6.74905 10.0109 6.74905C11.8634 6.74905 13.3792 8.20063 13.3792 9.97478C13.3792 11.7893 11.8634 13.2005 10.0109 13.2005Z"
                                        fill="#2A7BE4"></path>
                                </svg></span></span></span></div>
            </div><small class="text-uppercase text-body-tertiary fw-bold py-2 pe-2 ps-1 rounded-end">customize</small>
        </div>
    </a>


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
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>



</body>

</html>