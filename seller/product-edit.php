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


?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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

        .mb-3 input,
        .mb-3 textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        .mb-3 button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .mb-3 button:hover {
            background-color: #0069d9;
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
            <div class="mb-3">
                <label for="category">Category:</label>
                <input type="text" class="form-control" id="category" name="category"
                    value="<?= htmlspecialchars($product['category']) ?>" required>
            </div>


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
</body>

</html>