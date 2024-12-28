<?php
 include "../includes.php";
$arr = array();
$cat = $_POST['cat_1_name'];
$sql = "SELECT * FROM `category_new` WHERE `depth` = 2 and JSON_EXTRACT(`json_`, '$.roots') = '" . $cat . "'";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $arr[$row['cat_id']] = $row['categoryName'];
    }
}


echo json_encode($arr);