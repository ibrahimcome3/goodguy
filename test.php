<?php
include_once "includes.php";
include "class/Invoice.php";



$a = new Invoice(82);

$b = new Order(82);


var_dump($b->getOrderItems(82));


?>