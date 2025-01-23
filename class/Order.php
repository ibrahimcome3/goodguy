<?php
class Order extends Connn
{
    public $user_id;
    private $order_id;
    private $order_date;



    function __construct($order = 0)
    {

        parent::__construct();
        if (isset($_SESSION['uid'])) {
            $this->user_id = $_SESSION['uid'];
        } else {
            $this->user_id = 0;
        }
        $sql = "SELECT * FROM `lm_orders` WHERE `order_id` = $order";
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        if ($row) {
            $this->order_id = $row['order_id'];
            $this->order_date = $row['order_date_created'];
        }



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
        $pdo = $this->dbc;
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
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return $stmt;
        }
    }

    function get_orders_by_id($id)
    {

        $sql = "SELECT * FROM `lm_orders` WHERE  = $id";
        $pdo = $this->dbc;
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
        $pdo = $this->dbc;
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
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return $row['c'];

    }

    function count_order_item_from_an_order($oid)
    {
        $sql = "SELECT count(*) as c FROM `lm_order_line` WHERE `orderID` = $oid ";
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return $row['c'];
    }

    function cancel_order($id)
    {
        $sql = "DELETE FROM `lm_orders` WHERE `order_id` = $id";
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        if ($stmt) {
            return true;
        } else {
            return false;
        }

    }

    function get_order_item_price($id, $order_id)
    {
        $pdo = $this->dbc;
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
        $pdo = $this->dbc;
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





}


?>