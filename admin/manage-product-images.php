<?php

include "../includes.php";
include "../class/InventoryItem.php";

session_start();
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$inventoryItemId = isset($_GET['inventoryItemId']) ? (int) $_GET['inventoryItemId'] : 0;

if (!$productId || !$inventoryItemId) {
    echo "<h3>Product or Inventory item not found.</h3>";
    exit;
}

$inventory = new InventoryItem($pdo);

// Handle image upload via InventoryItem methods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['images'])) {
    $results = $inventory->addInventoryItemImages($inventoryItemId, $_FILES['images']);
    // Optionally inspect $results for errors
    header("Location: manage-product-images.php?id={$productId}&inventoryItemId={$inventoryItemId}");
    exit;
}

// Fetch images for this inventory item
$stmtImg = $pdo->prepare("
    SELECT 
        inventory_item_image_id,
        image_name,
        image_path,
        is_primary
    FROM inventory_item_image
    WHERE inventory_item_id = ?
    ORDER BY is_primary DESC, sort_order ASC
");
$stmtImg->execute([$inventoryItemId]);
$images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Images – <?= htmlspecialchars($productId) ?> / Inventory <?= htmlspecialchars($inventoryItemId) ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #image-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
        }

        #image-list li {
            margin: .5rem;
            position: relative;
        }

        #image-list img {
            display: block;
            width: 120px;
            height: 120px;
            object-fit: cover;
        }

        .btn-delete {
            position: absolute;
            top: 2px;
            right: 2px;
        }
    </style>
</head>

<body class="p-4">
    <h2>Manage Images for InventoryItem #<?= $inventoryItemId ?></h2>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <input type="file" name="images[]" multiple accept="image/*" class="form-control mb-2">
        <button type="submit" class="btn btn-primary">Upload Images</button>
    </form>
    <ul id="image-list">
        <?php foreach ($images as $img): ?>
            <li data-id="<?= $img['inventory_item_image_id'] ?>">
                <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['image_name']) ?>">
                <button class="btn btn-sm btn-danger btn-delete">×</button>
            </li>
        <?php endforeach; ?>
    </ul>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        new Sortable(document.getElementById('image-list'), {
            animation: 150,
            onEnd() {
                const order = Array.from(document.querySelectorAll('#image-list li'))
                    .map(li => li.dataset.id);
                fetch('update-image-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ inventoryItemId: <?= $inventoryItemId ?>, order })
                });
            }
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', e => {
                const li = e.currentTarget.closest('li');
                if (!confirm('Delete this image?')) return;
                fetch('delete-product-image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        inventoryItemId: <?= $inventoryItemId ?>,
                        imageId: li.dataset.id
                    })
                }).then(() => li.remove());
            });
        });
    </script>
</body>