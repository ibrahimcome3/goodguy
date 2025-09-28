<?php
<?php
require_once "includes.php"; // Your main includes file for DB connection etc.

// 1. Get the collection slug from the URL, and sanitize it.
$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>No collection specified.</p>";
    exit;
}

// 2. Find the collection details from the database using the slug.
$stmt = $pdo->prepare("SELECT collection_id, name, description FROM collections WHERE slug = ?");
$stmt->execute([$slug]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>The collection you're looking for does not exist.</p>";
    exit;
}

// 3. Fetch all products belonging to this collection.
// This is a simplified query; you can join more tables as you do in shop.php
$product_stmt = $pdo->prepare("
    SELECT p.productID, p.product_name, p.primary_image, i.price
    FROM productitem p
    JOIN inventoryitem i ON p.productID = i.productItemID
    WHERE p.collection_id = ? AND p.status = 'active'
    ORDER BY p.date_added DESC
");
$product_stmt->execute([$collection['collection_id']]);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($collection['name']) ?> - Good Guy</title>
    <!-- Include your site's main CSS and header here -->
    <link rel="stylesheet" href="path/to/your/main.css">
</head>
<body>
    <!-- Include your site's header/navbar here -->

    <div class="container" style="padding: 40px 15px;">
        
        <!-- Collection Header -->
        <div class="collection-header" style="text-align: center; margin-bottom: 40px;">
            <h1><?= htmlspecialchars($collection['name']) ?></h1>
            <?php if (!empty($collection['description'])): ?>
                <p class="lead"><?= htmlspecialchars($collection['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Product Grid -->
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php if (empty($products)): ?>
                <p style="grid-column: 1 / -1; text-align: center;">There are no products in this collection yet.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" style="border: 1px solid #eee; text-align: center;">
                        <a href="product.php?id=<?= $product['productID'] ?>">
                            <img src="<?= htmlspecialchars($product['primary_image'] ?? 'path/to/default-image.png') ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" style="max-width: 100%; height: auto;">
                            <h5 style="margin: 10px 0;"><?= htmlspecialchars($product['product_name']) ?></h5>
                        </a>
                        <p class="price" style="font-weight: bold;">$<?= number_format($product['price'], 2) ?></p>
                        <button>Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Include your site's footer here -->
</body>
</html>