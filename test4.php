<?php
include "conn.php";

require_once 'class/Order.php';


$o = new Order();

echo $o->deleteOrderItem($mysqli, 3);
?>