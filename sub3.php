<?php
include "include/conn.php";
$arr = array();
$cat = $_POST['sub_cat_1_name'];
$sql = "SELECT * FROM `category_new` WHERE `depth` = 2 and JSON_EXTRACT(`json_`, '$.subroot1') = '" . $cat . "'";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $arr[$row['cat_id']] = $row['categoryName'];
    }
}


echo json_encode($arr);