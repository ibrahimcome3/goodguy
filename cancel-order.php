 <?php
require_once "includes.php";


if($orders->cancel_order($_GET['order_id'])){
	header("Location: dashboard.php#tab-orders"); 
}else{
	header("Location: dashboard.php"); 
}
	
	
  ?>