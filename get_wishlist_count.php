<?php
require_once "includes.php"; //Include your database connection and wishlist class

if (isset($_SESSION['uid'])) {
    $count = $wishlist->no_of_wish_list_item; // Use your wishlist class method
    echo $count;
} else {
    echo 0;
}
?>