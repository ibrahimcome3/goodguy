<?php
include "../includes.php";
$cat = $_POST['category'];
$sql = "INSERT INTO `category_new` (`cat_id`, `categoryName`, `cat_path`, `depth`, `json_`) VALUES (NULL, '$cat', '1', '1', '{\"root\":\"$cat\"}')";
$result = $mysqli->query($sql);
try{
if ($result) {
    $lastid = $mysqli->insert_id;
    $sql = "update `category_new` set cat_path  = $lastid where cat_id = $lastid";
    $result = $mysqli->query($sql);
    if ($result) {
        $arr = array('true' => "category succesfuly inserted into the database");
        echo json_encode($arr);
    }

}
}catch (Exception $ex) {
    if (str_contains($ex->getMessage(), 'Duplicate entry')) {
    echo 'Duplicate Entry';
} 

}