<?php

require_once 'Connn.php';
require_once "invoice.php";
require_once "Order.php";
require_once "ProductItem.php";

//$invoice = new Invoice(orderId: 78);
$p = new ProductItem();
class Order
{
    public $user_id;
    private $order_id;
    private $order_date;
    private $pdo; // Store the PDO connection here
    public function __construct($pdo)
    {

        $this->pdo = $pdo; // Store the PDO connection

        if (isset($_SESSION['uid'])) {
            $this->user_id = $_SESSION['uid'];
        } else {
            $this->user_id = 0;
        }
        // $sql = "SELECT * FROM `lm_orders` WHERE `order_id` = $order";

        // $stmt = $pdo->query($sql);
        // $row = $stmt->fetch();
        // if ($row) {
        //     $this->order_id = $row['order_id'];
        //     $this->order_date = $row['order_date_created'];
        // }



    }


    function set_order_parameter($order_id = 2)
    {
        $this->order_id = $order_id;
    }

    function get_order_date()
    {
        return $this->order_date;
    }

    function get_order_id()
    {
        return $this->order_id;
    }

    function get_orders()
    {

        $sql = "SELECT * FROM `lm_orders` WHERE `customer_id` = " . $this->user_id;
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return $stmt;
        } else {
            return false;
        }

    }



    function get_order_item($id_)
    {
        $id = $id_;
        $sql = "SELECT * FROM `lm_order_line` left join inventoryitem on lm_order_line.InventoryItemID = inventoryitem.InventoryItemID left join inventory_item_image on inventoryitem.InventoryItemID = inventory_item_image.`inventory_item_id` WHERE `orderID` = $id group by inventoryitem.InventoryItemID;";
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return $stmt;
        }
    }

    function get_orders_by_id($id)
    {

        $sql = "SELECT * FROM `lm_orders` WHERE  = $id";
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return $stmt;
        } else {
            return false;
        }

    }

    function _delete_item_from_cart($id)
    {
        session_start();
        if (is_numeric($id) && isset($_SESSION['cart']) && isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$_POST['remove']]);
            echo true;
        } else {
            echo "error";
        }
    }

    function remove_order_item_from_an_order($oid, $id)
    {
        $sql = "DELETE FROM `lm_order_line` WHERE `InventoryItemID` = $id and `orderID` = $oid";
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            if ($this->count_order_item_from_an_order($oid) <= 0) {
                $this->cancel_order($oid);
                header("Location: dashboard.php");
                exit();
            }
            return true;
        } else {
            return false;
        }

    }

    function count_number_of_orders()
    {
        $sql = "SELECT count(*) as c FROM `lm_orders` WHERE `customer_id` = " . $this->user_id;
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return $row['c'];

    }


    function cancel_order($id)
    {
        $sql = "DELETE FROM `lm_orders` WHERE `order_id` = $id";
        $pdo = $this->pdo;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return true;
        } else {
            return false;
        }

    }

    function get_order_item_price($id, $order_id)
    {
        $pdo = $this->pdo;
        $stmt = $pdo->query("select * from lm_order_line where orderID = $order_id and InventoryItemID = $id");
        $row_count = $stmt->rowCount();
        if ($row_count > 0) {
            $row = $stmt->fetch();
            return $row['item_price'];
        } else {
            return false;
        }

    }

    function getOrderItems($orderId)
    {
        $sql = "SELECT oi.*, it.*, first_image.image_name FROM lm_order_line oi left JOIN ( SELECT inventory_item_id, MIN(image_name) AS image_name FROM inventory_item_image GROUP BY inventory_item_id ) AS first_image ON oi.InventoryItemID = first_image.inventory_item_id left join inventoryitem it on it.InventoryItemID = oi.InventoryItemID WHERE oi.orderID = ?;";
        $pdo = $this->pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($items) {
            foreach ($items as &$item) {
                $item['image_path'] = $this->getImagePath($item['InventoryItemID'], $item['image_name']);
            }
        }

        return $items;
    }


    private function getImagePath($inventoryItemId, $imageName)
    {
        $imagePath = "products/product-" . $inventoryItemId . "/product-" . $inventoryItemId . "-image/inventory-" . $inventoryItemId . "-" . $inventoryItemId . "/" . $imageName;
        // Check if the image exists; if not, return a default image path
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath)) {
            $imagePath = "e.jpeg"; // Default image
        }
        return $imagePath;
    }

    public function concludeOrder($orderID)
    {
        //Update order status in the database
        $pdo = $this->pdo;
        $sql = "UPDATE lm_orders SET order_status = 'concluded' WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderID]);
    }

    public function updatePaymentMethod($orderID, $paymentMethod)
    {
        //Update the payment method column in the order table
        $pdo = $this->pdo;
        $sql = "UPDATE lm_orders SET payment_method = ? WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$paymentMethod, $orderID]);
    }


    public function OrderItem($orderId)
    {
        $product_obj = new ProductItem();
        $totalItems = 0;
        $totalDiscount = 0;
        $grandTotal = 0;
        $sn = 1;
        $orderItemHtml = "";
        $p = new ProductItem();
        $products = $this->getOrderItems($orderId);
        $taxAmount = 0;

        // Start the main table with the same style as generateInvoice
        $orderItemHtml .= "<table style='max-width: 800px; margin: 0 auto; border-collapse: collapse;  border: 1px solid #ddd;'>";

        // Table Header (adjust as needed to match generateInvoice header)
        $orderItemHtml .= "<thead><tr>";
        $orderItemHtml .= "<th style='padding: 8px;'>#</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Item Code</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Image</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Description</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Price</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Discount</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Quantity</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Tax (%)</th>";
        $orderItemHtml .= "<th style='padding: 8px;'>Total</th>";
        $orderItemHtml .= "</tr></thead><tbody>";


        foreach ($products as $product) {
            $totalItems += $product['quwantitiyofitem'];
            $imageUrl = $p->get_image($product['InventoryItemID']);
            if (!$imageUrl)
                $imageUrl = "e.jpeg";
            $productID = $product_obj->get_product_id($product['InventoryItemID']);
            $inventoryItemID = $product['InventoryItemID'];

            if ($product_obj->check_dirtory_resized_600($productID, $inventoryItemID)) {
                $imageUrl = "products/product-" . $productID . "/product-" . $productID . "-image/inventory-" . $productID . "-" . $inventoryItemID . "/resized_600/" . basename($imageUrl);
            }
            $itemTotal = $product['cost'] * $product['quwantitiyofitem'];

            $discountAmount = 0;
            if (isset($product['discount']) && $product['discount'] > 0) {
                $discountAmount = $itemTotal * ($product['discount'] / 100);
                $itemTotal -= $discountAmount;
                $totalDiscount += $discountAmount;
            }

            $taxAmount = ($product['tax'] > 0) ? ($itemTotal * ($product['tax'] / 100)) : 0;
            $itemTotalWithTaxAndDiscount = $itemTotal + $taxAmount;
            $grandTotal += $itemTotalWithTaxAndDiscount;

            $orderItemHtml .= "<tr>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . $sn++ . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>SDFR564BGYRF</td>"; // Replace with actual item code if available
            $orderItemHtml .= "<td style='padding: 8px;'><img src='" . $imageUrl . "' alt='Product Image' style='max-width: 50px;'></td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . $product['description'] . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . number_format($product['cost'], 2) . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . number_format($discountAmount, 2) . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . $product['quwantitiyofitem'] . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . $product['tax'] . "</td>";
            $orderItemHtml .= "<td style='padding: 8px;'>" . number_format($itemTotalWithTaxAndDiscount, 2) . "</td>";
            $orderItemHtml .= "</tr>";
        }

        $orderItemHtml .= $this->getTotalsForOrders($products);
        $orderItemHtml .= "</tbody></table>"; // Close the main table
        $orderItemHtml .= $this->poweredByGoodguy();
        $orderItemHtml .= $this->orderfooter();
        //echo $orderItemHtml;
    }



    function poweredByGoodguy()
    {
        $html = <<<HTML
    <div style="display: flex; align-items: center; justify-content: center; margin-top: 20px;">
        <span><img src="assets/images/goodguy.svg" alt="goodguyng.com logo" style="width: 20px; margin-right: 5px;"></span> 
        <span style="font-size:0.8rem; color: #777;">Invoice powered by goodguy</span>
        
        
    </div>
    HTML;

        return $html;
    }

    function orderfooter()
    {
        $html = <<<HTML
    <div style="display: flex; align-items: center; justify-content: center; margin-top: 20px;">
        
        <span style="font-size:0.8rem; color: #777;">Copyright 2024 Goodguyng.com. All rights reserved.</span>
        
        
    </div>
    HTML;

        return $html;
    }

    public function getTotalsForOrders($products)
    {
        $totalItems = 0;
        $totalDiscount = 0;
        $grandTotal = 0;
        $p = new ProductItem();
        $product_obj = new ProductItem();

        foreach ($products as $product) {
            $totalItems += $product['quwantitiyofitem'];

            $itemTotal = $product['cost'] * $product['quwantitiyofitem'];

            $discountAmount = 0;
            if (isset($product['discount']) && $product['discount'] > 0) {
                $discountAmount = $itemTotal * ($product['discount'] / 100);
                $itemTotal -= $discountAmount;

                $totalDiscount += $discountAmount;
            }


            $taxAmount = ($product['tax'] > 0) ? ($itemTotal * ($product['tax'] / 100)) : 0;
            $itemTotalWithTaxAndDiscount = $itemTotal + $taxAmount;

            $grandTotal += $itemTotalWithTaxAndDiscount;
        }



        $totals = "
         <tr>
                            <td colspan='8' style='text-align: right'><strong style='margin-right: 10px'>Total Discount:</strong></td>
                            <td><strong>" . number_format($totalDiscount, 2) . "</strong></td>
                        </tr>
                        <tr>
                            <td colspan='8' style='text-align: right'><strong style='margin-right: 10px'>Grand Total:</strong></td>
                            <td><strong>" . number_format($grandTotal, 2) . "</strong></td>
                        </tr>
                        <tr>
                            <td colspan='8' style='text-align: right'><strong style='margin-right: 10px'>Total Items:</strong></td>
                            <td><strong>" . $totalItems . "</strong></td>
        </tr>";

        return $totals;
    }


    public function updateOrderItemQuantity($mysqli, $orderItemId, $quantity)
    {
        $stmt = $mysqli->prepare("UPDATE lm_order_line SET quwantitiyofitem = ? WHERE order_item_id = ?");
        $stmt->bind_param("ii", $quantity, $orderItemId);
        return $stmt->execute(); // Return true on success, false on failure
    }

    public function deleteOrderItem($mysqli, $orderItemId)
    {
        $stmt = $mysqli->prepare("DELETE FROM lm_order_line WHERE order_item_id = ?");
        $stmt->bind_param("i", $orderItemId);
        return $stmt->execute(); // Return true on success, false on failure
    }

    public function getOrderDetailsFromLmOrders($mysqli, $orderId)
    {
        $stmt = $mysqli->prepare("SELECT * FROM lm_orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Return an associative array or null if not found
    }

    public function getOrderItemsFromLmOrders($mysqli, $orderId)
    {
        $stmt = $mysqli->prepare("SELECT * FROM lm_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC); // Return an array of associative arrays or empty array if none found
    }

    function count_order_item_from_an_order($mysqli, $oid)
    {
        $sql = "SELECT COUNT(*) AS total_items FROM `lm_order_line` WHERE `orderID` = ?";
        $stmt = $mysqli->prepare($sql); // Use prepared statement for security
        $stmt->bind_param("i", $oid);
        $stmt->execute(); // Bind parameter to prevent SQL injection
        $result = $stmt->get_result();
        $row = $result->fetch_assoc(); // Fetch as associative array
        return $row['total_items']; // Return the count
    }
    public function addOrderTracking($mysqli, $orderId, $location)
    {
        $sql = "INSERT INTO lm_order_tracking (order_id, location) VALUES (?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("is", $orderId, $location);
        return $stmt->execute();
    }

    public function getOrderTrackingHistory($mysqli, $orderId)
    {
        $sql = "SELECT * FROM lm_order_tracking WHERE order_id = ? ORDER BY timestamp DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }
    public function updateOrderLocation($mysqli, $orderId, $newLocation)
    {
        $sql = "UPDATE `lm_orders` SET order_location = ? WHERE order_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $newLocation, $orderId);
        return $stmt->execute();
    }
    public function acceptOrder($mysqli, $orderId)
    {
        $sql = "UPDATE `lm_orders` SET order_status = 'Accepted' WHERE order_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $orderId);
        return $stmt->execute();
    }
    public function createOrder_($userId, $shippingAddressId, $paymentMethod, $subtotal, $shippingCost, $finalTotal, $cartItems)
    {
        // Validate inputs (basic example)
        if ($userId <= 0 || $shippingAddressId <= 0 || empty($paymentMethod) || $finalTotal < 0 || empty($cartItems)) {
            error_log("Invalid parameters passed to createOrder.");
            return false;
        }

        $this->pdo->beginTransaction(); // Start transaction

        try {
            // 1. Insert into lm_orders table
            // *** IMPORTANT: Verify all column names match your lm_orders table ***
            $sqlOrder = "INSERT INTO lm_orders (
                            customer_id,
                            order_date_created,
                            order_status,
                            order_shipping_address,
                            payment_method,
                            order_subtotal,
                            shipping_cost, -- Added column
                            order_total
                            -- Add other relevant columns like discount_amount, tax_amount if they exist
                        ) VALUES (
                            :user_id,
                            NOW(),
                            'pending', -- Initial status
                            :shipping_address_id,
                            :payment_method,
                            :subtotal,
                            :shipping_cost, -- Added placeholder
                            :total
                            -- Add other placeholders if needed
                        )";

            $stmtOrder = $this->pdo->prepare($sqlOrder);

            $stmtOrder->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtOrder->bindParam(':shipping_address_id', $shippingAddressId, PDO::PARAM_INT);
            $stmtOrder->bindParam(':payment_method', $paymentMethod, PDO::PARAM_STR);
            // Bind monetary values as strings for precision with DECIMAL type
            $stmtOrder->bindParam(':subtotal', $subtotal, PDO::PARAM_STR);
            $stmtOrder->bindParam(':shipping_cost', $shippingCost, PDO::PARAM_STR); // Bind new value
            $stmtOrder->bindParam(':total', $finalTotal, PDO::PARAM_STR);
            // Bind others if needed

            if (!$stmtOrder->execute()) {
                throw new Exception("Failed to insert into lm_orders: " . implode(", ", $stmtOrder->errorInfo()));
            }

            $orderId = $this->pdo->lastInsertId(); // Get the newly created order ID

            // 2. Insert into order_items table (or your equivalent item table)
            // *** IMPORTANT: Verify column names (order_id, InventoryItemID, quwantitiyofitem, item_price) ***
            $sqlItems = "INSERT INTO lm_order_line (
                            orderID,
                            InventoryItemID,
                            quwantitiyofitem, -- Assuming typo exists in DB
                            item_price
                            -- Add other columns like product_name if you store snapshots
                         ) VALUES (
                            :order_id,
                            :product_id,
                            :quantity,
                            :price
                            -- Add other placeholders
                         )";
            $stmtItems = $this->pdo->prepare($sqlItems);

            foreach ($cartItems as $item) {
                // Ensure item structure is correct (adjust keys if needed)
                $productId = $item['InventoryItemID'] ?? null;
                $quantity = $item['quantity'] ?? 0;
                // Use the final price (incl. promotion) stored in the cart item detail
                $price = $item['cost'] ?? ($item['product']['cost'] ?? 0); // Adapt based on your cart item structure

                if ($productId && $quantity > 0) {
                    $stmtItems->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                    $stmtItems->bindParam(':product_id', $productId, PDO::PARAM_INT);
                    $stmtItems->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                    $stmtItems->bindParam(':price', $price, PDO::PARAM_STR); // Bind price as string

                    if (!$stmtItems->execute()) {
                        throw new Exception("Failed to insert order item (Product ID: $productId): " . implode(", ", $stmtItems->errorInfo()));
                    }
                }
            }

            $this->pdo->commit(); // Commit transaction if all inserts were successful
            return (int) $orderId; // Return the new order ID

        } catch (Exception $e) {
            $this->pdo->rollBack(); // Roll back changes on any error
            error_log("Order creation failed: " . $e->getMessage());
            return false; // Return false on failure
        }
    }
    public function createOrder($userId, $shippingAddressId, $paymentMethod, $total, $cartItems)
    {
        // echo $userId;
        // echo $shippingAddressId;
        //echo $paymentMethod;
        // echo $total;
        // var_dump($cartItems);



        $pdo = $this->pdo;
        try {
            // Start a transaction to ensure data consistency
            $pdo->beginTransaction();

            // 1. Insert the order into the 'lm_orders' table
            $stmt = $pdo->prepare("INSERT INTO lm_orders (customer_id, order_total, order_total_items, order_status, order_date_created, order_shipping_address, payment_method) VALUES (?, ?, ?, 'pending', NOW(), ?, ?)");
            var_dump($stmt);
            $stmt->execute([$userId, $total, count($cartItems), $shippingAddressId, $paymentMethod]);

            $orderId = $pdo->lastInsertId();
            var_dump($orderId);


            // 2. Insert the order items into the 'lm_order_line' table
            $stmt = $pdo->prepare("INSERT INTO lm_order_line (orderID, InventoryItemID, quwantitiyofitem, item_price, status) VALUES (?, ?, ?, ?, 'pending')");
            foreach ($cartItems as $item) {
                $stmt->execute([$orderId, $item['product']['InventoryItemID'], $item['quantity'], $item['cost']]);
            }

            // Commit the transaction
            $pdo->commit();

            return $orderId;
        } catch (Exception $e) {
            // Rollback the transaction if any error occurred
            $pdo->rollBack();
            // Log the error or handle it appropriately
            error_log("Error creating order: " . $e->getMessage());
            //return false; // Or throw an exception
        }
    }


    public function getOrderDetails($orderId)
    {
        // *** IMPORTANT: Add 'shipping_cost' to the SELECT list ***
        $sql = "SELECT
                    o.order_id, o.user_id, o.order_date_created, o.order_status,
                    o.order_shipping_address, o.payment_method,
                    o.order_subtotal, o.shipping_cost, o.order_total, -- Added shipping_cost
                    u.email as customer_email, -- Example join for email
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name -- Example join for name
                    -- Add/remove columns and joins as needed
                FROM lm_orders o
                LEFT JOIN users u ON o.user_id = u.user_id -- Example join to users table
                WHERE o.order_id = :order_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the single order row
        } catch (PDOException $e) {
            error_log("Database error fetching order details for order ID $orderId: " . $e->getMessage());
            return false;
        }
    }

    public function updateOrderStatus($orderId, $status)
    {
        $pdo = $this->pdo;
        $stmt = $pdo->prepare("UPDATE lm_orders SET lm_orders.order_status = ? WHERE order_id = ?");
        return $stmt->execute([$status, $orderId]);
    }

    public function getOrderShippingAddress($addressId)
    {
        $pdo = $this->pdo;

        // Prepare the SQL query with a LEFT JOIN
        // Select all columns from shipping_address (aliased as 'sa')
        // Select specific columns from the users table (aliased as 'u') to avoid name collisions
        // *** IMPORTANT: Verify table names ('users') and column names ('user_id') below match your database schema ***
        $sql = "SELECT
                    sa.*,
                    u.customer_email AS email,         -- Alias user email
                    u.customer_fname AS first_name, -- Alias user first name
                    u.customer_fname AS last_name   -- Alias user last name
                    -- Add any other user fields you need here, e.g., u.username AS user_username
                FROM
                    shipping_address sa
                LEFT JOIN
                    customer u ON sa.customer_id = u.customer_id -- The JOIN condition based on the foreign key
                WHERE
                    sa.shipping_address_no = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$addressId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as an associative array

        // Return the combined data or null if the address wasn't found
        return $row ? $row : null;
    }



    public function getShippingAreaCost(int $areaId): ?float
    {
        if ($areaId <= 0) { // Basic validation: Don't query if ID is invalid
            return null;
        }

        $sql = "SELECT area_cost FROM shipping_areas WHERE area_id = :area_id LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->execute();
            $cost = $stmt->fetchColumn(); // Fetch the single 'area_cost' value

            // fetchColumn returns false if no row/column found, or the value
            // Return the cost as a float, or null if not found/not numeric
            return ($cost !== false && is_numeric($cost)) ? (float) $cost : null;

        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Database error fetching shipping area cost for area ID $areaId: " . $e->getMessage());
            return null; // Return null on database error
        }
    }

    public function getShippingAddressStateName(int $addressId): ?string
    {
        if ($addressId <= 0) {
            return null; // Invalid address ID
        }

        // *** ADJUST table ('states') and column names ('state_id', 'state_name', 'shipping_address_no') as necessary ***
        $sql = "SELECT s.state_name
                FROM shipping_address sa
                INNER JOIN states s ON sa.state_id = s.state_id
                WHERE sa.shipping_address_no = :address_id
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $stmt->execute();

            $stateName = $stmt->fetchColumn(); // Fetch the first column of the first row

            // fetchColumn returns false if no row found
            return ($stateName !== false) ? (string) $stateName : null;

        } catch (PDOException $e) {
            error_log("Database error fetching state name for shipping address ID $addressId: " . $e->getMessage());
            return null; // Return null on error
        }
    }


    /**
     * Retrieves the state name based on the state ID.
     * (You might already have this or similar - keep it if needed elsewhere)
     *
     * @param int $stateId The ID of the state.
     * @return string|null The name of the state, or null if not found.
     */
    public function getStateName(int $stateId): ?string
    {
        if ($stateId <= 0) {
            return null;
        }
        // *** ADJUST table ('states') and column names ('state_id', 'state_name') if necessary ***
        $sql = "SELECT state_name FROM states WHERE state_id = :state_id LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':state_id', $stateId, PDO::PARAM_INT);
            $stmt->execute();
            $name = $stmt->fetchColumn();
            return ($name !== false) ? (string) $name : null;
        } catch (PDOException $e) {
            error_log("Database error fetching state name for state ID $stateId: " . $e->getMessage());
            return null;
        }
    }

    public function getShippingAreaIdFromAddress(int $addressId): ?int
    {
        if ($addressId <= 0) {
            return null; // Invalid address ID provided
        }

        // *** ADJUST table ('shipping_address') and column names ('shipping_area_id', 'shipping_address_no') if necessary ***
        $sql = "SELECT shipping_area_id
                FROM shipping_address
                WHERE shipping_address_no = :address_id
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $stmt->execute();

            $areaId = $stmt->fetchColumn(); // Fetch the value of the first column

            // fetchColumn returns false if no row found, or the value (which could be NULL in the DB)
            // We want to return null if it's false (not found) or explicitly NULL
            if ($areaId === false) {
                return null; // Address not found
            }
            // If $areaId is NULL in the database, fetchColumn returns NULL, which is correct.
            // If it's a number (including 0), cast to int.
            return ($areaId !== null) ? (int) $areaId : null;

        } catch (PDOException $e) {
            error_log("Database error fetching shipping area ID for address ID $addressId: " . $e->getMessage());
            return null; // Return null on database error
        }
    }

    public function updateOrderCosts(int $orderId, float $subtotal, float $shippingCost, float $finalTotal): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $sql = "UPDATE lm_orders
            SET order_subtotal = :subtotal,
                shipping_cost = :shipping_cost,
                order_total = :total
            WHERE order_id = :order_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind monetary values as strings for precision
            $stmt->bindParam(':subtotal', $subtotal, PDO::PARAM_STR);
            $stmt->bindParam(':shipping_cost', $shippingCost, PDO::PARAM_STR);
            $stmt->bindParam(':total', $finalTotal, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

            $success = $stmt->execute();
            // Optionally check rowCount, though update might succeed with 0 rows if values are the same
            return $success;

        } catch (PDOException $e) {
            error_log("Error updating costs for order ID $orderId: " . $e->getMessage());
            return false;
        }
    }





}




?>