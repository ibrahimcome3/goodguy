<?php
session_start();
//var_dump($_SESSION);

require_once  'C:\wamp64\www\lm\conn.php';
$item = $_GET["itemid"];

if(isset($_SESSION['uid'])){
$uid = $_SESSION['uid'];
if(isset($_GET['oid']) and isset($_GET['oitem'])){
if(->remove_order_item_from_an_order($_GET['oid'],$_GET['oitem'])){
    echo "Wish list registred";

}else{
header("Location: login.php");
}
}
}

?>