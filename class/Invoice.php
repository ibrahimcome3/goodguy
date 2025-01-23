<?php
class Invoice extends Connn
{
    public $shippingId;
    public $billingId;
    public $orderId;
    public $customerid;

    public $customer_full_name;

    public $customer_email;
    public $customer_phone;


    public function __construct($orderId)
    {
        parent::__construct();
        $defaultTimeZone = 'UTC';
        date_default_timezone_set($defaultTimeZone);

        $pdo = $this->dbc;
        $sql = "SELECT * FROM lm_orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->orderId = $order['order_id'];



        if ($order) {
            $customerSql = "SELECT * FROM customer WHERE customer_id = ?";
            $customerStmt = $pdo->prepare($customerSql);
            $customerStmt->execute([$order['customer_id']]);
            //$customerName = $customerStmt->fetch(PDO::FETCH_COLUMN);
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
            // Directly fetches the first column value

            $this->customer_full_name = $customer['customer_fname'] . " " . $customer['customer_lname'];
            $this->customer_email = $customer['customer_email'];
            $this->customer_phone = $customer['customer_phone'];

            $this->orderId = $order['order_id'];
            $this->customerid = $order['customer_id'];
            $this->shippingId = $order['order_shipping_address'];
        }



    }

    public function generateInvoice($orderId)
    {
        $pdo = $this->dbc;
        $sql = "SELECT * FROM lm_orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$order) {
            return "Order not found.";
        }

        $this->shippingId = $order['order_shipping_address'];
        $customerSql = "SELECT * FROM customer WHERE customer_id = ?";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([$order['customer_id']]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

        $itemsSql = "SELECT oi.*, p.description, i.image_name FROM lm_order_line oi
                     JOIN productitem p ON oi.InventoryItemID = p.productID
                     JOIN inventoryitem i ON oi.InventoryItemID = i.InventoryItemID
                     WHERE oi.orderID = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);




        $invoiceHtml = "<div style='border: 0px solid blue; text-align: center; padding: 5px 0px 5px 0px; background-color:#fffee0; color: Date;'>";
        $invoiceHtml .= "<span style='text-transform: uppercase;  font-weight: 900;'>Invoice order no" . $order['order_id'] . "</span> ( goodguyng.com )</div><div style='margin-top: 10px;'><div style='margin-top: 2px'><b>Order No:</b>" . $order['order_id'] . "</div>";
        $invoiceHtml .= "<div style='margin-top: 2px'><b>Order Date:";

    }

    public function getShippingAddress()
    {
        $pdo = $this->dbc;
        $shippingSql = "SELECT * FROM shipping_address WHERE shipping_address_no = ?";
        $shippingStmt = $pdo->prepare($shippingSql);
        $shippingStmt->execute([$this->shippingId]);
        $shipping = $shippingStmt->fetch(PDO::FETCH_ASSOC);
        return $shipping['address1'] . " " . $shipping['address2'];
    }

    public function getBillingAddress()
    {

    }

    public function getOrderInfromation()
    {
        $pdo = $this->dbc;
        $sql = "SELECT * FROM lm_orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        return "<div><b>Order ID</b>: " . $order['order_id'] . "</div>" . "<div><b>Order Date:</b> " . $order['order_date_created'] . "</div>" . "<div><b>Order Customer:</b> " . ucwords($this->customer_full_name) . "</div> ";

    }

    public function printInvoice(): void
    {

    }

    public function getcustomerName(): string
    {
        return $this->customer_full_name;
    }

    public function getCustomerEmail(): string
    {
        return $this->customer_email;
    }

    public function getCustomerPhoneNumber(): string
    {
        return $this->customer_phone;
    }

    public function getProducts()
    {
        $pdo = $this->dbc;
        $itemsSql = "SELECT oi.*,  i.image_name FROM lm_order_line oi
        JOIN productitem p ON oi.InventoryItemID = p.productID
        JOIN inventoryitem i ON oi.InventoryItemID = i.InventoryItemID
        WHERE oi.orderID = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$this->orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        return $items;
    }


}