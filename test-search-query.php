<?php
// filepath: c:\wamp64\www\goodguy\test-search-query.php
session_start();
require_once "includes.php";

// Function to display results in a readable format
function displayQueryResults($pdo, $query, $params = [], $title = "Query Results")
{
    echo "<h2>{$title}</h2>";

    try {
        $start = microtime(true); // Start timing
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $end = microtime(true); // End timing
        $time = round(($end - $start) * 1000, 2); // Convert to ms

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($results);

        echo "<div class='alert alert-info'>Query executed in {$time}ms and returned {$rowCount} rows.</div>";

        if ($rowCount > 0) {
            echo "<div class='table-responsive'>";
            echo "<table class='table table-bordered table-striped'>";

            // Headers
            echo "<thead><tr>";
            foreach (array_keys($results[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr></thead>";

            // Data rows
            echo "<tbody>";
            $displayLimit = min(20, $rowCount); // Limit to 20 rows display
            for ($i = 0; $i < $displayLimit; $i++) {
                echo "<tr>";
                foreach ($results[$i] as $value) {
                    echo "<td>" . htmlspecialchars($value ?? "NULL") . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            if ($rowCount > $displayLimit) {
                echo "<div class='alert alert-warning'>Showing first {$displayLimit} of {$rowCount} results.</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>No results found.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($query) . "</pre>";
    }
}

// Test search term
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchTermWildcard = "%" . strtolower($searchTerm) . "%";

// Test different versions of the query
$versions = [
    'count' => [
        'title' => 'Count Query',
        'query' => "SELECT COUNT(DISTINCT ii.InventoryItemID) as total_count
                    FROM inventoryitem ii
                    JOIN productitem pi ON ii.productItemID = pi.productID
                    LEFT JOIN product_categories pc ON pi.productID = pc.product_id
                    LEFT JOIN categories cat ON pc.category_id = cat.category_id
                    LEFT JOIN brand b ON pi.brand_id = b.brand_id
                    WHERE (LOWER(ii.barcode) LIKE :term_bc
                           OR LOWER(ii.description) LIKE :term_desc
                           OR LOWER(pi.product_name) LIKE :term_pn
                           OR LOWER(cat.name) LIKE :term_cat
                           OR LOWER(b.Name) LIKE :term_brand)",
        'params' => [
            ':term_bc' => $searchTermWildcard,
            ':term_desc' => $searchTermWildcard,
            ':term_pn' => $searchTermWildcard,
            ':term_cat' => $searchTermWildcard,
            ':term_brand' => $searchTermWildcard,
        ],
    ],
    'main_query' => [
        'title' => 'Main Search Query',
        'query' => "SELECT ii.InventoryItemID, ii.barcode, ii.description, ii.cost, ii.date_added,
                           pi.product_name, pi.productID as baseProductID, 
                           b.Name as brand_name, 
                           cat.name as category_name, 
                           cat.category_id as cat_id_for_link
                    FROM inventoryitem ii
                    JOIN productitem pi ON ii.productItemID = pi.productID
                    LEFT JOIN product_categories pc ON pi.productID = pc.product_id
                    LEFT JOIN categories cat ON pc.category_id = cat.category_id
                    LEFT JOIN brand b ON pi.brand_id = b.brand_id
                    WHERE (LOWER(ii.barcode) LIKE :term_bc
                           OR LOWER(ii.description) LIKE :term_desc
                           OR LOWER(pi.product_name) LIKE :term_pn
                           OR LOWER(cat.name) LIKE :term_cat
                           OR LOWER(b.Name) LIKE :term_brand)
                    GROUP BY ii.InventoryItemID, pi.product_name, b.Name, cat.name, cat.category_id
                    ORDER BY ii.date_added DESC
                    LIMIT 20",
        'params' => [
            ':term_bc' => $searchTermWildcard,
            ':term_desc' => $searchTermWildcard,
            ':term_pn' => $searchTermWildcard,
            ':term_cat' => $searchTermWildcard,
            ':term_brand' => $searchTermWildcard,
        ],
    ],
    'schema_check' => [
        'title' => 'Database Schema Check',
        'query' => "SELECT 
                        column_name, 
                        data_type, 
                        is_nullable
                    FROM 
                        information_schema.columns
                    WHERE 
                        table_name = :table_name
                    ORDER BY 
                        ordinal_position",
        'params' => []  // Will be populated if a table is selected
    ],
    'relationship_check' => [
        'title' => 'Testing Join Relationships',
        'query' => "SELECT 
                        pi.productID,
                        pi.product_name,
                        COUNT(ii.InventoryItemID) as inventory_count,
                        COUNT(DISTINCT pc.category_id) as categories_count,
                        GROUP_CONCAT(DISTINCT cat.name SEPARATOR ', ') as category_names
                    FROM 
                        productitem pi
                    LEFT JOIN inventoryitem ii ON ii.productItemID = pi.productID
                    LEFT JOIN product_categories pc ON pi.productID = pc.product_id
                    LEFT JOIN categories cat ON pc.category_id = cat.category_id
                    GROUP BY 
                        pi.productID
                    LIMIT 20",
        'params' => [],
    ],
];

// Check if we're testing a specific table's schema
if (isset($_GET['table']) && !empty($_GET['table'])) {
    $versions['schema_check']['params'] = [':table_name' => $_GET['table']];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Query Testing for Product Search</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
        }

        .query-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }

        .table-responsive {
            margin-bottom: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <h1>SQL Query Testing for Product Search</h1>

        <div class="row">
            <div class="col-md-6">
                <form action="" method="GET" class="form">
                    <div class="form-group">
                        <label for="q">Search Term:</label>
                        <input type="text" name="q" id="q" class="form-control"
                            value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Enter search term...">
                    </div>
                    <button type="submit" class="btn btn-primary">Test Search</button>
                </form>
            </div>

            <div class="col-md-6">
                <form action="" method="GET" class="form">
                    <div class="form-group">
                        <label for="table">Check Table Schema:</label>
                        <select name="table" id="table" class="form-control">
                            <option value="">Select a table</option>
                            <option value="inventoryitem" <?= isset($_GET['table']) && $_GET['table'] == 'inventoryitem' ? 'selected' : '' ?>>inventoryitem</option>
                            <option value="productitem" <?= isset($_GET['table']) && $_GET['table'] == 'productitem' ? 'selected' : '' ?>>productitem</option>
                            <option value="categories" <?= isset($_GET['table']) && $_GET['table'] == 'categories' ? 'selected' : '' ?>>categories</option>
                            <option value="product_categories" <?= isset($_GET['table']) && $_GET['table'] == 'product_categories' ? 'selected' : '' ?>>product_categories</option>
                            <option value="brand" <?= isset($_GET['table']) && $_GET['table'] == 'brand' ? 'selected' : '' ?>>brand</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Check Schema</button>
                </form>
            </div>
        </div>

        <?php if (!empty($searchTerm) || isset($_GET['table'])): ?>
            <div class="alert alert-primary mt-3">
                <?php if (!empty($searchTerm)): ?>
                    Testing search for: <strong><?= htmlspecialchars($searchTerm) ?></strong>
                <?php endif; ?>
                <?php if (isset($_GET['table']) && !empty($_GET['table'])): ?>
                    <?= empty($searchTerm) ? '' : ' | ' ?>Checking schema for table:
                    <strong><?= htmlspecialchars($_GET['table']) ?></strong>
                <?php endif; ?>
            </div>

            <?php if (!empty($searchTerm)): ?>
                <div class="query-box">
                    <h3>Search Term Processing</h3>
                    <p><strong>Original:</strong> <?= htmlspecialchars($searchTerm) ?></p>
                    <p><strong>Lowercase with wildcards:</strong> <?= htmlspecialchars($searchTermWildcard) ?></p>
                </div>
            <?php endif; ?>

            <?php
            // Execute relevant queries
            foreach ($versions as $key => $testCase) {
                // Skip schema check if no table specified and not a search test
                if ($key === 'schema_check' && (!isset($_GET['table']) || empty($_GET['table']))) {
                    continue;
                }

                // Skip search queries if no search term
                if (empty($searchTerm) && ($key === 'count' || $key === 'main_query')) {
                    continue;
                }

                echo "<div class='query-box'>";
                echo "<h4>" . htmlspecialchars($testCase['title']) . "</h4>";
                echo "<pre>" . htmlspecialchars($testCase['query']) . "</pre>";
                displayQueryResults($pdo, $testCase['query'], $testCase['params'] ?? []);
                echo "</div>";
            }
            ?>

        <?php else: ?>
            <div class="alert alert-info mt-4">
                Enter a search term or select a table to check its schema.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>