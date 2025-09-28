<?php
// removed require_once "../db_conncection/conn.php";
// Go up one level from the current file's directory

require_once __DIR__ . '/ProductItem.php';

class InventoryItem extends ProductItem
{
    private $timestamp;
    protected $pdo; // Store the PDO connection here
    public $discount_percent;
    public $cost;

    public function __construct($pdo)
    {

        $this->pdo = $pdo; // Store the PDO connection
        $defaultTimeZone = 'UTC';
        date_default_timezone_set($defaultTimeZone);
        $this->timestamp = date('Y-m-d');
    }


    /**
     * Uploads multiple images for an inventory item.
     *
     * @param int   $inventoryItemId
     * @param array $files           $_FILES['images']
     * @return array                 An array of results from addInventoryItemImage()
     */
    public function addInventoryItemImages(int $inventoryItemId, array $files): array
    {
        $results = [];
        foreach ($files['tmp_name'] as $index => $tmpPath) {
            $file = [
                'tmp_name' => $tmpPath,
                'name' => $files['name'][$index],
                'error' => $files['error'][$index]
            ];
            // mark the first uploaded file as primary
            $isPrimary = ($index === 0);
            $results[] = $this->addInventoryItemImage($inventoryItemId, $file, $isPrimary);
        }
        return $results;
    }

    /**
     * Uploads a single image for an inventory item (creates dirs, moves file, inserts DB record),
     * then converts and resizes the image.
     */
    public function addInventoryItemImage(int $inventoryItemId, array $file, bool $isPrimary = false): array
    {
        // determine product ID
        $productId = $this->getProductIdForInventoryItem($inventoryItemId);

        // prepare directories
        $basePath = "../products/product-{$productId}/inventory-{$productId}-{$inventoryItemId}/";
        $resizedDir = $basePath . "resized/";
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        if (!is_dir($resizedDir)) {
            mkdir($resizedDir, 0755, true);
        }

        // check upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => "Upload error code {$file['error']}"];
        }

