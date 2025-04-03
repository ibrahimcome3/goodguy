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
        $pdo = $this->pdo;
        $stmt = $pdo->prepare("SELECT * FROM lm_orders WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : null;
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
        $stmt = $pdo->prepare("SELECT * FROM shipping_address WHERE shipping_address_no = ?");
        $stmt->execute([$addressId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : null;
    }

    public function getStateName($stateId)
    {
        $pdo = $this->pdo;
        $stmt = $pdo->prepare("SELECT state_name FROM shipping_state WHERE state_id = ?");
        $stmt->execute([$stateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['state_name'] : null;
    }




}




?>