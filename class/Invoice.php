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

    public $invoice_date;
    public $invoice_due_date;
    public $invoiceNumber;



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
        $this->insertInvoiceRecord($orderId);

        $sql = "SELECT * FROM lm_orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetchAll(PDO::FETCH_ASSOC);


        if (!$order) {
            return "Order not found.";
        }

        //$this->shippingId = $order['order_shipping_address'];
        $customerSql = "SELECT * FROM customer WHERE customer_id = ?";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([$this->customerid]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);


        $invoiceHtml = "<table style='width: 800px; margin: 0 auto; border-collapse: collapse; border: 1px solid #ddd;'>"; // Start main table


        // Add invoice number row (after the header row)
        $invoiceHtml .= "<tr style='background-color:#e1ecf7; color: Date;'><td colspan='2'>"; // Header row
        $invoiceHtml .= "<div style='text-align: center; padding: 15px 10px;'>";
        $invoiceHtml .= "<img src='assets/images/goodguy.svg' alt='goodguyng.com logo' style='width: 35px; margin-right:10px' />";
        $invoiceHtml .= "<span style='text-transform: uppercase; font-weight: 900;'>Order Invoice</span> ( goodguyng.com )";
        $invoiceHtml .= "</div>";
        $invoiceHtml .= "</td></tr>";


        $invoiceHtml .= "<tr style='background-color:#f8f9fa;'><td colspan='2'>";
        $invoiceHtml .= "<div style='text-align: right; padding: 10px;'><b>Invoice #:</b> " . $this->invoiceNumber . "</div>";
        $invoiceHtml .= "</td></tr>";


        $invoiceHtml .= "<tr>";


        $invoiceHtml .= "<td><p style='text-align: left; padding: 8px; margin-top: 20px;'>Dear <b>" . ucwords($this->customer_full_name) . "</b>,</p>";
        $invoiceHtml .= "<p style='text-align: left; padding: 8px;'>Thank you for your order!</p>";
        $invoiceHtml .= "</td>";

        $invoiceHtml .= "<td style='vertical-align: top; text-align: right; padding: 10px;'>"; // Company details cell
        $invoiceHtml .= $this->companyaddress();
        $invoiceHtml .= "</td> </tr>";

        $invoiceHtml .= "<tr><td style='vertical-align: top; padding: 10px;' colspan='2'>"; // Thank you message row
        $invoiceHtml .= "<p>Your order will be packaged and shipped as soon as possible. Once the item(s) is out for delivery or available for pick-up you will receive a notification from us.</p></td></tr>";


        $invoiceHtml .= "<tr>"; // Company and Order Information row
        $invoiceHtml .= "<td style='vertical-align: top; padding: 10px;'>"; // Order details cell
        $invoiceHtml .= $this->getOrderInfromation();
        $invoiceHtml .= "</td>";
        $invoiceHtml .= "</tr>";



        $invoiceHtml .= "<tr><td colspan='2'>"; // Billing and Shipping row (spanning both columns)

        $invoiceHtml .= "<table style='width:100%; margin-bottom: 20px;'><tr>"; // Start nested table
        $invoiceHtml .= "<td style='vertical-align: top; padding: 10px;'>";
        $invoiceHtml .= "<b>Bill to:</b><br>";
        $invoiceHtml .= ucwords($this->customer_full_name) . "<br>";
        $invoiceHtml .= "<i>" . $this->customer_email . "</i><br>";
        $invoiceHtml .= $this->customer_phone . "<br>";
        $invoiceHtml .= "</td>";
        $invoiceHtml .= "<td style='vertical-align: top; padding: 10px;'>";
        $invoiceHtml .= "<b>Ship to:</b><br>";
        $invoiceHtml .= $this->getShippingAddress();
        $invoiceHtml .= "</td>";

        $invoiceHtml .= "</tr></table>"; // End of nested table

        $invoiceHtml .= "</td></tr>";

        // ... (Add more table rows for items, totals, etc.)

        $invoiceHtml .= "</table>"; // End main table

        //$this->insertInvoiceRecord($orderId);
        return $invoiceHtml;
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

    function companyaddress()
    {
        $html = <<<HTML
        <div style="margin-bottom: 10px; color: blue;">
    <div style="">
    <div>  
    <span><img src="assets/images/goodguy.svg" alt="goodguyng.com logo" style="width: 20px; margin-right: 5px;"></span> 
        <span>Goodguyng.com</span>
    </div>
    
      <div>No 31 saint finberss collage road akoka Yaba lagos</div>
        <div>Email: care@goodguyng.com</div>
        <div>Phone Number: +2348051067944</div>

        
        
    </div>
    </div>
    HTML;

        return $html;
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
    private function insertInvoiceRecord($orderId)
    {
        $pdo = $this->dbc;
        $invoiceDate = date("Y-m-d H:i:s");
        $dueDays = 14;
        $invoiceDueDate = date("Y-m-d", strtotime("+$dueDays days"));


        try {
            // Check if an invoice for this order already exists
            $checkSql = "SELECT invoice_number FROM invoice WHERE order_id = ?"; // Select invoice_number
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$orderId]);

            if ($checkStmt->rowCount() > 0) {
                // Invoice exists, get the existing invoice number
                $this->invoiceNumber = $checkStmt->fetchColumn();
            } else {
                // Invoice doesn't exist, generate a new one
                $this->invoiceNumber = $this->generateInvoiceNumber();
            }


            // Now use $this->invoiceNumber for INSERT or UPDATE (simplified below)
            if (isset($invoiceId)) { //if invoice already exist update the invoice
                $sql = "UPDATE invoice SET invoice_date = ?, invoice_number = ?, invoice_due_date = ? WHERE invoiceid = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$invoiceDate, $this->invoiceNumber, $invoiceDueDate, $invoiceId]);

            } else { //insert new invoice 

                $sql = "INSERT INTO invoice (invoice_date, invoice_number, order_id, invoice_due_date) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$invoiceDate, $this->invoiceNumber, $orderId, $invoiceDueDate]);

            }



        } catch (PDOException $e) {
            error_log("Error inserting/updating invoice record: " . $e->getMessage());

        }
    }


    private function generateInvoiceNumber()
    {
        return substr(time(), 0, 6);
    }









}