<?php 

 include "../includes.php";

 $brand_name = $_POST['brand_name'];
 $brand_url = $_POST['brand_url'];
 $sql = "INSERT INTO `brand` (`brandID`, `Name`, `websiteURL`, `Dateadded`, `image`) VALUES (NULL, '$brand_name', '$brand_url', current_timestamp(), '');";
 echo $sql;
 if ($result = $mysqli->query($sql)) {
    echo  "new brand added created";
 }

?>