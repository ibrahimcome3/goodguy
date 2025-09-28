<?php
// 1. BOOTSTRAP
// =================================
include "../includes.php";
require_once __DIR__ . '/../class/InventoryItem.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['inventoryItemId'])) {
    die("Inventory item ID not provided.");
}

$inventoryItemId = (int) $_GET['inventoryItemId'];

$inventoryItemObj = new InventoryItem($pdo);

// Fetch SKU and product ID from the database
$inventoryItem = $inventoryItemObj->getInventoryItemById($inventoryItemId);
if (!$inventoryItem) {
    die("Inventory item not found.");
}

$skuJson = $inventoryItem['sku'];
$productId = $inventoryItem['productItemID'];
$skuArray = json_decode($skuJson, true) ?: [];

// --- POST HANDLERS ---

/**
 * Creates a safely quoted JSON path string for use in functions like JSON_REMOVE.
 * @param PDO $pdo The PDO connection object.
 * @param string $key The key to be included in the path.
 * @return string The quoted JSON path (e.g., '$."my-key"').
 */
function get_safe_json_path(PDO $pdo, string $key): string
{
    // For JSON functions, path arguments must be string literals.
    // We build the path string safely by quoting it.
    return $pdo->quote('$.' . $key);
}

/**
 * Handles updating the SKU properties for the current item.
 */
function handleUpdateSku(PDO $pdo, int $inventoryItemId, int $productId): void
{
    $properties = $_POST['properties'] ?? [];
    $deleteProperties = $_POST['delete_properties'] ?? [];
    $deleteFromOthers = $_POST['delete_from_others'] ?? [];
    $newKey = trim($_POST['new_property_key'] ?? '');
    $newValue = trim($_POST['new_property_value'] ?? '');

    // If a property is marked for deletion from all variants, it implies
    // it should also be deleted from the current item.
    $propertiesToDeleteForCurrent = array_unique(array_merge($deleteProperties, $deleteFromOthers));

    // 1. Build the new SKU array for the current item
    $updatedSku = [];
    foreach ($properties as $key => $value) {
        if (!in_array($key, $propertiesToDeleteForCurrent)) {
            $updatedSku[trim($key)] = trim($value);
        }
    }

    // 2. Add new property if provided
    if (!empty($newKey) && !array_key_exists($newKey, $updatedSku)) {
        $updatedSku[$newKey] = $newValue;
    }

    // 3. Update the current item's SKU
    $updatedSkuJson = json_encode($updatedSku);
    $stmt = $pdo->prepare("UPDATE inventoryitem SET sku = ? WHERE inventoryitemID = ?");
    $stmt->execute([$updatedSkuJson, $inventoryItemId]);

    // 4. Handle deleting properties from other variants
    if (!empty($deleteFromOthers)) {
        $jsonPaths = array_map(fn($key) => get_safe_json_path($pdo, $key), $deleteFromOthers);
        $jsonPathsString = implode(', ', $jsonPaths);

        // Note: The paths for JSON_REMOVE cannot be bound parameters.
        // We are constructing the SQL string, but using a helper to quote each path makes it safe.
        $sql = "UPDATE inventoryitem SET sku = JSON_REMOVE(sku, {$jsonPathsString}) WHERE productItemID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
    }
    $_SESSION['success_message'] = "SKU properties updated successfully.";
}

/**
 * Handles sharing a property with other items.
 */
