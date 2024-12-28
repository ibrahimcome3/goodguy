<?php

include "include/conn.php";
$arr = array();
$firstselect = $_POST['firstselect'];
$secondselect = $_POST['secondselect'];
$thirdselect = $_POST['thirdselect'];

if (!empty($thirdselect)) {
    $sql = "select * from category_new where JSON_EXTRACT(`json_`, '$.roots') = '" . $firstselect . "' and  JSON_EXTRACT(`json_`, '$.subroot1') = '" . $secondselect . "' and  JSON_EXTRACT(`json_`, '$.subroot2') = '" . $thirdselect . "'";
} else {
    $sql = "select * from category_new where JSON_EXTRACT(`json_`, '$.roots') = '" . $firstselect . "' and  JSON_EXTRACT(`json_`, '$.subroot1') = '" . $secondselect . "'";

}

//echo $sql;

$result = $mysqli->query($sql);
if ($result) {
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        $arr['2'] = 'bad';
        echo json_encode($arr);
    }
}
//$sql = "SELECT * FROM `category_new` WHERE `depth` = 2 and JSON_EXTRACT(`json_`, '$.roots') = '" . $cat . "'";
//$result = $mysqli->query($sql);
//if ($result) {
//    while ($row = $result->fetch_assoc()) {
//   $arr[$row['cat_id']] = $row['categoryName'];
//    }
//}