        // move original file
        $filename = round(microtime(true)) . '_' . basename($file['name']);
        $target = $basePath . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['error' => "Failed to move {$file['name']}"];
        }

        // convert quality if needed
        $this->convertImage1($target, $target, 100);

        // resize to a max of 600×600 and save in 'resized' folder
        $resizedFilename = pathinfo($filename, PATHINFO_FILENAME) . ".jpg";
        $resizedTarget = $resizedDir . $resizedFilename;
        $this->resize_image($target, 600, 600, $resizedTarget);

        // insert DB record using resized image path
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory_item_image 
                (inventory_item_id, image_name, image_path, is_primary)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $inventoryItemId,
            $filename,
            ltrim($resizedTarget, './'),
            $isPrimary ? 1 : 0
        ]);

        return [
            'filename' => $filename,
            'original' => $target,
            'resized' => $resizedTarget,
            'is_primary' => $isPrimary ? 1 : 0
        ];
    }

    /**
     * Deletes a single image for an inventory item.
     * Removes the database record and the physical files (original and resized).
     * If the primary image is deleted, it promotes the next available image.
     *
     * @param int $inventoryItemId The ID of the inventory item.
     * @param int $imageId         The ID of the inventory item image.
     * @return array               An array with a success or error message.
     */
    public function deleteInventoryItemImage(int $inventoryItemId, int $imageId): array
    {
        // 1. Get image details from the database
        $stmt = $this->pdo->prepare("
            SELECT image_path, image_name, is_primary
            FROM inventory_item_image
            WHERE inventory_item_image_id = ? AND inventory_item_id = ?
        ");
        $stmt->execute([$imageId, $inventoryItemId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            return ['error' => 'Image not found or does not belong to this inventory item.'];
        }

        $isPrimary = (int) $image['is_primary'] === 1;
        $resizedPath = '../' . $image['image_path'];
        $baseDir = dirname(dirname($resizedPath));
        $originalPath = $baseDir . '/' . $image['image_name'];

        // 2. Delete physical files
        if (file_exists($resizedPath)) {
            @unlink($resizedPath);
        }
        if (file_exists($originalPath)) {
            @unlink($originalPath);
        }

        // 3. Delete the database record
        $stmt = $this->pdo->prepare("DELETE FROM inventory_item_image WHERE inventory_item_image_id = ?");
        $stmt->execute([$imageId]);

        // 4. If the primary image was deleted, promote the next one
        if ($isPrimary) {
            $this->pdo->query("UPDATE inventory_item_image SET is_primary = 1 WHERE inventory_item_id = {$inventoryItemId} ORDER BY sort_order ASC, inventory_item_image_id ASC LIMIT 1");
        }

        return ['success' => true, 'message' => 'Image deleted successfully.'];
    }

    /**
     * Updates the sort order of images for an inventory item and sets the primary image.
     *
     * @param int   $inventoryItemId The ID of the inventory item.
     * @param array $imageOrder      An array of inventory_item_image_id's in the desired order.
     * @return array                 An array with a success or error message.
     */
    public function updateInventoryItemImageOrder(int $inventoryItemId, array $imageOrder): array
    {
        if (empty($imageOrder)) {
            return ['error' => 'Image order array cannot be empty.'];
        }

        $this->pdo->beginTransaction();
        try {
            // First, set all images for this inventory item to not be primary
            $stmt = $this->pdo->prepare("UPDATE inventory_item_image SET is_primary = 0 WHERE inventory_item_id = ?");
            $stmt->execute([$inventoryItemId]);

            // Then, iterate through the new order and update sort_order and set the new primary
            foreach ($imageOrder as $index => $imageId) {
                $isPrimary = ($index === 0) ? 1 : 0;
                $stmt = $this->pdo->prepare("
                    UPDATE inventory_item_image 
                    SET sort_order = ?, is_primary = ? 
                    WHERE inventory_item_image_id = ? AND inventory_item_id = ?
                ");
                $stmt->execute([$index, $isPrimary, (int) $imageId, $inventoryItemId]);
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Image order updated successfully.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating image order for inventory item {$inventoryItemId}: " . $e->getMessage());
            return ['error' => 'An internal server error occurred while updating image order.'];
        }
    }

    /**
     * Fetches combined details for a product variant from the database.
     *
     * @param int $inventory_item_id The ID of the inventory item.
     * @return array|false An associative array of product details or false if not found.
     */
    public function get_product_details($inventory_item_id)
    {
        // This query joins the inventory item with its parent product to get all details.
        $sql = "SELECT
                    inv.description,
                    inv.cost,
                    inv.price,
                    inv.sku,
                    inv.quantity,
                    p.productID as parent_product_id
                FROM inventoryitem AS inv
                JOIN productitem AS p ON inv.productItemID = p.productID
                WHERE inv.InventoryItemID = ?"; // Corrected to use inv.description

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$inventory_item_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in get_product_details: " . $e->getMessage());
            return false;
        }
    }

    // ... (rest of the methods, now using $this->pdo) ...

    function add_inventory_item()
    {
        // $pdo = $this->dbc; REMOVE
        $sku = $this->generateSKU(); // Generate SKU before preparing the statement
        $skuCode = $this->generateSKUCode();

        $data = [
            ':description' => $_POST['description'],
            ':quantityOnHand' => $_POST['quantityOnHand'],
            ':cost' => $_POST['cost'],
            ':reorderQuantity' => $_POST['reorderQuantity'],
            ':productItemID' => $_POST['productItemID'],
            ':date_added' => date('Y-m-d H:i:s'), // Use current datetime
            ':sku' => $sku,
            ':barcode' => $_POST['barcode'],
            ':sku_code' => $skuCode,
            ':delivery_date' => $_POST['delivery_date'],
            ':tax' => $_POST['tax'],
            ':discount' => $_POST['discount']
        ];

        $sql = "INSERT INTO `inventoryitem` (
                    `description`, 
                    `quantityOnHand`, 
                    `cost`, 
                    `reorderQuantity`, 
                    `productItemID`, 
                    `date_added`, 
                    `sku`, 
                    `barcode`, 
                    `sku_code`,
                    `delivery_date`, 
                    `tax`, 
                    `discount`
                  ) VALUES (
                    :description, 
                    :quantityOnHand, 
                    :cost, 
                    :reorderQuantity, 
                    :productItemID, 
                    :date_added, 
                    :sku, 
                    :barcode, 
                    :sku_code,
                    :delivery_date, 
                    :tax, 
                    :discount
                  )";

        try {
            $stmt = $this->pdo->prepare($sql); // Use $this->pdo
            $stmt->execute($data);
            // Success!  Handle as needed (e.g., redirect, message)
        } catch (PDOException $e) {
            error_log("Error adding inventory item: " . $e->getMessage());
            // Handle the error appropriately (e.g., display error message to user)
        }
    }



    /**
     * Get all images for a specific inventory item.
     *
     * @param int $inventoryItemId
     * @return array
     */
    public function getImagesForInventoryItem(int $inventoryItemId): array
    {
        $sql = "
        SELECT 
            inventory_item_image_id,
            image_name,
            image_path,
            is_primary,
            sort_order
        FROM inventory_item_image
        WHERE inventory_item_id = ?
        ORDER BY is_primary DESC, sort_order ASC
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }











    function check_item_in_existance($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM inventoryitem WHERE InventoryItemID = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Log the error (you might want to use a proper logging mechanism here)
            error_log("Error checking item existence for ID $id: " . $e->getMessage());
            return false; // Return false in case of error
        }
    }


    /**
     * Gets the total inventory quantity for a given product ID.
     *
     * @param int $productID The ID of the product.
     * @return int The total quantity of inventory items for the product, or 0 if no items are found.
     */
    public function getTotalInventoryByProductId($productID)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(quantityOnHand) AS total_quantity FROM inventoryitem WHERE productItemID = ?"); // Use $this->pdo
            $stmt->execute([$productID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total_quantity'] ?? 0; // Returns 0 if total_quantity is null (no items)
        } catch (PDOException $e) {
            // Log the error (you might want to use a proper logging mechanism here)
            error_log("Error fetching total inventory for product ID $productID: " . $e->getMessage());

            return 0; // Return 0 in case of error
        }
    }

    /**
     * Gets inventory items for a product.
     *
     * @param int $productID The ID of the product.
     * @return array An array of inventory items for the product
     */
    public function getInventoryItemsByProductId($productID)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM inventoryitem WHERE productItemID = ?"); // Use $this->pdo
            $stmt->execute([$productID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching inventory items for product ID $productID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets a single inventory item by its ID.
     *
     * @param int $inventoryItemId The ID of the inventory item.
     * @return array|false The inventory item data or false if not found.
     */
    public function getInventoryItemById(int $inventoryItemId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventoryitem WHERE InventoryItemID = ?");
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_product_image($inventory_item_id)
    {
        $stmt = $this->pdo->prepare("SELECT image_path FROM inventory_item_image WHERE inventory_item_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$inventory_item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['image_path'] : null;
    }

    public function getPaginatedProducts($page, $perPage, $searchTerm = null)
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $sql = "SELECT * FROM inventoryitem";

        if ($searchTerm) {
            $sql .= " WHERE description LIKE ?";
            $params[] = "%$searchTerm%";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count total products (for pagination)
        $countSql = "SELECT COUNT(*) as total FROM inventoryitem";
        if ($searchTerm) {
            $countSql .= " WHERE description LIKE ?";
        }
        $countStmt = $this->pdo->prepare($countSql);
        if ($searchTerm) {
            $countStmt->execute(["%$searchTerm%"]);
        } else {
            $countStmt->execute();
        }
        $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'products' => $products,
            'total' => $totalProducts,
            'perPage' => $perPage,
            'currentPage' => $page,
        ];
    }

    /**
     * Gets the product ID (productItemID) for a given inventory item ID.
     *
     * @param int $inventoryItemId The ID of the inventory item.
     * @return int|null The productItemID if found, or null otherwise.
     */
    public function getProductIdForInventoryItem(int $inventoryItemId): ?int
    {
        try {
            $sql = "SELECT productItemID FROM inventoryitem WHERE InventoryItemID = :inventory_item_id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':inventory_item_id', $inventoryItemId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int) $result['productItemID'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching product ID for inventory item ID {$inventoryItemId}: " . $e->getMessage());
            return null;
        }
    }




    /**
     * Get all color variants (from inventoryitem.sku JSON) 
     * and their primary inventory‐item image.
     *
     * @param int $product_id
     * @return array
     */
    public function get_color_variations_for_product_from_sku(int $product_id): array
    {
        $sql = "
        SELECT 
            ii.InventoryItemID, 
            ii.sku,
            invImg.image_path AS thumbnail
        FROM inventoryitem ii
        LEFT JOIN inventory_item_image invImg 
            ON invImg.inventory_item_id = ii.InventoryItemID 
            AND invImg.is_primary = 1
        WHERE ii.productItemID = ?
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$product_id]);

        $colors = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skuData = json_decode($row['sku'], true) ?: [];
            if (isset($skuData['color'])) {
                $colors[] = [
                    'InventoryItemID' => $row['InventoryItemID'],
                    'color' => $skuData['color'],
                    'thumbnail' => $row['thumbnail'] ?? '../assets/img/products/default-variant.png'
                ];
            }
        }

        return $colors;
    }

    /**
     * Get color‐based variants for a product, including all inventory_item_image paths.
     *
     * @param int $productId
     * @return array
     */
    public function getColorVariationsWithImages(int $productId): array
    {
        $sql = "
        SELECT 
            ii.InventoryItemID,
            ii.sku,
            img.image_path,
            img.is_primary
        FROM inventoryitem ii
        LEFT JOIN inventory_item_image img
            ON img.inventory_item_id = ii.InventoryItemID
        WHERE ii.productItemID = ?
        ORDER BY ii.InventoryItemID, img.is_primary DESC, img.inventory_item_image_id ASC
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId]);

        $variants = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skuData = json_decode($row['sku'], true) ?: [];
            if (empty($skuData['color'])) {
                continue;
            }
            $key = $row['InventoryItemID'];
            if (!isset($variants[$key])) {
                $variants[$key] = [
                    'InventoryItemID' => $row['InventoryItemID'],
                    'color' => $skuData['color'],
                    'thumbnail' => $row['image_path'], // first (primary) image
                    'images' => []
                ];
            }
            if ($row['image_path']) {
                $variants[$key]['images'][] = $row['image_path'];
            }
        }
        return array_values($variants);
    }


    public function getOtherInventoryItemsForProduct(int $productId, int $currentInventoryItemId): array
    {
        // Simplified query without CTE for better compatibility
        $sql = "
        SELECT 
            i.inventoryitemID, 
            i.sku, 
            (SELECT ii.image_path 
             FROM inventory_item_image ii 
             WHERE ii.inventory_item_id = i.inventoryitemID 
             ORDER BY ii.is_primary DESC, ii.sort_order ASC 
             LIMIT 1) as image_path
        FROM inventoryitem i
        WHERE i.productItemID = ? AND i.inventoryitemID != ?
        ORDER BY i.inventoryitemID
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId, $currentInventoryItemId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[$row['inventoryitemID']] = [
                'sku' => json_decode($row['sku'], true) ?: [],
                'image_path' => $row['image_path']
            ];
        }

        return $items;
    }

}
