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

$u = new User();
$i = new InventoryItem($pdo);
$userId = $_SESSION['uid'];
$user = $u->getUserById($mysqli, $userId);


if ($user['vendor_status'] != 'approved') {
    header("Location: ../index.php"); // Redirect if not an approved seller
    exit;
}

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
$p = new ProductItem();

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
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Welcome to Your Seller Dashboard, <?= htmlspecialchars($user['customer_fname']) ?>!</h2>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Information</h5>
                        <p class="card-text">
                            <strong>Name:</strong> <?= htmlspecialchars($seller['seller_name']) ?><br>
                            <strong>Email:</strong> <?= htmlspecialchars($seller['seller_email']) ?><br>
                            <strong>Phone:</strong> <?= htmlspecialchars($seller['seller_phone']) ?><br>
                            <strong>Address:</strong> <?= htmlspecialchars($seller['seller_address']) ?><br>
                            <strong>Business Name:</strong> <?= htmlspecialchars($seller['seller_business_name']) ?><br>
                            <strong>Description:</strong> <?= htmlspecialchars($seller['seller_description']) ?><br>
                            <a href="edit_seller_information.php" class="btn btn-primary btn-sm">Edit Information</a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage Your Products</h5>
                        <p class="card-text">
                            <a href="../product.txt" class="btn btn-primary">Add New Product</a>
                            <a href="manage_products.php" class="btn btn-secondary">Manage Products</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
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
                                    <th>Category</th>
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
                                        <td><?= $product['category'] ?></td>
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
            // $("#productTable").on("click", ".edit-product-button", function (event) {
            //     event.preventDefault();
            //     const productId = $(this).data("product-id");
            //     alert(productId);
            //     $.ajax({
            //         url: "product-edit.php",
            //         method: "GET",
            //         data: {
            //             product_id: productId
            //         },
            //         success: function (data) {
            //             $("#editProductModal .modal-body").html(data);
            //             $("#editProductModal").modal("show");
            //         },
            //         error: function (error) {
            //             console.error("Error fetching product data:", error);
            //             alert("Error fetching product data.");
            //         }
            //     });
            // });

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