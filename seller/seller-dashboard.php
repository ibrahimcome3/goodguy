<?php
session_start();
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/User.php';
require_once '../class/Seller.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php'; // 

// Check if a seller is logged in
if (!isset($_SESSION['uid'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}

$u = new User($pdo);
$i = new InventoryItem($pdo);
$userId = $_SESSION['uid'];
$user = $u->getUserById($userId);

if ($user['vendor_status'] != 'approved') {
    header("Location: ../index.php"); // Redirect if not an approved seller
    exit;
}

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$perPage = 10; // Number of products per page
$offset = ($page - 1) * $perPage;

//Get the total number of products
$sql = "SELECT COUNT(*) AS totalProducts FROM productitem WHERE vendor_id = ?";
$params = [$userId];
$types = "i";

if (!empty($searchTerm)) {
    $sql .= " AND product_name LIKE ?"; // Add search condition
    $params[] = "%{$searchTerm}%";
    $types .= "s";
}

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalProducts = $row['totalProducts'];
$totalPages = ceil($totalProducts / $perPage);

//Get the products for the current page

$s = new Seller();
$seller = $s->getSellerByUserId($mysqli, $userId);

if (!$seller) {
    // Handle error (seller not found)
}
$p = new ProductItem($pdo);

$sql = "SELECT * FROM productitem WHERE vendor_id = ?";
$params = [$userId];
$types = "i";
if (!empty($searchTerm)) {
    $sql .= " AND product_name LIKE ?";
    $params[] = "%{$searchTerm}%";
    $types .= "s";
}
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

//$products = $p->getAllProductsByVendorId($mysqli, $userId, $perPage, $offset);
if ($products) {
    foreach ($products as $product) {
        $sql = "SELECT * FROM product_images WHERE product_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $product['productID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $productImages[$product['productID']] = $result->fetch_all(MYSQLI_ASSOC);

        $totalInventory = $i->getInventoryItemsByProductId($product['productID']);
        $productInventory[$product['productID']] = $totalInventory;
        // Fetch categories for this product
        $sql = "SELECT c.name FROM categories c
                JOIN product_categories pc ON c.category_id = pc.category_id
                WHERE pc.product_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $product['productID']);
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['name'];
        }
        $productCategories[$product['productID']] = $categories;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .card {
            border: 1px solid #ddd;
            /* Subtle border */
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            /* Add a subtle shadow */
        }

        .table-borderless td,
        .table-borderless th {
            border: none;
            /* Remove table cell borders */
        }

        .table-borderless th {
            text-align: left;
            /* Align table headers to the left */
            font-weight: bold;
            /* Make headers bold */
        }
    </style>
</head>

<div>
    <?php include "navbar.php"; ?>

    <div class="container mt-5">
        <h2>Welcome to Your Seller Dashboard, <?= htmlspecialchars($user['customer_fname']) ?>!</h2>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Information</h5>
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th scope="row">Name</th>
                                    <td><?= htmlspecialchars($seller['seller_name']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Email</th>
                                    <td><?= htmlspecialchars($seller['seller_email']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Phone</th>
                                    <td><?= htmlspecialchars($seller['seller_phone']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Address</th>
                                    <td><?= htmlspecialchars($seller['seller_address']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Business Name</th>
                                    <td><?= htmlspecialchars($seller['seller_business_name']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Description</th>
                                    <td><?= htmlspecialchars($seller['seller_description']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <a href="edit_seller_information.php" class="btn btn-primary btn-sm">Edit Information</a>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-5">
            <div class="col-md-6">

                <p class="card-text">
                    <a href="../admin/add-product.php" class="btn btn-primary">Add New Product</a>
                    <a href="manage_products.php" class="btn btn-secondary">Manage Products</a>
                </p>
            </div>


        </div>
        <br />

        <div class="row">
            <div class="col-md-12 mt-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Products</h5>
                        <div class="mb-3">
                            <form method="GET" action="seller-dashboard.php">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="search" id="searchInput"
                                        placeholder="Search products..." value="<?= htmlspecialchars($searchTerm) ?>">
                                    <button class="btn btn-outline-secondary" type="submit">Filter all Pages</button>
                                </div>
                            </form>
                            <!--  <input type="text" class="form-control" placeholder="Search products...">  -->
                        </div>
                        <table class="table table-bordered" id="productTable">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Description</th>
                                    <th>Brand</th>
                                    <th>Categories</th>
                                    <th>Image</th>
                                    <th>Inventory</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <?php foreach ($products as $product): ?>
                                    <tr id="product-row-<?= $product['productID'] ?>">
                                        <td><?= $product['product_name'] ?></td>
                                        <td><?= $product['product_information'] ?></td>
                                        <td><?= $product['brand'] ?></td>
                                        <td>
                                            <?php
                                            // Display categories as a comma-separated list
                                            echo isset($productCategories[$product['productID']]) ? implode(", ", $productCategories[$product['productID']]) : '';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($productImages[$product['productID']])): ?>
                                                <?php foreach ($productImages[$product['productID']] as $image): ?>
                                                    <img src="../products/product-<?= $product['productID'] ?>/product-<?= $product['productID'] ?>-image/<?= $image['image'] ?>"
                                                        alt="<?= $product['product_name'] ?> Image" width="50">
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                No image
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= isset($productInventory[$product['productID']]) ? count($productInventory[$product['productID']]) : 0 ?>
                                            <a href="view_inventory.php?product_id=<?= $product['productID'] ?>">View
                                                Inventory</a>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm edit-product-button"
                                                data-product-id="<?= $product['productID'] ?>">Edit</button>
                                            <button type="button" class="btn btn-danger btn-sm delete-product-button"
                                                data-product-id="<?= $product['productID'] ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($totalProducts > $perPage): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link"
                                                href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>




        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {

                $("#productTable").on("click", ".edit-product-button", function (event) {
                    const productId = $(this).data("product-id");
                    window.location.href = `product-edit.php?product_id=${productId}`;
                });

                $("#searchInput").on("keyup", function () {
                    var value = $(this).val().toLowerCase();
                    $("#productTableBody tr").filter(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
                $(".delete-product-button").click(function (event) {
                    event.preventDefault(); // Prevent the default form submission
                    var productId = $(this).data('product-id');


                    if (confirm("Are you sure you want to delete this product?")) {
                        $.ajax({
                            url: "edit_product.php",
                            type: "POST",
                            data: {
                                action: "delete_product",
                                product_id: productId
                            },
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    // Remove the table row
                                    $("#product-row-" + productId).remove();
                                    // Display a success message
                                    alert(response.message);
                                } else {
                                    alert(response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                alert("Error deleting product: " + error);
                            }
                        });
                    }
                });
            });
        </script>
        </body>

</html>