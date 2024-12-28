<?php
 include "includes.php";
$arr = array();
$id = $_POST['state_id'];
$sql = "SELECT * FROM `shipping_areas`  where state_id = $id";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $arr[$row['area_id']] = $row['area_name'];
    }
}


echo json_encode($arr);