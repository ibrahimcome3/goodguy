<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "../includes.php"; // Provides $pdo

$inventoryItemId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$productItemId = null;
$message = "Inventory item added successfully!";
$message_type = "success";

if ($inventoryItemId) {
    try {
        // Fetch the productItemID for the "Add Another Variant" link
        $stmt = $pdo->prepare("SELECT productItemID FROM inventoryitem WHERE InventoryItemID = :inventory_item_id");
        $stmt->bindParam(':inventory_item_id', $inventoryItemId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result['productItemID'])) {
            $productItemId = $result['productItemID'];
        } else {
            $message = "Inventory item added, but could not retrieve product details for adding another variant.";
            $message_type = "warning";
        }
    } catch (PDOException $e) {
        error_log("Error fetching productItemID in success.php: " . $e->getMessage());
        $message = "Inventory item added, but an error occurred while fetching product details.";
        $message_type = "warning";
    }
} else {
    $message = "Action completed, but no specific item ID was provided.";
    $message_type = "info";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            flex: 1;
        }

        .button-container a {
            margin: 0 10px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; // Assuming you have a common navbar for the admin area ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="alert alert-<?= htmlspecialchars($message_type) ?>" role="alert">
                    <h4 class="alert-heading">Success!</h4>
                    <p><?= htmlspecialchars($message) ?></p>
                    <?php if ($inventoryItemId): ?>
                        <p>Inventory Item ID: <strong><?= htmlspecialchars($inventoryItemId) ?></strong></p>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div class="button-container">
                    <?php if ($productItemId): ?>
                        <a href="add-product-varient.php?product_id=<?= htmlspecialchars($productItemId) ?>"
                            class="btn btn-info">Add Another Variant to This Product</a>
                    <?php endif; ?>
                    <a href="add-product.php" class="btn btn-primary">Add a New Product</a>
                    <a href="seller-dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>