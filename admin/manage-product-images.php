<?php
include "../includes.php";
require_once __DIR__ . '/../class/InventoryItem.php';
session_start();
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// get inventoryItemId from query
$inventoryItemId = isset($_GET['inventoryItemId'])
    ? (int) $_GET['inventoryItemId']
    : 0;

// instantiate and derive productId
$inventory = new InventoryItem($pdo);
$productId = $inventory->getProductIdForInventoryItem($inventoryItemId);

if (!$productId || !$inventoryItemId) {
    echo "<h3>Product or Inventory item not found.</h3>";
    exit;
}

// Handle image upload via InventoryItem methods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['images'])) {
    $results = $inventory->addInventoryItemImages($inventoryItemId, $_FILES['images']);
    // Redirect to prevent form resubmission
    header("Location: manage-product-images.php?inventoryItemId={$inventoryItemId}");
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

// Fetch product name for breadcrumbs/title
$product = $inventory->getProductById($productId);
$productName = $product ? $product['product_name'] : 'Unknown Product';

// Fetch inventory item details for a more descriptive title
$inventoryItem = $inventory->getInventoryItemById($inventoryItemId);
$inventoryItemDisplayName = $inventoryItem && !empty(trim($inventoryItem['description'])) ? $inventoryItem['description'] : "Item #" . $inventoryItemId;

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">

<head>
    <title>Manage Images for <?= htmlspecialchars($productName) ?></title>
    <?php include 'admin-header.php'; ?>
    <style>
        #image-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        #image-list li {
            margin: 0;
            position: relative;
            border: 1px solid #dcdcdc;
            border-radius: .5rem;
            overflow: hidden;
        }

        #image-list img {
            display: block;
            width: 120px;
            height: 120px;
            object-fit: cover;
        }

        .btn-delete {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(255, 0, 0, 0.7);
            border: none;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            padding: 0;
        }

        .primary-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: rgba(0, 128, 0, 0.8);
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="view-all-products.php">Products</a></li>
                    <li class="breadcrumb-item"><a
                            href="view-single-product.php?id=<?= $productId ?>"><?= htmlspecialchars($productName) ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Images</li>
                </ol>
            </nav>

            <div class="row g-3 flex-between-end mb-4">
                <div class="col-auto">
                    <h2 class="mb-2">Manage Images</h2>
                    <h5 class="text-body-tertiary fw-semibold">
                        For: <a
                            href="view-single-product.php?id=<?= $productId ?>#inventory-item-<?= $inventoryItemId ?>"
                            title="View this item on the product page">
                            <?= htmlspecialchars($inventoryItemDisplayName) ?></a>
                    </h5>
                </div>
                <div class="col-auto">
                    <a href="view-single-product.php?id=<?= $productId ?>"
                        class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0">
                        <span class="fas fa-arrow-left me-1"></span> Back to Product
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upload New Images</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label" for="images">Select images (multiple allowed)</label>
                            <input type="file" name="images[]" multiple accept="image/*" class="form-control"
                                id="images" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Images</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Images</h5>
                    <p class="text-body-tertiary fs-9 mb-0">Drag and drop to reorder images. The first image is the
                        primary one.</p>
                </div>
                <div class="card-body">
                    <?php if (empty($images)): ?>
                        <p class="text-center text-body-tertiary">No images have been uploaded for this item yet.</p>
                    <?php else: ?>
                        <ul id="image-list">
                            <?php foreach ($images as $img): ?>
                                <li data-id="<?= $img['inventory_item_image_id'] ?>">
                                    <?php if ($img['is_primary']): ?>
                                        <span class="badge bg-success primary-badge">Primary</span>
                                    <?php endif; ?>
                                    <img src="../<?= htmlspecialchars($img['image_path']) ?>"
                                        alt="<?= htmlspecialchars($img['image_name']) ?>">
                                    <button class="btn btn-sm btn-danger btn-delete" title="Delete Image">×</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

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
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/lodash/lodash.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/feather-icons/feather.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dayjs/dayjs.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

    <!-- SortableJS for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        new Sortable(document.getElementById('image-list'), {
            animation: 150,
            onEnd: function () {
                const order = Array.from(document.querySelectorAll('#image-list li'))
                    .map(li => li.dataset.id);

                fetch('update-image-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ inventoryItemId: <?= $inventoryItemId ?>, order })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to reflect the primary image change
                            location.reload();
                        } else {
                            console.error('Failed to reorder images:', data.error);
                            alert('Failed to reorder images. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error reordering images:', error);
                        alert('An error occurred. Please try again.');
                    });
            }
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const li = e.currentTarget.closest('li');
                if (!confirm('Are you sure you want to delete this image?')) return;

                fetch('delete-product-image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        inventoryItemId: <?= $inventoryItemId ?>,
                        imageId: li.dataset.id
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload to show updated primary image if necessary
                            location.reload();
                        } else {
                            alert(data.error || 'Failed to delete image.');
                        }
                    });
            });
        });
    </script>
</body>

</html>