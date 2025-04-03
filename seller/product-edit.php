<?php
session_start();
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/User.php';
require_once '../class/Seller.php';
require_once '../class/ProductItem.php';

$p = new ProductItem();

if (!isset($_GET['product_id'])) {
    header('Location: seller-dashboard.php');
    exit;
}

$productId = $_GET['product_id'];
$product = $p->getProductById($mysqli, $productId);

if (!$product) {
    header('Location: seller-dashboard.php');
    exit;
}

// Fetch product images
$sql = "SELECT * FROM product_images WHERE product_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$productImages = $result->fetch_all(MYSQLI_ASSOC);

// Check if the product belongs to this seller
if ($product['vendor_id'] != $_SESSION['uid']) {
    header('Location: seller-dashboard.php');
    exit;
}

$currentUserId = $_SESSION['uid'];

// Fetch all categories (no filtering yet)
$sql = "SELECT category_id, name, owner_id FROM categories"; // Changed to fetch all categories
$stmt = $mysqli->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $mysqli->error);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

$allCategories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allCategories[] = $row;
    }
}
$stmt->close();

$sql = "SELECT category_id FROM product_categories WHERE product_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$productCategories = [];
while ($row = $result->fetch_assoc()) {
    $productCategories[] = $row['category_id'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: sans-serif;
        }

        .container {
            margin-top: 20px;
        }

        .mb-3 label {
            display: block;
            margin-bottom: 5px;
        }

       

       

       
        img {
            max-width: 200px;
            max-height: 200px;
            margin-right: 10px;
        }

        .image-checkbox {
            display: inline-block;
            margin-right: 10px;
        }
    </style>
</head>

<body>
<?php include "navbar.php"; ?>
    <div class="container">
        <h2>Edit Product</h2>
        <form method="post" action="process_edit_product.php" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            <div class="mb-3">
                <label for="product_name">Product Name:</label>
                <input type="text" class="form-control" id="product_name" name="product_name"
                    value="<?= htmlspecialchars($product['product_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="brand">Brand:</label>
                <input type="text" class="form-control" id="brand" name="brand"
                    value="<?= htmlspecialchars($product['brand']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="produt_info">Product Information:</label>
                <textarea class="form-control" id="produt_info" name="produt_info"
                    required><?= htmlspecialchars($product['product_information']) ?></textarea>
            </div>
            <!-- Categories section from test-c.php -->
            <div class="mb-3">
                <label for="categories">Choose Categories:</label>
                <select name="categories[]" id="categories" multiple>
                    <?php foreach ($allCategories as $category): ?>
                        <option value="<?= $category['category_id'] ?>" data-owner="<?= $category['owner_id'] ?>"
                        <?php if (in_array($category['category_id'], $productCategories)): ?>selected<?php endif; ?>>
                            <?= htmlspecialchars($category['name']) ?>
                            <?php if ($category['owner_id'] == 0): ?>
                                (Owner: System)
                            <?php elseif ($category['owner_id'] == $currentUserId): ?>
                                (Owner: You)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
         
            <!-- End Categories section -->

            <div class="mb-3">
                <label for="product_image">Product Image:</label><br>
                <input type="file" class="form-control" name="file[]" multiple name="product_images" id="fileToUpload"
                    multiple />
            </div>
            <?php if (!empty($productImages)): ?>
                <div class="mb-3">
                    <h4>Current Images:</h4>
                    <?php foreach ($productImages as $image): ?>
                        <div class="image-checkbox">
                            <img src="../products/product-<?= $productId ?>/product-<?= $productId ?>-image/<?= $image['image'] ?>"
                                alt="Product Image" width="200">
                            <input type="checkbox" name="images_to_delete[]" value="<?= $image['p_imgeid'] ?>"> Delete
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>


            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>
    <script>
        $(document).ready(function () {
            const choices = new Choices('#categories', {
                removeItemButton: true,
                searchEnabled: true,
                maxItemCount: null,
                shouldSort: true
            });
            // Function to filter categories based on checkboxes
            function filterCategories() {
                const showUser = $('#filter_by_user').is(':checked');
                const showSystem = $('#filter_by_system').is(':checked');
                $('#categories option').each(function () {
                    const ownerId = $(this).data('owner');
                    const isUserOwned = ownerId == <?= $currentUserId ?>;
                    const isSystemOwned = ownerId == 0;

                    // Show or hide based on checkbox status and owner
                    if ((showUser && isUserOwned) || (showSystem && isSystemOwned) || (!showUser && !showSystem)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                   // Refresh Choices.js to update the display
                   choices.destroy();
                 new Choices('#categories', {
                    removeItemButton: true,
                    searchEnabled: true,
                    maxItemCount: null,
                    shouldSort: true
                });
            }

            // Initial filtering on page load
            filterCategories();

            // Event handlers for checkboxes
            $('#filter_by_user, #filter_by_system').change(function () {
                filterCategories();
            });
        });
    </script>

</body>

</html>
