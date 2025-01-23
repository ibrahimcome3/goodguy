<!DOCTYPE html>
<?php
require_once "includes.php";

if (isset($_SESSION['uid'])) {

    $resultsPerPage = 10; // Number of results per page
    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;

    // Get total count of wishlist items for the current user
    $countSql = "SELECT COUNT(*) AS total FROM `wishlist` WHERE customer_id = " . $_SESSION['uid'];
    $countResult = $mysqli->query($countSql);
    $totalCount = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalCount / $resultsPerPage);

    $offset = ($currentPage - 1) * $resultsPerPage;
    require_once 'conn.php';
    $sql = "SELECT * FROM `wishlist` LEFT JOIN inventoryitem ON wishlist.`inventory_item_id` = inventoryitem.`InventoryItemID` WHERE customer_id = " . $_SESSION['uid'] . " LIMIT $offset, $resultsPerPage";
    $result = $mysqli->query($sql);
} else {
    header("Location: login.php");
    exit();
}

?>
<html lang="en">



<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Wish list</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header-for-other-pages.php";
        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <table class="table table-wishlist table-mobile">
                        <?php if ($result && $result->num_rows > 0) { ?>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock Status</th>
                                    <th>qty</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                        <?php } ?>
                        <tbody>

                            <?php
                            if ($result && $result->num_rows > 0) {


                                while ($row = mysqli_fetch_array($result)) {

                                    $old_cost = null;
                                    if ($promotion->check_if_item_is_in_inventory_promotion($row['inventory_item_id'])) {
                                        $row['cost'] = $promotion->get_promoPrice_price($row['inventory_item_id']);
                                        $old_cost = $promotion->get_regular_price($row['inventory_item_id']);
                                    }

                                    $formattedCost = number_format($row["cost"], 2, '.', ','); // 2 decimal places, . as decimal, , as thousands
                            
                                    ?>
                                    <tr>
                                        <td class="product-col">
                                            <div class="product">
                                                <figure class="product-media">
                                                    <a href="product-detail.php?itemid=<?= $row['inventory_item_id'] ?>">
                                                        <img src="<?php echo getImage($row['inventory_item_id']); ?>"
                                                            alt="Product image">
                                                    </a>
                                                </figure>

                                                <h3 class="product-title">
                                                    <a href="product-detail.php?itemid=<?= $row['inventory_item_id'] ?>">
                                                        <?= $row["description"] ?></a>
                                                </h3><!-- End .product-title -->
                                            </div><!-- End .product -->
                                        </td>
                                        <td class="price-col" style="letter-spacing: 2px;">&#8358;<?= $formattedCost ?>

                                            <?php
                                            if (isset($old_cost) and ($old_cost !== $row["cost"])) {
                                                $formattedOldCost = number_format($old_cost, 2, '.', ','); // Format old cost too
                                                echo "<small><span style=\"text-decoration: line-through\">$formattedOldCost</span></small>";
                                            }
                                            ?>
                                        </td>
                                        <td class="stock-col"><span class="in-stock">In stock</span></td>
                                        <!--	<td class="stock-col"><span class="out-of-stock">In stock</span></td>
                               <!--	<td class="action-col">
                                    <button class="btn btn-block btn-outline-primary-2 disabled">Out of Stock</button>
                                </td>
                                -->
                                        <td class="action-col">

                                            <form class="cart_form" action="cart.php" method="post">

                                                <div>
                                                    <input type="hidden" name="inventory_product_id"
                                                        value="<?= $row['inventory_item_id'] ?>">
                                                    <div class="product-details-quantity"
                                                        style="margin-bottom: 5px; width: 100%;">
                                                        <input name="qty" type="number"
                                                            id="qty-<?= $row['inventory_item_id'] ?>" class="form-control"
                                                            value="1" min="1" max="10" step="1" data-decimals="0" required />
                                                    </div>





                                                </div>

                                            </form>
                                        </td>
                                        <td>
                                            <button class="submit-cart  btn btn-block btn-outline-primary-2"
                                                style="border: none;"><i class="icon-cart-plus"></i>Add to Cart</button>
                                        </td>
                                        <td class="remove-col align-right" style="margin-right: 5px;">
                                            <a href="#" class="remove-from-wishlist"
                                                data-wishlist-id="<?= $row['wishlistid'] ?>">Remove</a>


                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr>
                                    <td>
                                        <div class="alert alert-dark" role="alert">
                                            <center>No wishlist items found</center>
                                        </div>
                                    </td>
                                </tr>

                            <?php }

                            ?>
                        </tbody>
                    </table><!-- End .table table-wishlist -->
                    <?php
                    if ($totalPages > 1) { // Only show pagination if there's more than one page
                        ?>
                        <nav aria-label="Page navigation example">
                            <ul class="pagination justify-content-center">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="sr-only">Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo ($currentPage == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>



                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                            <span class="sr-only">Next</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php } ?>
                    <!--	<div class="wishlist-share">
                        <div class="social-icons social-icons-sm mb-2">
                            <label class="social-label">Share on:</label>
                            <a href="#" class="social-icon" title="Facebook" target="_blank"><i class="icon-facebook-f"></i></a>
                            <a href="#" class="social-icon" title="Twitter" target="_blank"><i class="icon-twitter"></i></a>
                            <a href="#" class="social-icon" title="Instagram" target="_blank"><i class="icon-instagram"></i></a>
                            <a href="#" class="social-icon" title="Youtube" target="_blank"><i class="icon-youtube"></i></a>
                            <a href="#" class="social-icon" title="Pinterest" target="_blank"><i class="icon-pinterest"></i></a>
                        </div>
                    
                    </div>
                -->
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <footer class="footer">


            <?php include "footer.php"; ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <div class="mobile-menu-container">
        <div class="mobile-menu-wrapper">
            <span class="mobile-menu-close"><i class="icon-close"></i></span>

            <form action="#" method="get" class="mobile-search">
                <label for="mobile-search" class="sr-only">Search</label>
                <input type="search" class="form-control" name="mobile-search" id="mobile-search"
                    placeholder="Search in..." required>
                <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
            </form>
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->

    <!-- Sign in / Register Modal -->


    <!-- Plugins JS File -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="login.js"></script>
    <script>
        $(document).ready(function () {
            $(".submit-cart").click(function (event) {  // Use class selector, more robust
                event.preventDefault(); // Prevent default form submission

                var inventory_product_id = $(this).closest('tr').find('input[name="inventory_product_id"]').val();
                var qty = $(this).closest('tr').find('input[name="qty"]').val();
                $.ajax({
                    type: 'POST',
                    url: 'cart-ajax.php',
                    data: {
                        inventory_product_id: inventory_product_id,
                        qty: qty
                    },
                    success: function (response) {
                        if (response.success) { // Check for success in JSON response
                            alert(response.message); // Display success message from server

                            //Optionally update cart count or other elements
                        } else {
                            alert("Error: " + response.message); // Handle errors gracefully
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText); // Log error for debugging
                        alert("An error occurred adding to cart."); // Display general error
                    }
                });
            });

            /*   $("button.btn-remove").click(function (event) {
                   event.preventDefault();
                   var del_title = $(this).attr('cart-item-id');
                   var item_to_removed = $(this).closest('tr');
                   console.log("About to delete itme : " + del_title);
                   $.ajax({
                       type: 'POST',
                       cache: false,
                       url: 'https://goodguyng.com/wishlist.php/remove_product_from_watch_list.php',
                       dataType: "json",
                       data: { remove: del_title },
                       success: function (data) {
                           alert(data);
                           item_to_removed.remove();
                           location.reload(true);
                       }
                   });
               });
           */

            $('.remove-from-wishlist').click(function (event) {
                event.preventDefault(); // Prevent the default link behavior

                const wishlistItemId = $(this).data('wishlist-id');
                const itemRow = $(this).closest('tr'); // Store the row for easy removal later

                // Confirmation dialog
                if (confirm("Are you sure you want to remove this item from your wishlist?")) {
                    $.ajax({
                        type: "POST",
                        url: "remove-from-wishlist.php",
                        data: JSON.stringify({ wishlist_id: wishlistItemId }),
                        contentType: "application/json",
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                itemRow.remove();
                                $.ajax({
                                    url: "get_wishlist_count.php", //This file needs to be created
                                    type: "GET",
                                    success: function (data) {
                                        $('.wishlist-count').text(data);

                                    },
                                    error: function (error) {
                                        console.error("Error updating wishlist count:", error);
                                        alert("Error updating wishlist count. Please try again.");
                                    }
                                });

                            } else {
                                alert("Error removing from wishlist: " + response.message);
                            }
                        },
                        error: function (error) {
                            alert("An error occurred during removal.");
                            console.error(error); // Log the error for debugging
                        }
                    });
                }
            });
        });
    </script>
</body>



<!-- molla/wishlist.html  22 Nov 2019 09:55:06 GMT -->

</html>