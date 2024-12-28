<?php
$host="localhost"; // Host name
$username="voicefol_maryam";
$password="hBSCTFXCk@~C"; // Mysql password
$db_name="voicefol_voicefold"; // Database name


$mysqli =  mysqli_connect("$host","$username","$password", "$db_name")or die("cannot connect");
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
} ?>

