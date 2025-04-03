<?php
// view_inventory.php

session_start();
include "../conn.php"; // Include to have access to $pdo
require_once '../class/User.php';
require_once '../class/Seller.php';
require_once '../class/InventoryItem.php';

// Check if a user is logged in
if (!isset($_SESSION['uid'])) {
    header("Location: ../login.php");
    exit;
}

// Get user data
$u = new User();
$userId = $_SESSION['uid'];
$user = $u->getUserById($mysqli, $userId);

// Check if the user is an approved seller
if ($user['vendor_status'] != 'approved') {
    header("Location: ../index.php");
    exit;
}

// Get the product ID from the query string
$productId = $_GET["product_id"] ?? null;
if ($productId === null) {
    header("Location: seller-dashboard.php");
    exit;
}

// Create an InventoryItem object with the PDO connection
$inventory = new InventoryItem($pdo);

// Get the inventory items for the product
$inventoryItems = $inventory->getInventoryItemsByProductId($productId);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Inventory Items</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <h2>Inventory Items</h2>
        <a href="seller-dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <a href="add_inventory.php?product_id=<?= $productId ?>" class="btn btn-success">Add New Inventory</a>

        <?php if (empty($inventoryItems)): ?>
            <div class="alert alert-info mt-3" role="alert">
                No inventory items found for this product.
            </div>
        <?php else: ?>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Reorder Quantity</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th>SKU Code</th>
                        <th>Delivery Date</th>
                        <th>Tax</th>
                        <th>Discount</th>
                        <th>Created At</th>
                        <th>Actions</th>
                        <!-- Add more headers if you have more columns in your inventoryitem table -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryItems as $inventoryItem): ?>
                        <tr id="inventory-row-<?= $inventoryItem["InventoryItemID"] ?>">
                            <td><?= htmlspecialchars($inventoryItem["InventoryItemID"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["description"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["quantityOnHand"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["cost"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["reorderQuantity"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["sku"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["barcode"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["sku_code"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["delivery_date"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["tax"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["discount"]) ?></td>
                            <td><?= htmlspecialchars($inventoryItem["date_added"]) ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm edit-inventory-button"
                                    data-inventory-id="<?= $inventoryItem["InventoryItemID"] ?>">Edit</button>
                                <button type="button" class="btn btn-danger btn-sm delete-inventory-button"
                                    data-inventory-id="<?= $inventoryItem["InventoryItemID"] ?>">Delete</button>
                            </td>
                            <!-- Add more cells (<td>) if you have more columns -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Edit button click
            $(".edit-inventory-button").click(function () {
                alert("Edit button clicked");
                const inventoryId = $(this).data("inventory-id");
                window.location.href = `../admin/edit_inventory_item.php?id=${inventoryId}`;
            });

            // Delete button click
            $(".delete-inventory-button").click(function (event) {
                event.preventDefault();
                const inventoryId = $(this).data("inventory-id");

                if (confirm("Are you sure you want to delete this inventory item?")) {
                    $.ajax({
                        url: "edit_inventory.php",
                        type: "POST",
                        data: {
                            action: "delete_inventory",
                            inventory_id: inventoryId
                        },
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                $("#inventory-row-" + inventoryId).remove();
                                alert(response.message);
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert("Error deleting inventory: " + error);
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>