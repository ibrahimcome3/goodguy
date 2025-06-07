<?php

// Ensure Composer's autoloader is included
require_once __DIR__ . '/../vendor/autoload.php';

// require_once "Order.php"; // Self-inclusion, likely not needed here
require_once "ProductItem.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//$invoice = new Invoice(orderId: 78);

class Order
{
    public $user_id;
    private $order_id;
    private $order_date;
    public $pdo; // Store the PDO connection here

    private $table_name = "orders";
    private $items_table_name = "order_items";
    private $tracking_table_name = "order_tracking_updates";
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

    function remove_order_item_from_an_order($oid, $id, $uid)
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
        <span><img src="assets/images/payments.png" alt="goodguyng.com logo" style="width: 20px; margin-right: 5px;"></span> 
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

    /**
     * Counts the number of items in a specific order.
     *
     * @param int $orderId The ID of the order.
     * @return int The total number of items in the order.
     */
    function count_order_item_from_an_order(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total_items FROM `lm_order_line` WHERE `orderID` = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(1, $orderId, PDO::PARAM_INT); // Bind the integer order ID
            $stmt->execute();
            return (int) $stmt->fetchColumn(); // fetchColumn() is suitable for COUNT(*)
        } catch (PDOException $e) {
            error_log("Database error counting order items for order ID $orderId: " . $e->getMessage());
            return 0; // Return 0 on error
        }
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
        // Let's var_dump all the parameters here!
        // echo "<pre>--- Dumping parameters for createOrder_ ---</pre>";
        // echo "<pre>User ID: ";
        // var_dump($userId);
        // echo "</pre>";
        // echo "<pre>Shipping Address ID: ";
        // var_dump($shippingAddressId);
        // echo "</pre>";
        // echo "<pre>Payment Method: ";
        // var_dump($paymentMethod);
        // echo "</pre>";
        // echo "<pre>Subtotal: ";
        // var_dump($subtotal);
        // echo "</pre>";
        // echo "<pre>Shipping Cost: ";
        // var_dump($shippingCost);
        // echo "</pre>";
        // echo "<pre>Final Total: ";
        // var_dump($finalTotal);
        // echo "</pre>";
        // echo "<pre>Cart Items: ";
        // var_dump($cartItems);
        // echo "</pre>";
        // echo "<pre>--- End of parameter dump ---</pre>";

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
            // The var_dump and exit below were for debugging the statement preparation.
            // They need to be removed for the loop to execute and insert items.
            foreach ($cartItems as $item) {

                // Ensure item structure is correct (adjust keys if needed)
                $productId = $item['product']['InventoryItemID'] ?? null;
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
        $sql = "SELECT   o.order_id, o.`customer_id`, o.order_date_created, o.order_status,
                    o.order_shipping_address, o.payment_method,
                    o.order_subtotal, o.shipping_cost, o.order_total, -- Added shipping_cost
                    u.`customer_email` as customer_email, -- Example join for email
                    CONCAT(u.`customer_fname`, ' ', u.`customer_lname`) as customer_name -- Example join for name
                    -- Add/remove columns and joins as needed
                FROM lm_orders o
                LEFT JOIN customer u ON o.`customer_id` = u.`customer_id`-- Example join to users table
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

    public function sendInvoiceEmail($orderId)
    {
        // 1. Fetch order details
        $orderDetails = $this->getOrderDetails($orderId); // Assumes this method exists and returns comprehensive order data
        if (!$orderDetails) {
            error_log("sendInvoiceEmail: Could not fetch order details for order ID $orderId");
            return false;
        }

        // 2. Fetch order items
        $orderItems = $this->getOrderItems($orderId); // Assumes this method exists
        if ($orderItems === false) {
            error_log("sendInvoiceEmail: Could not fetch order items for order ID $orderId");
            $orderItems = []; // Proceed with empty items or return false based on preference
        }

        // 3. Fetch customer details
        // Ensure User class is available and $this->pdo is set in Order constructor
        $userInstance = new User($this->pdo); // Or however you access User methods
        $customerDetails = $userInstance->getUserById($orderDetails['customer_id']); // Assumes user_id is in orderDetails and User::getUserById exists

        if (!$customerDetails || empty($customerDetails['customer_email'])) {
            error_log("sendInvoiceEmail: Could not fetch customer email for user ID {$orderDetails['customer_id']}");
            return false;
        }
        $customerEmail = $customerDetails['customer_email'];
        $customerName = trim(($customerDetails['customer_fname'] ?? '') . ' ' . ($customerDetails['customer_lname'] ?? 'Customer'));
        if (empty($customerName))
            $customerName = 'Valued Customer';


        // 4. Fetch shipping address details
        $shippingAddress = null;
        if (!empty($orderDetails['order_shipping_address'])) {
            $shippingAddress = $this->getOrderShippingAddress($orderDetails['order_shipping_address']); // Assumes this method exists
        }
        $stateName = '';
        if ($shippingAddress && !empty($orderDetails['order_shipping_address'])) {
            // Assuming getShippingAddressStateName takes the address ID
            $stateName = $this->getShippingAddressStateName((int) $orderDetails['order_shipping_address']);
        }


        // 5. Construct HTML Email
        $subject = "Your Goodguy Order Confirmation - #" . $orderId;

        // Basic inline styles are generally better for email compatibility
        $body = "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'></head><body style='font-family: Arial, sans-serif; line-height: 1.6; font-size: 16px; color: #333;'>";
        $body .= "<div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd;'>";
        // Mobile-friendly stacked logo and site name
        $body .= "<div style='text-align: center; margin-bottom: 20px;'>";
        $body .= "<img src='cid:goodguyLogo' alt='Goodguy Logo' style='max-width: 50px; height: auto; display: block; margin-left: auto; margin-right: auto; margin-bottom: 5px;'>";
        $body .= "<span style='font-size: 1.5em; font-weight: bold; color: #333; display: block; text-align: center;'>goodguyng.com</span>";
        $body .= "</div>";
        $body .= "<h2 style='color: #333;'>Thank you for your order, " . htmlspecialchars($customerName) . "!</h2>";
        $body .= "<p>Your order #<strong>" . htmlspecialchars($orderId) . "</strong> has been successfully placed.</p>";
        $body .= "<hr style='border: 0; border-top: 1px solid #eee;'>";

        $body .= "<h3 style='color: #555;'>Order Summary:</h3>";
        $body .= "<p><strong>Order Date:</strong> " . htmlspecialchars(date("F j, Y, g:i a", strtotime($orderDetails['order_date_created']))) . "</p>";
        $body .= "<p><strong>Payment Method:</strong> " . htmlspecialchars(ucwords(str_replace('_', ' ', $orderDetails['payment_method']))) . "</p>";

        if ($shippingAddress) {
            $body .= "<h3 style='color: #555;'>Shipping Address:</h3>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']) . "</p>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['address1']) . "</p>";
            if (!empty($shippingAddress['address2'])) {
                $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['address2']) . "</p>";
            }
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['city']) . ", " . htmlspecialchars($stateName) . " " . htmlspecialchars($shippingAddress['zip'] ?? '') . "</p>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['country']) . "</p>";
        }
        $body .= "<hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px;'>";

        $body .= "<h3 style='color: #555;'>Order Items:</h3>";
        $body .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'><thead><tr>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f9f9f9;'>Product</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f9f9f9;'>Quantity</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: right; background-color: #f9f9f9;'>Price</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: right; background-color: #f9f9f9;'>Total</th>";
        $body .= "</tr></thead><tbody>";

        foreach ($orderItems as $item) {
            // Ensure keys match what getOrderItems returns. Using 'quwantitiyofitem' due to its presence in context.
            $quantity = (int) ($item['quwantitiyofitem'] ?? $item['quantity'] ?? 0); // Try to be flexible with quantity key
            $price = (float) ($item['item_price'] ?? $item['cost'] ?? 0); // Try to be flexible with price key
            $lineTotal = $price * $quantity;

            $body .= "<tr>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($item['description']) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . htmlspecialchars($quantity) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>&#8358;" . number_format($price, 2) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>&#8358;" . number_format($lineTotal, 2) . "</td>";
            $body .= "</tr>";
        }
        $body .= "</tbody></table>";

        $body .= "<h3 style='color: #555;'>Order Totals:</h3>";
        $body .= "<table style='width: 100%; max-width: 300px; margin-left: auto; border-collapse: collapse;'>";
        $body .= "<tr><td style='padding: 8px; text-align: right;'>Subtotal:</td><td style='padding: 8px; text-align: right; font-weight: bold;'>&#8358;" . number_format($orderDetails['order_subtotal'], 2) . "</td></tr>";
        $body .= "<tr><td style='padding: 8px; text-align: right;'>Shipping:</td><td style='padding: 8px; text-align: right; font-weight: bold;'>&#8358;" . number_format($orderDetails['shipping_cost'], 2) . "</td></tr>";
        $body .= "<tr><td style='padding: 8px; text-align: right; font-weight: bold; border-top: 1px solid #ddd;'>Total:</td><td style='padding: 8px; text-align: right; font-weight: bold; border-top: 1px solid #ddd;'>&#8358;" . number_format($orderDetails['order_total'], 2) . "</td></tr>";
        $body .= "</table>";

        $body .= "<hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px;'>";
        $body .= "<p style='text-align: center; font-size: 0.9em; color: #777;'>Thank you for shopping with Goodguy!</p>";
        $body .= "<p style='text-align: center; font-size: 0.9em; color: #777;'>If you have any questions, please contact our support.</p>";
        $body .= "</div></body></html>";

        // 6. Set Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // Replace with your actual "From" address and name
        $headers .= 'From: Goodguy Store <noreply@yourdomain.com>' . "\r\n";
        // Optional: Add Reply-To, Bcc, etc.
        // $headers .= 'Reply-To: support@yourdomain.com' . "\r\n";

        // 7. Send Email
        if ($this->sendConfiguredEmail($customerEmail, $subject, $body)) {
            error_log("Invoice email sent successfully for order ID $orderId to $customerEmail.");
            return true;
        } else {
            $error = error_get_last();
            $errorMessage = $error ? $error['message'] : 'Unknown mail error';
            error_log("Failed to send invoice email for order ID $orderId to $customerEmail. Mailer error: " . $errorMessage);
            return false;
        }
    }
    private function sendConfiguredEmail(string $to, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for testing
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';       // Your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'care@goodguyng.com';   // Your SMTP username
            $mail->Password = 'Password1@';           // Your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
            $mail->Port = 465;                        // TCP port to connect to

            // Use a local file path for embedding the logo
            // This ensures it works correctly in production environments
            $logoPath = __DIR__ . '/../assets/images/logo.png'; // Path to your local SVG logo
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'goodguyLogo', 'logo.png'); // Embed the SVG
            }

            // Recipients
            $mail->setFrom('care@goodguyng.com', 'Goodguyng.com'); // Sender
            $mail->addAddress($to); // Add a recipient

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients'; // Optional

            if ($mail->send()) {
                return true;
            } else {
                // This branch is unlikely to be hit when exceptions are enabled in PHPMailer,
                // as send() would typically throw an exception on failure.
                // Added for explicit handling if send() were to return false without throwing.
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}. (send() returned false)");
                return false;
            }
        } catch (Exception $e) {
            // Log the detailed PHPMailer error
            error_log("Message could not be sent. Mailer Error (exception): {$mail->ErrorInfo}. Exception: {$e->getMessage()}");
            return false;
        }
    }


    public function sendPaymentReceiptEmail($orderId)
    {
        // 1. Fetch order details
        $orderDetails = $this->getOrderDetails($orderId);
        if (!$orderDetails) {
            error_log("sendPaymentReceiptEmail: Could not fetch order details for order ID $orderId");
            return false;
        }

        // 2. Fetch order items
        $orderItems = $this->getOrderItems($orderId);
        if ($orderItems === false) {
            error_log("sendPaymentReceiptEmail: Could not fetch order items for order ID $orderId");
            $orderItems = [];
        }

        // 3. Fetch customer details
        $userInstance = new User($this->pdo);
        $customerDetails = $userInstance->getUserById($orderDetails['customer_id']);

        if (!$customerDetails || empty($customerDetails['customer_email'])) { // Assuming email is customer_email from your User::getUserById
            error_log("sendPaymentReceiptEmail: Could not fetch customer email for user ID {$orderDetails['customer_id']}");
            return false;
        }
        $customerEmail = $customerDetails['customer_email'];
        $customerName = trim(($customerDetails['customer_fname'] ?? '') . ' ' . ($customerDetails['customer_lname'] ?? 'Customer'));
        if (empty($customerName))
            $customerName = 'Valued Customer';

        // 4. Fetch shipping address details (optional for receipt, but good for consistency)
        $shippingAddress = null;
        if (!empty($orderDetails['order_shipping_address'])) {
            $shippingAddress = $this->getOrderShippingAddress($orderDetails['order_shipping_address']);
        }
        $stateName = '';
        if ($shippingAddress && !empty($orderDetails['order_shipping_address'])) {
            $stateName = $this->getShippingAddressStateName((int) $orderDetails['order_shipping_address']);
        }

        // 5. Construct HTML Email
        $subject = "Your Goodguy Payment Receipt - Order #" . $orderId;

        $body = "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'></head><body style='font-family: Arial, sans-serif; line-height: 1.6; font-size: 16px; color: #333;'>";
        $body .= "<div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd;'>";
        // Mobile-friendly stacked logo and site name
        $body .= "<div style='text-align: center; margin-bottom: 20px;'>";
        $body .= "<img src='cid:goodguyLogo' alt='Goodguy Logo' style='max-width: 50px; height: auto; display: block; margin-left: auto; margin-right: auto; margin-bottom: 5px;'>";
        $body .= "<span style='font-size: 1.5em; font-weight: bold; color: #333; display: block; text-align: center;'>goodguyng.com</span>";
        $body .= "</div>";
        $body .= "<h2 style='color: #333;'>Thank you for your payment, " . htmlspecialchars($customerName) . "!</h2>";
        $body .= "<p>Your payment for order #<strong>" . htmlspecialchars($orderId) . "</strong> has been successfully processed. Here is your receipt:</p>";
        $body .= "<hr style='border: 0; border-top: 1px solid #eee;'>";

        $body .= "<h3 style='color: #555;'>Order & Payment Details:</h3>";
        $body .= "<p><strong>Order Date:</strong> " . htmlspecialchars(date("F j, Y, g:i a", strtotime($orderDetails['order_date_created']))) . "</p>";
        $body .= "<p><strong>Payment Method:</strong> " . htmlspecialchars(ucwords(str_replace('_', ' ', $orderDetails['payment_method']))) . "</p>";
        $body .= "<p><strong>Payment Status:</strong> Paid</p>"; // Assuming this is sent after successful payment

        if ($shippingAddress) {
            $body .= "<h3 style='color: #555;'>Shipping Address:</h3>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']) . "</p>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['address1']) . "</p>";
            if (!empty($shippingAddress['address2'])) {
                $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['address2']) . "</p>";
            }
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['city']) . ", " . htmlspecialchars($stateName) . " " . htmlspecialchars($shippingAddress['zip'] ?? '') . "</p>";
            $body .= "<p style='margin:0;'>" . htmlspecialchars($shippingAddress['country']) . "</p>";
        }
        $body .= "<hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px;'>";

        $body .= "<h3 style='color: #555;'>Items Purchased:</h3>";
        $body .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'><thead><tr>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f9f9f9;'>Product</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f9f9f9;'>Quantity</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: right; background-color: #f9f9f9;'>Price</th>";
        $body .= "<th style='border: 1px solid #ddd; padding: 8px; text-align: right; background-color: #f9f9f9;'>Total</th>";
        $body .= "</tr></thead><tbody>";

        foreach ($orderItems as $item) {
            $quantity = (int) ($item['quwantitiyofitem'] ?? $item['quantity'] ?? 0);
            $price = (float) ($item['item_price'] ?? $item['cost'] ?? 0);
            $lineTotal = $price * $quantity;
            $body .= "<tr>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($item['description']) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . htmlspecialchars($quantity) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>&#8358;" . number_format($price, 2) . "</td>";
            $body .= "<td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>&#8358;" . number_format($lineTotal, 2) . "</td>";
            $body .= "</tr>";
        }
        $body .= "</tbody></table>";

        $body .= "<h3 style='color: #555;'>Payment Summary:</h3>";
        $body .= "<table style='width: 100%; max-width: 300px; margin-left: auto; border-collapse: collapse;'>";
        $body .= "<tr><td style='padding: 8px; text-align: right;'>Subtotal:</td><td style='padding: 8px; text-align: right; font-weight: bold;'>&#8358;" . number_format($orderDetails['order_subtotal'], 2) . "</td></tr>";
        $body .= "<tr><td style='padding: 8px; text-align: right;'>Shipping:</td><td style='padding: 8px; text-align: right; font-weight: bold;'>&#8358;" . number_format($orderDetails['shipping_cost'], 2) . "</td></tr>";
        $body .= "<tr><td style='padding: 8px; text-align: right; font-weight: bold; border-top: 1px solid #ddd;'>Total Paid:</td><td style='padding: 8px; text-align: right; font-weight: bold; border-top: 1px solid #ddd;'>&#8358;" . number_format($orderDetails['order_total'], 2) . "</td></tr>";
        $body .= "</table>";

        $body .= "<hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px;'>";
        $body .= "<p style='text-align: center; font-size: 0.9em; color: #777;'>Thank you for shopping with Goodguy!</p>";
        $body .= "<p style='text-align: center; font-size: 0.9em; color: #777;'>If you have any questions, please contact our support.</p>";
        $body .= "</div></body></html>";

        // 6. Send Email using the configured helper
        if ($this->sendConfiguredEmail($customerEmail, $subject, $body)) {
            error_log("Payment receipt email sent successfully for order ID $orderId to $customerEmail.");
            return true;
        } else {
            // Error already logged by sendConfiguredEmail
            error_log("Failed to send payment receipt email for order ID $orderId to $customerEmail. (Called from sendPaymentReceiptEmail)");
            return false;
        }
    }

    public function updateOrderStatus($orderId, $newStatus)
    {
        // It's good practice to validate $newStatus against a list of allowed statuses
        $allowedStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];
        if (!in_array(strtolower($newStatus), $allowedStatuses)) {
            error_log("updateOrderStatus: Invalid status '{$newStatus}' for order ID {$orderId}.");
            return false;
        }

        $sql = "UPDATE lm_orders SET lm_orders.order_status = :status, order_date_updated = NOW() WHERE order_id = :order_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating order status for order ID {$orderId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the total number of orders for a specific user.
     *
     * @param int $userId The ID of the user.
     * @param string|null $statusFilter Optional status to filter by.
     * @return int The total number of orders for the user.
     */
    public function getTotalOrderCountForUser(int $userId, ?string $statusFilter = null): int
    {
        if ($userId <= 0) {
            return 0; // Or throw an InvalidArgumentException
        }

        $params = [':user_id' => $userId];
        $sql = "SELECT COUNT(*) FROM lm_orders WHERE customer_id = :user_id";

        if (!empty($statusFilter)) {
            $sql .= " AND order_status = :status_filter";
            $params[':status_filter'] = $statusFilter;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            // Bind parameters dynamically
            // foreach ($params as $key => &$val) { // Pass $val by reference
            //     $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            // }
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error fetching total order count for user ID $userId: " . $e->getMessage());
            return 0; // Return 0 on error, or handle more gracefully
        }
    }

    /**
     * Gets a paginated list of orders for a specific user, ordered by creation date.
     *
     * @param int $userId The ID of the user.
     * @param int $limit The maximum number of orders to return.
     * @param int $offset The number of orders to skip (for pagination).
     * @param string|null $statusFilter Optional status to filter by.
     * @return array|false An array of orders on success, or false on failure.
     */
    public function getOrdersForUser(int $userId, int $limit, int $offset, ?string $statusFilter = null): array|false
    {
        if ($userId <= 0 || $limit < 0 || $offset < 0) {
            // Basic validation for parameters
            error_log("Invalid parameters for getOrdersForUser: UserID: $userId, Limit: $limit, Offset: $offset");
            return false;
        }

        $params = [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ];
        $sql = "SELECT * FROM lm_orders WHERE customer_id = :user_id";

        if (!empty($statusFilter)) {
            $sql .= " AND order_status = :status_filter";
            $params[':status_filter'] = $statusFilter;
        }

        $sql .= " ORDER BY order_date_created DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            // PDO can infer types for execute with an array, but explicit binding is safer for LIMIT/OFFSET
            $stmt->bindParam(':user_id', $params[':user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':limit', $params[':limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $params[':offset'], PDO::PARAM_INT);
            if (!empty($statusFilter)) {
                $stmt->bindParam(':status_filter', $params[':status_filter'], PDO::PARAM_STR);
            }
            $stmt->execute(); // Execute without passing params again if bound individually
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error fetching orders for user ID $userId: " . $e->getMessage());
            return false;
        }
    }


    public function get_order_by_id(int $orderId): array|false
    {
        if ($orderId <= 0) {
            return false; // Invalid order ID
        }

        $sql = "SELECT * FROM lm_orders WHERE order_id = :order_id LIMIT 1";
        // If your primary key column for orders has a different name, adjust 'order_id' accordingly.

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            return $order ?: false; // Returns the order array if found, otherwise false
        } catch (PDOException $e) {
            // Log the error for debugging purposes
            error_log("Database error fetching order ID $orderId: " . $e->getMessage());
            return false; // Return false on database error
        }
    }

    public function getTrackingEvents($orderId)
    {
        try {
            $sql = "SELECT 
                        `event_timestamp`, 
                        `status_description`, 
                        `location` 
                    FROM 
                        `" . $this->tracking_table_name . "`
                    WHERE 
                        `order_id` = :order_id 
                    ORDER BY 
                        `event_timestamp` DESC"; // Get latest events first
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching tracking events for order ID {$orderId}: " . $e->getMessage());
            return []; // Return empty array on error to prevent breaking the page
        }
    }

}




?>