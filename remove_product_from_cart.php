
<?php
session_start();
if (isset($_POST['remove']) && is_numeric($_POST['remove']) && isset($_SESSION['cart']) && isset($_SESSION['cart'][$_POST['remove']])) {
    unset($_SESSION['cart'][$_POST['remove']]);
    $arr=array('msg' => 'Success');
    echo json_encode($arr);
}else{
    echo "error";
}

?>