<?php
session_start();
include 'conn.php';
$last_id = null;
$fname = strtolower($_POST['firstname']);   //string
$lname = strtolower($_POST['lastname']);   //string
$streetaddress1 = strtolower($_POST['streetaddress1']); //string
$streetaddress2 = strtolower($_POST['streetaddress2']); //string
$email = $_POST['email'];   //string
$state = $_POST['state'];   //string
$is_this_your_Shipping_address = (isset($_POST['is_this_your_Shipping_address'])) ? "on" : "off";
$city = $_POST['city'];
$zip = $_POST['zip'];
$password = '123456';
$shipping_area = $_POST['shipment'];
$phone_number = $_POST['phone'];

foreach ($_POST as $key => $value) {
    $item[$key] = $value;
}
$item['is_this_your_Shipping_address'] = $is_this_your_Shipping_address;
$_SESSION['registration'] = $item;


if (empty($fname) || empty($lname) || empty($shipping_area) || empty($zip) || empty($password) || empty($phone_number) || empty($city) || empty($email) || empty($zip) || empty($state) || empty($streetaddress1) || empty($streetaddress2)) {
    echo "Some field missing";
    exit(0);
}

if ($state === -1) {
    echo "Please select a state";
    exit();
}

if ($shipping_area === -1) {
    echo "Please select a state";
    exit();
}


if (isset($email)) {
    $_SESSION['registration_step'] = 2;
    header("Location: password-registration.php");

}

?>