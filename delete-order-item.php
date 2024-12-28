<?php
include "includes.php";
//var_dump($_SESSION);

include  'conn.php';
if(isset($_SESSION['uid'])){
$uid = $_SESSION['uid'];
if(isset($_GET['oid']) and isset($_GET['oitem'])){
if($orders->remove_order_item_from_an_order($_GET['oid'],$_GET['oitem'])){
    echo header("Location: order_detail.php?order_id=".$_GET['oid']);

}else{
header("Location: login.php");
}
}
}

?>