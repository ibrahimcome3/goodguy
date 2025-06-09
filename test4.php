<?php
session_start();
include "conn.php";

require_once 'class/Review.php';
require_once 'class/Promotion.php';

require_once 'class/User.php';
require_once 'class/Cart.php';
$r = new Review($pdo);
$p = new Promotion($pdo);
$u = new User($pdo);
$c = new Cart($pdo, $p);


var_dump($c->getCartDetails());




?>