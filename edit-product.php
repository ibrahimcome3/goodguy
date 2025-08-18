<?php
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Category.php';
require_once __DIR__ . '/../class/Vendor.php';

session_start();

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$productId) {