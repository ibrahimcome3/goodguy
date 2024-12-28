<?php 

 include "../includes.php";

 $cat_name = $_POST['cat_name'];
 $cat_depth = $_POST['cat_depth'];
 $cat_length = $_POST['cat_length'];
 $cat_jason = mysqli_real_escape_string($mysqli,$_POST['cat_jason']);
 $sql = "INSERT INTO `category_new`(`cat_id`, `categoryName`, `cat_path`, `depth`, `json_`) values (null,'$cat_name','$cat_depth','$cat_length','$cat_jason')";
 echo $sql;

 if ($result = $mysqli->query($sql)) {
    echo  "new category created";
 }

?>