function handleShareProperty(PDO $pdo, array $currentSku, int $productId): void
{
    $propertyToShare = trim($_POST['share_property'] ?? '');
    $propertyValue = trim($_POST['share_property_value'] ?? '');
    $itemsToShareWith = $_POST['share_with_items'] ?? [];

    if (empty($propertyToShare) || empty($itemsToShareWith)) {
        return;
    }

    // If no value is provided for the property to share, use the value from the current item.
    if ($propertyValue === '' && isset($currentSku[$propertyToShare])) {
        $propertyValue = $currentSku[$propertyToShare];
    }

    // Use JSON_MERGE_PATCH to add/update the property for all selected items
    $placeholders = implode(',', array_fill(0, count($itemsToShareWith), '?'));
    $sql = "UPDATE inventoryitem SET sku = JSON_MERGE_PATCH(sku, JSON_OBJECT(?, ?)) WHERE inventoryitemID IN ({$placeholders}) AND productItemID = ?";

    $params = array_merge([$propertyToShare, $propertyValue], $itemsToShareWith, [$productId]);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $_SESSION['success_message'] = "Property '{$propertyToShare}' shared successfully.";
}

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;

    if ($action === 'update' || !isset($action) /* Fallback for original form */) {
        handleUpdateSku($pdo, $inventoryItemId, $productId);
    } elseif ($action === 'share') {
        handleShareProperty($pdo, $skuArray, $productId);
    }

    // Redirect to the same page to see changes and prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?inventoryItemId=" . $inventoryItemId);
    exit();
}

// --- DATA FETCHING FOR PAGE RENDER ---

// Get other inventory items for the same product to display in the "share" section
$otherInventoryItemDetails = $inventoryItemObj->getOtherInventoryItemsForProduct($productId, $inventoryItemId);
// FIX: Corrected the method call by removing the redundant $pdo argument.
$primaryImage = $inventoryItemObj->get_product_image($inventoryItemId);

// --- HELPER FUNCTIONS FOR RENDERING ---

/**
 * Filters out 'color' and 'size' to get only dynamic properties.
 */
function get_dynamic_properties(array $sku): array
{
    return array_filter($sku, fn($key) => !in_array($key, ['color', 'size']), ARRAY_FILTER_USE_KEY);
}

/**
 * Renders a single property row in the SKU table.
 */
function render_property_row(string $key, string $value, bool $is_dynamic = true): void
{
    $key_esc = htmlspecialchars($key);
    $value_esc = htmlspecialchars($value);
    $type = ($key === 'color') ? 'color' : 'text';
    $input_class = ($key === 'color') ? 'form-control form-control-color' : 'form-control';

    echo '<tr>';
    echo '<td class="align-middle">' . ucfirst($key_esc) . '</td>';
    echo "<td><input type='{$type}' name='properties[{$key_esc}]' value='{$value_esc}' class='{$input_class}' " . (!$is_dynamic ? 'required' : '') . '></td>';

    if ($is_dynamic) {
        echo '<td class="align-middle">';
        echo '<div class="form-check">';
        echo "<input class='form-check-input' type='checkbox' name='delete_properties[]' value='{$key_esc}' id='delete_{$key_esc}'>";
        echo "<label class='form-check-label small' for='delete_{$key_esc}'>Delete</label>";
        echo '</div>';
        echo '<div class="form-check">';
        echo "<input class='form-check-input' type='checkbox' name='delete_from_others[]' value='{$key_esc}' id='delete_others_{$key_esc}'>";
        echo "<label class='form-check-label small' for='delete_others_{$key_esc}'>...from all variants</label>";
        echo '</div>';
        echo '</td>';
    } else {
        echo '<td class="align-middle"><small class="text-body-tertiary">Required</small></td>';
    }
    echo '</tr>';
}

/**
 * Renders a card for another inventory item for the sharing UI.
 */
