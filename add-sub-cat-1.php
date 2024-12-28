<?php

include "include/conn.php";
$cat = $_POST['category'];


$sub_cat_1 = $_POST['sub_category_1'];
$sub_cat_name = $_POST['sub_category_1_name'];
$concat = $sub_cat_1 . '\\' . $cat;

$sql = "INSERT INTO `category_new` (`cat_id`, `categoryName`, `cat_path`, `depth`, `json_`) VALUES (NULL, '$cat', '$concat', '2', '{\"roots\":\"$sub_cat_name\", \"subroot1\":\"$cat\"}')";
echo $sql;
$result = $mysqli->query($sql);
if ($result) {
    $lastid = $mysqli->insert_id;
    $c = $sub_cat_1 . '/' . $lastid;
    $sql = "update `category_new` set cat_path  ='" . $c . "' where cat_id = $lastid";
    echo $sql;
    $result = $mysqli->query($sql);
    if ($result) {
        $arr = array('true' => 1);
        echo json_encode($arr);
    }

}