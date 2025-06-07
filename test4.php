<?php
include "conn.php";

require_once 'class/Review.php';

require_once 'class/User.php';
$r = new Review($pdo);

var_dump($r->getPaginatedReviewsByProduct(317, 1, 2));




?>