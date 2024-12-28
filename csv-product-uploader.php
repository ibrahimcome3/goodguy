<?php
include 'includes.php';
$product_colums = ['productID,description,category,vendor,brand,product_information,additional_information,shipping_returns'];

$file = fopen("csv/pp.csv","r");

    
while (($data = fgetcsv($file)) !== FALSE) {

    var_dump($data);
    $sql = "select * from productitem where productID = ". $data[0];
    if ($result = $mysqli->query($sql)) {
        if( $result->num_rows > 0){
            continue;
        }
    }
    $sql = "insert into productitem (" . implode(",", $product_colums) . ")";
    $sql .= " values ('" . implode("','", $data) . "')";
    echo $sql."<br/>";
     
     if ($result = $mysqli->query($sql)) {
    $last = $data[0];
    $dir = "products/product-" . $last . "/" . "product-" . $last . "-image/";
    //echo $dir . "<br/>";
    echo $last;
    echo "<br/>";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    $from = "copy/" . $last . '/';
    $to = $dir;
    echo "<br/>";
    //echo $from;
    // in this way glob() can give us just the file names
    foreach (glob($from . '*.{jpg,png}', GLOB_BRACE) as $name) {

        //echo $name;
        // echo $name;
        //echo $to;
        //$id = substr($name, strrpos($name, '/') + 1);
        $pos = strrpos($name, '/');
        $id = $pos === false ? $name : substr($name, $pos + 1);
        echo $id;
        copy($name, $to . $id);
    }
}
}

fclose($file);


