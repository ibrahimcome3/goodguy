<?php
// filepath: c:\wamp64\www\goodguy\admin\product_category_migration.php
session_start();
require_once "../includes.php";

// Check if user is logged in as admin
if (empty($_SESSION['admin_id'])) {
    die('Unauthorized access. Please log in as admin.');
}

/**
 * Assigns random categories to products that don't have any assigned category in the product_categories table.
 */
function assignRandomCategories($pdo)
{
    try {
        // Start transaction for data integrity
        $pdo->beginTransaction();

        // Get all available categories
        $categoryQuery = "SELECT category_id FROM categories WHERE active = 1";
        $categoryStmt = $pdo->query($categoryQuery);
        $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($categories)) {
            throw new Exception("No active categories found in the database.");
        }

        // Get all products that don't have any category assignment yet
        $query = "SELECT p.productID FROM productitem p 
                 LEFT JOIN product_categories pc ON p.productID = pc.product_id 
                 WHERE pc.product_id IS NULL";
        $stmt = $pdo->query($query);
        $productsWithoutCategory = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Prepare the insert query
        $insertStmt = $pdo->prepare("
            INSERT INTO product_categories (product_id, category_id) 
            VALUES (?, ?)
        ");

        $totalProducts = count($productsWithoutCategory);
        $assignedCount = 0;
        $errors = [];

        // Process each product
        foreach ($productsWithoutCategory as $productId) {
            try {
                // Randomly select a category
                $randomCategoryId = $categories[array_rand($categories)];

                // Assign the random category to the product
                $insertStmt->execute([$productId, $randomCategoryId]);
                $assignedCount++;
            } catch (PDOException $e) {
                $errors[] = "Error assigning category to product ID $productId: " . $e->getMessage();
            }
        }

        // Commit the transaction
        $pdo->commit();

        return [
            'total' => $totalProducts,
            'assigned' => $assignedCount,
            'errors' => $errors,
            'categories_used' => count($categories)
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// Process the random assignment if confirmed
$result = null;
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

if ($confirmed) {
    try {
        $result = assignRandomCategories($pdo);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Random Category Assignment</title>
    <?php include 'admin-header.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include 'includes/admin_navbar.php'; ?>
        <div class="content">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Random Category Assignment Tool</h5>
                </div>
                <div class="card-body">
                    <?php if (!$confirmed): ?>
                        <div class="alert alert-warning">
                            <h6>Important!</h6>
                            <p>This tool will randomly assign categories to products that don't have any category
                                in the <code>product_categories</code> junction table.</p>
                            <p>Since the <code>category</code> column has been deleted from <code>productitem</code>,
                                this is necessary to ensure all products appear in category-based filtering.</p>
                            <p>Each product without a category will be assigned one random category from your active
                                categories.</p>
                        </div>

                        <form method="post" class="mb-3">
                            <input type="hidden" name="confirm" value="yes">
                            <button type="submit" class="btn btn-primary">Assign Random Categories</button>
                        </form>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger">
                            <h6>Error</h6>
                            <p><?= htmlspecialchars($error) ?></p>
                            <a href="product_category_migration.php" class="btn btn-sm btn-outline-danger mt-2">Try
                                Again</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <h6>Category Assignment Complete</h6>
                            <p>Successfully processed random category assignments.</p>
                            <ul>
                                <li>Products without categories found: <strong><?= $result['total'] ?></strong></li>
                                <li>Categories successfully assigned: <strong><?= $result['assigned'] ?></strong></li>
                                <li>Number of active categories used: <strong><?= $result['categories_used'] ?></strong>
                                </li>
                            </ul>
                        </div>

                        <?php if (!empty($result['errors'])): ?>
                            <div class="alert alert-warning mt-3">
                                <h6>Warnings</h6>
                                <p>The following errors occurred during processing:</p>
                                <ul>
                                    <?php foreach ($result['errors'] as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <a href="product_category_migration.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include 'includes/admin_footer.php'; ?>
    </main>
</body>

</html>