function render_share_item_card(int $otherItemId, array $otherDetails): void
{
    $imagePath = $otherDetails['image_path'] ? "../" . htmlspecialchars($otherDetails['image_path']) : 'assets/img/icons/spot-illustrations/image.svg';
    $link = "manage-sku-properties.php?inventoryItemId=" . $otherItemId;
    ?>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <a href="<?= $link ?>">
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="Inventory Item Image"
                    class="card-img-top inventory-item-image p-2" style="object-fit: contain; height: 120px;">
            </a>
            <div class="card-body text-center p-2">
                <a href="<?= $link ?>" class="text-body-emphasis fw-semibold text-decoration-none">
                    <h6 class="mb-0">ID: <?= $otherItemId ?></h6>
                </a>
            </div>
            <div class="card-footer bg-body-tertiary text-center pt-1 pb-1 border-top-0">
                <div class="form-check d-inline-block">
                    <input class="form-check-input share-item-checkbox" type="checkbox" name="share_with_items[]"
                        id="share_with_<?= $otherItemId ?>" value="<?= $otherItemId ?>">
                    <label class="form-check-label small" for="share_with_<?= $otherItemId ?>">Share with this</label>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Manage SKU Properties</title>
    <?php include 'admin-header.php'; ?>
    <style>
        .inventory-item-image {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content mt-5">
            <div class="container-small">
                <?php
                // IMPROVEMENT: Display success message from session
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['success_message']);
                }
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <?php if ($primaryImage): ?>
                                <img src="../<?= htmlspecialchars($primaryImage) ?>" alt="Current Inventory Item"
                                    class="me-3 rounded"
                                    style="width: 80px; height: 80px; object-fit: contain; border: 1px solid #dee2e6;">
                            <?php endif; ?>
                            <div>
                                <h2 class="mb-0">Manage SKU Properties</h2>
                                <p class="mb-0 text-body-tertiary">For Inventory Item ID: <?= $inventoryItemId ?></p>
                            </div>
                        </div>
                        <form id="update-form" method="post"
                            action="manage-sku-properties.php?inventoryItemId=<?= $inventoryItemId ?>">
                            <input type="hidden" name="action" value="update">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Value</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    render_property_row('color', $skuArray['color'] ?? '', false);
                                    render_property_row('size', $skuArray['size'] ?? '', false);
                                    foreach (get_dynamic_properties($skuArray) as $key => $value) {
                                        render_property_row($key, $value, true);
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <h3>Add New Property</h3>
                            <div class="mb-3">
                                <label for="new_property_key">Property Key:</label>
                                <input type="text" class="form-control" id="new_property_key" name="new_property_key">
                            </div>
                            <div class="mb-3">
                                <label for="new_property_value">Property Value:</label>
                                <input type="text" class="form-control" id="new_property_value"
                                    name="new_property_value">
                            </div>
                            <button type="submit" class="btn btn-primary">Update
                                SKU</button>
                        </form>
                        <hr class="my-4">
                        <form id="share-form" method="post"
                            action="manage-sku-properties.php?inventoryItemId=<?= $inventoryItemId ?>">
                            <input type="hidden" name="action" value="share">
                            <h3 class="mt-4">Share Property with Other Items</h3>
                            <div class="mb-3">
                                <label for="share_property">Property to Share:</label>
                                <select class="form-control" id="share_property" name="share_property">
                                    <option value="">Select a property</option>
                                    <?php foreach (get_dynamic_properties($skuArray) as $key => $value): ?>
                                        <option value="<?= htmlspecialchars($key) ?>"
                                            data-value="<?= htmlspecialchars($value) ?>">
                                            <?= htmlspecialchars(ucfirst($key)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="share_property_value">Value to Share (auto-filled from selection):</label>
                                <input type="text" class="form-control" id="share_property_value"
                                    name="share_property_value">
                            </div>
                            <div class="mb-3">
                                <p>Share with these items:</p>
                                <?php if (!empty($otherInventoryItemDetails)): ?>
                                    <div class="row">
                                        <?php foreach ($otherInventoryItemDetails as $otherItemId => $otherDetails):
                                            render_share_item_card($otherItemId, $otherDetails);
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p>No other inventory items found for this product.</p>
                                <?php endif; ?>
                            </div>
                            <button type="submit" id="share-button" class="btn btn-secondary" disabled>Share
                                Property</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sharePropertySelect = document.getElementById('share_property');
            const shareValueInput = document.getElementById('share_property_value');
            const shareButton = document.getElementById('share-button');
            const shareCheckboxes = document.querySelectorAll('.share-item-checkbox');

            function updateShareButtonState() {
                const propertySelected = sharePropertySelect.value !== '';
                const itemsSelected = document.querySelectorAll('.share-item-checkbox:checked').length > 0;
                shareButton.disabled = !(propertySelected && itemsSelected);
            }

            sharePropertySelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                shareValueInput.value = selectedOption.dataset.value || '';
                updateShareButtonState();
            });

            shareCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateShareButtonState);
            });

            // Initial check
            updateShareButtonState();
        });
    </script>
</body>

</html>