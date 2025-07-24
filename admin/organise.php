<?php
// This file is included by add-product.php, which should have already
// included ../includes.php to provide the $pdo connection object.

// Fetch Categories
$categories = [];
try {
    // Using 'categories' table and 'category_id' for consistency with manage_categories.php
    // Fetching all categories is more appropriate for a product form.
    $stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real application, you would log this error.
    // error_log("Error fetching categories in organise.php: " . $e->getMessage());
    echo '<div class="text-danger">Could not fetch categories.</div>';
}

// Fetch Vendors (from supplier table, based on test.php)
$vendors = [];
try {
    $stmt = $pdo->query("SELECT sup_id, sup_company_name FROM supplier ORDER BY sup_company_name ASC");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // error_log("Error fetching vendors in organise.php: " . $e->getMessage());
    echo '<div class="text-danger">Could not fetch vendors.</div>';
}

// Fetch Brands
$brands = [];
try {
    // Assuming a 'brand' table with 'brandID' and 'Name' columns based on test.php
    $stmt = $pdo->query("SELECT brandID, Name FROM brand ORDER BY Name ASC");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // error_log("Error fetching brands in organise.php: " . $e->getMessage());
    echo '<div class="text-danger">Could not fetch brands.</div>';
}

// Fetch Return Policies
$return_policies = [];
try {
    // Assuming a 'shipping_policy' table based on test.php
    $stmt = $pdo->query("SELECT shipping_policy_id, shipping_policy FROM shipping_policy ORDER BY shipping_policy_id ASC");
    $return_policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // error_log("Error fetching return policies in organise.php: " . $e->getMessage());
    echo '<div class="text-danger">Could not fetch return policies.</div>';
}

?>
<div class="col-12 col-xl-4">
    <div class="row g-2">
        <div class="col-12 col-xl-12">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title mb-4">Organize</h4>
                    <div class="row gx-3">
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="mb-4">
                                <div class="d-flex flex-wrap mb-2">
                                    <h5 class="mb-0 text-body-highlight me-2">Category</h5>
                                    <a class="fw-bold fs-9" href="manage_categories.php">Add new category</a>
                                </div>
                                <select class="form-select mb-3" name="category" aria-label="category" required>
                                    <option value="">Select a category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No categories found</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="mb-4">
                                <div class="d-flex flex-wrap mb-2">
                                    <h5 class="mb-0 text-body-highlight me-2">Vendor</h5><a class="fw-bold fs-9"
                                        href="#!">Add new vendor</a>
                                </div>
                                <select class="form-select mb-3" name="vendor" aria-label="vendor" required>
                                    <option value="">Select a vendor</option>
                                    <?php if (!empty($vendors)): ?>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= htmlspecialchars($vendor['sup_id']) ?>">
                                                <?= htmlspecialchars($vendor['sup_company_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No vendors found</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="mb-4">
                                <div class="d-flex flex-wrap mb-2">
                                    <h5 class="mb-0 text-body-highlight me-2">Brand</h5><a class="fw-bold fs-9"
                                        href="new-brand.php">Add new brand</a>
                                </div>
                                <select class="form-select mb-3" name="brand" aria-label="brand" required>
                                    <option value="">Select a brand</option>
                                    <?php if (!empty($brands)): ?>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand['brandID']) ?>">
                                                <?= htmlspecialchars($brand['Name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No brands found</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="mb-4">
                                <h5 class="mb-2 text-body-highlight">Collection</h5>
                                <input class="form-control mb-xl-3" name="collection" type="text"
                                    placeholder="Collection">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="d-flex flex-wrap mb-2">
                                <h5 class="mb-0 text-body-highlight me-2">Tags</h5><a class="fw-bold fs-9 lh-sm"
                                    href="#!">View all tags</a>
                            </div>
                            <select class="form-select" name="tags" aria-label="tags">
                                <!-- This should also be dynamic, but for now it's hardcoded -->
                                <option value="men-cloth">Men's Clothing</option>
                                <option value="women-cloth">Womens's Clothing</option>
                                <option value="kid-cloth">Kid's Clothing</option>
                            </select>
                        </div>


                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-12 policy">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Return Policy</h4>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="pb-4">
                                <?php if (!empty($return_policies)): ?>
                                    <?php foreach ($return_policies as $policy): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_returns"
                                                id="returnPolicy<?= htmlspecialchars($policy['shipping_policy_id']) ?>"
                                                value="<?= htmlspecialchars($policy['shipping_policy_id']) ?>" required>
                                            <label class="form-check-label"
                                                for="returnPolicy<?= htmlspecialchars($policy['shipping_policy_id']) ?>">
                                                <?= htmlspecialchars($policy['shipping_policy']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>

        </div>
    </div>
</div>