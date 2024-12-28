<?php 
include "includes.php";

if(isset($_SESSION['cart'])){
$_SESSION['cart_total'] = 0;
$products_in_cart = $_SESSION['cart'];

foreach($_SESSION['cart'] as $key => $val){
$result = $mysqli->query("SELECT * FROM inventoryitem WHERE InventoryItemID = $key");
$row = mysqli_fetch_assoc($result); 
//echo $products_in_cart[$key]."<br/>";
 if (array_key_exists($key, $_SESSION['cart'])) {

                                                    // Product exists in cart so just update the quanity
            if($promotion->check_if_item_is_in_inventory_promotion($key)){
               $_SESSION['cart_total'] += $promotion->get_promoPrice_price($key) * $products_in_cart[$key];
              }else{
              $_SESSION['cart_total'] += $row['cost'] * $products_in_cart[$key];
              }
          }

}

echo "&#8358; ". number_format($_SESSION['cart_total'], 2);
}else{
echo "&#8358; ". number_format(0.00, 2);
}
?>