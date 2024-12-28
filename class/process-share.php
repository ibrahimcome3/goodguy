<?php
session_start();
 require_once "class/class-shared.php";
 $s = new Share();
  $s->share($_GET["tid"]);
 
?>