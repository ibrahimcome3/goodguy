<?php
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/ProductItem.php';

$p = new ProductItem($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_product') {
        //$result = $p->deleteProduct($mysqli, $_POST['product_id']);
        $result = $p->deleteProductCompletely($mysqli, $_POST['product_id']);
        $response = ['success' => $result ? true : false, 'message' => $result ? 'Product deleted successfully!' : 'Error deleting product'];
    } else {
        //Handle edit request here.
        //You'll probably need to create an edit form and handle the edit logic here.
        $response = ['success' => false, 'message' => 'Edit functionality not implemented'];
    }
    echo json_encode($response);
    exit();
}


?>