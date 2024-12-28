<?php
session_start();
require_once  'conn.php';

   $sql = "DELETE FROM `wishlist` WHERE `inventory_item_id` = ".$_POST['remove']."and  `customer_id` = ".$_SESSION['uid'];
//$sql = "DELETE FROM `wishlist` WHERE `inventory_item_id` = 8 and  `customer_id` = ".$_SESSION['uid'];
 //echo $sql;
   $result = $mysqli->query($sql);
   if($result){
   $arr=array('msg' => 'Success');
    echo json_encode($arr);
}else{
   $arr=array('error' => 'error');
     echo json_encode($arr);

}



?>