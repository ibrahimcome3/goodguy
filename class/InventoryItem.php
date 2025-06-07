<?php
// removed require_once "../db_conncection/conn.php";
// Go up one level from the current file's directory


class InventoryItem
{
    private $timestamp;
    private $pdo; // Store the PDO connection here
    public $discount_percent;
    public $cost;

    public function __construct($pdo)
    {

        $this->pdo = $pdo; // Store the PDO connection
        $defaultTimeZone = 'UTC';
        date_default_timezone_set($defaultTimeZone);
        $this->timestamp = date('Y-m-d');
    }


    public function setDiscountPercent($percent)
    {
        $this->discount_percent = max(0, min(100, $percent)); //Ensure it's between 0 and 100
    }

    public function getDiscountedPrice()
    {
        if (isset($this->discount_percent)) {
            $discount = $this->discount_percent / 100;
            return $this->cost * (1 - $discount);
        } else {
            return $this->cost;
        }
    }


    public function setDiscountAmount($amount)
    {
        $this->discount_amount = max(0, $amount); //Ensure amount is not negative
    }

    public function getDiscountedPriceAmount()
    {
        if (isset($this->discount_amount)) {
            return $this->cost - $this->discount_amount;
        } else {
            return $this->cost;
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
    function get_products()
    {

        $stmt = $this->pdo->query("select * from productitem"); // Use $this->pdo
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }
    }

    function get_all_drinks()
    {
        //$pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->query("select * from inventoryitem where category in (select `cat_id` from category_new where `cat_path` like '%1/%')"); // Use $this->pdo
        return $stmt;
    }

    function get_all_drinks_count()
    {
        //$pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->query("select count(*) as count from inventoryitem where category in (select `cat_id` from category_new where `cat_id` in (SELECT `cat_id` from category_new WHERE `cat_path` like '%1/%'))"); // Use $this->pdo
        $row = $stmt->fetch();
        return $row['count'];
    }


    function get_inventory_items_product($category_id)
    {
        // $pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `ParentID` = $category_id) "); // Use $this->pdo
        if ($stmt->rowCount() === 0)
            $stmt = $this->pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `categoryID` = $category_id) "); // Use $this->pdo
        if ($stmt->rowCount() === 0)
            print ("<center><b>No items in this category</b></center>");
        return $stmt;
    }

    function get_multiple_inventory_items_product($category_id)
    {
        // $pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `ParentID` = $category_id) "); // Use $this->pdo
        if ($stmt->rowCount() === 0)
            $stmt = $this->pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `categoryID` = $category_id) "); // Use $this->pdo
        if ($stmt->rowCount() === 0)
            print ("<center><b>No items in this category</b></center>");
        return $stmt;
    }

    function get_multiple_inventory_items_product2($sql, $starting_limit, $limit)
    {
        $sql = $sql . ' LIMIT ' . $starting_limit . ',' . $limit;
        //$pdo = $this->dbc; REMOVED

        $stmt = $this->pdo->query($sql); // Use $this->pdo
        return $stmt;
    }


    function get_product_inventory($product_id = 1)
    {
        // $pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->prepare("SELECT * FROM inventoryitem as i WHERE i.productItemID = ?"); // Use $this->pdo

        $stmt->execute([$product_id]);
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }
    }



    function decript_string($string)
    {
        $string2 = explode(",", $string);
        foreach ($string2 as $key => $value) {
            if (strlen($value) === 0) {
                unset($string2[$key]);
            }
        }
        return (array_unique($string2));
    }
    function get_description($id)
    {
        // $pdo = $this->dbc; REMOVED
        $stmt = $this->pdo->query("select * from inventoryitem where InventoryItemID = $id"); // Use $this->pdo
        $row = $stmt->fetch();
        return $row['small_description'];
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

    function get_color($c)
    {
        // $pdo = $this->dbc; REMOVED
        $sql = "select JSON_VALUE(sku, '$.color') as color from inventoryitem where `InventoryItemID` = $c";
        $stmt = $this->pdo->query($sql); // Use $this->pdo
        $row = $stmt->fetch();
        $row_count = $stmt->rowCount();
        if (strlen($row['color']) > 0) {
            return strtoupper($row['color']);
        } else {
            return null;
        }
        //return strtoupper($row['color']);
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

    public function get_product_details($pdo, $inventory_item_id)
    {

        $stmt = $pdo->prepare("SELECT * FROM inventoryitem WHERE InventoryItemID = ?");
        $stmt->execute([$inventory_item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : null;
    }

    public function get_product_image($pdo, $inventory_item_id)
    {
        $stmt = $pdo->prepare("SELECT image_path FROM inventory_item_image WHERE inventory_item_id = ? AND is_primary = 1 LIMIT 1");
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



}
