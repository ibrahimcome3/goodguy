<?php
session_start();
if(isset($_SESSION['cart'])){
echo count($_SESSION['cart']);
}else{
echo 0;
}
?>