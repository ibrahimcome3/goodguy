<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

include "includes.php";
var_dump($_SESSION);
if (isset($_SESSION['cart_final'])) {
    if ($_SESSION['uid']) {
        $customer_id = $_SESSION['uid'];
    } else {
        echo "<a href='login.php'>click here to login</a><br/>";
        exit();

    }
    if (!isset($_POST['ship-address'])) {

    }
    $shipaddress = $_POST['ship-address'];

 
    $shipcost = $_POST['shipcost'];
    $subtotal = $_POST['subtotal'];
    
    $sql__ = "INSERT INTO `shipment` (`shipmentID`, `shipmentAddress`, `deliverydate`, `status`, `cost`) VALUES (NULL, $shipaddress, ADDDATE(NOW(), 3), 'NOT STARTED', '$shipcost');";
    $result__ = $mysqli->query($sql__);
    $last_id__ = mysqli_insert_id($mysqli);


    $total_item_ordered = count($_SESSION['cart_final']);
    $total_price_of_items = 0;
    foreach ($_SESSION['cart_final'] as $key => $value) {
        $total_price_of_items += $value;
    }
    //$shiping_address = $_POST['ship-address'];


    $sql = "INSERT INTO `lm_orders`(`order_id`, `customer_id`, `order_total`, `order_total_items`, `order_status`, `order_date_created`, `order_shipping_address`)
                    VALUES (null, $customer_id,  $total_price_of_items,$total_item_ordered,'NOT COMPLETED', CURRENT_TIMESTAMP, $last_id__)";
    $result = $mysqli->query($sql);
    if ($result) {
        $last_id = mysqli_insert_id($mysqli);

        foreach ($_SESSION['cart'] as $key => $value) {
            $sql2 = "select cost from inventoryitem where InventoryItemID  = $key";
            $result = $mysqli->query($sql2);
            $row = mysqli_fetch_array($result);
            $cost = $row['cost'];
            $sql = "INSERT INTO `lm_order_line`(`orderID`, `InventoryItemID`, `delivery_date`, `quwantitiyofitem`, `item_price`, `status`)
              VALUES ($last_id, $key, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 2 DAY),$value, $cost,'NOT DELIVERED')";
            $result = $mysqli->query($sql);
            if ($result) {

            }

        }

        $mail = new PHPMailer(true);
        $orders = new Order($last_id);
        include "invoice.php";

        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            //$mail->isSMTP();                                            //Send using SMTP
            $mail->Host = 'smtp.hostinger.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth = true;  
            $mail->SMTPDebug = 2; //Enable SMTP authentication
            $mail->Username = 'care@goodguyng.com';                     //SMTP username
            $mail->Password = 'PPassword12@';                               //SMTP password
            $mail->Port = 465;

            //Recipients
            $mail->setFrom('care@goodguyng.com', 'GoodGuyng #order: ' . $last_id);
            $mail->addAddress($user_->get_email()); 
            $mail->addAddress('order@goodguyng.com');    //Add a recipient
            //Name is optional


            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Order detail for ' . $last_id;
            $mail->Body = $con_msg;

            //var_dump($mail);
           if (!$mail->send()) { 
             echo 'Mailer Error: ' . $mail->ErrorInfo;  
           }else{
               
                unset($_SESSION['cart']);
                unset($_SESSION['cart_final']);
                header("Location: order-confermation-last-page.php");  
           }

           
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }


       

    }

} else {
    header("Location: index.php");
}
?>