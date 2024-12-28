<?php
$md_table = '';
$msg_body = '';

$msg_body .= "<div style='border: 0px solid blue; text-align: center; padding: 5px 0px 5px 0px; background-color:#fffee0; color: Date;'>";
$msg_body .= "<span style='text-transform: uppercase;  font-weight: 900;'>Invoice for Item order " . $orders->get_order_id() . "</span> ( goodguyng.com )</div><div style='margin-top: 10px;'><div style='margin-top: 2px'><b>Order No:</b>" . $orders->get_order_id() . "</div>";
$msg_body .= "<div style='margin-top: 2px'><b>Order Date:</b>" . $orders->get_order_date() . "</div><div style='margin-top: 2px'><b>Invoice Date:</b>";
$msg_body .= $orders->get_order_date(). "</div></div></div>";
$msg_body .= "<div style='margin-top: 10px; border: 0px solid blue; padding: 0px;'><div>";


$sql = 'SELECT * FROM `customer` WHERE customer_id = ' . $_SESSION['uid'];
$result = $mysqli->query($sql);
$row = mysqli_fetch_array($result);

$msg_body .= "<div style='text-decoration: underline; font-weight: 900;'>Delivery Address</div><div>";
$msg_body .= $user_->get_address() . "<br />";
$msg_body .= "<b>Phone Number:</b>";

$stmt = $user_->get_all_phone_number(41);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['default_'] == 1)
        $msg_body .= $row['PhoneNumber'] . ' ' . 'default <br/>';
    else
        $msg_body .= $row['PhoneNumber'] . '<br/>';
}


$msg_body .= "</div>";
$msg_body .= "<div>";
$msg_body .= "<b>Email Address:</b> " . $user_->get_email();
$msg_body .= "</div>";
$msg_body .= "</div>";
$msg_body .= "</div>";
$msg_body .= "<br />";



$msg_body .= "<table border='1'
        style='background-color:#fffee0;border-collapse:collapse; border:0px solid #FFCC00;color:#000000;width:100%'
        cellpadding='3' cellspacing='3'>

        <tr>
            <th>#</th>
            <th>Item Description</th>
            <th>Picture</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total Price</th>
            <th>Order Status</th>
            <th>Possibly delivery date</th>
        </tr>";

$stmt = $orders->get_order_item($last_id);
$total_price = $shipcost;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $src = 'products/product-'.$row["productItemID"].'/product-'.$row["productItemID"].'-image/inventory-'.$row["productItemID"].'-'.$row["InventoryItemID"].'/'.$row["image_name"];
  //  $src = isset($row['image_path']) ? $row['image_path'] : 'e.jpg';
    $total_price += $row['item_price'] * $row['quwantitiyofitem'];
    //var_dump($row);

    $md_table .= "<tr>
            <td>1</td>
            <td>"
        . $row['small_description'] .
        "</td>
            <td><img src='https://goodguyng.com/" . $src . "'width='50' />
            </td>
            <td>Naira "
        . number_format($row['item_price'], 2) .
        "</td>
            <td>"
        . $row['quwantitiyofitem'] . "(x)
            </td>
            <td>"
        . number_format($row['item_price'] * $row['quwantitiyofitem'], 2) .
        "</td>
            <td>"
        . $row['status'] .
        "</td>
            <td>"
        . $row['delivery_date'] .
        "</td>
        </tr>";
}



$md_table .= "<tr>
        <td></td>
        <td>Tax</td>
        <td>0%</td>
    </tr>
    <tr>
    <td></td>
        <td>Shipping</td>
        <td>".number_format($shipcost, 2)."</td>
    </tr>
    <tr>
        <td></td>
        <td>Tatal Cost</td>
        <td>Naira"
    . number_format($total_price, 2) .
    "</td>
    </tr>
    </table>


    <p>*this is a computer generated invoice.</p>
    <p><small>Tayaya Retail Services Ltd </small></p>
    <p>For complain and enquiry please contact us at care@goodguyng.com</p>
    
    ";


$con_msg = $msg_body . $md_table;
echo $con_msg;
?>