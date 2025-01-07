<?php session_start(); ?>
<?php include('datagrid-master/lazy_mofo.php');
require_once "../conn.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Your custom CSS -->
    <link href="datagrid-master/style.css" rel="stylesheet" />
</head>

<body>
    <nav class="navbar" style="background-color: azure;">

        <div class="navbar-brand" href="#">
            <img alt="Logo" width="30" height="24" style="margin-top: 2px;" class="d-inline-block align-text-top">
            <b>goodguyng.com</b>
            </a>
        </div>
    </nav>
    <div class="">
        <!-- Sidebar -->


        <!-- Page Content -->
        <main>

            <div class="row">
                <div class="col-lg-1">
                    <nav>

                        <ul class="nav flex-column">
                            <li class="nav-item"><a class="nav-link active" href="product.php">Product</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">Orders</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">Products</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">Customers</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
                            <!-- Add more sidebar items as needed -->
                        </ul>

                    </nav>
                </div>
                <div class="col">
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center  pb-2 mb-3  border-bottom">


                        <div>
                            <?php


                            $lm = new lazy_mofo($pdo, 'en-us');
                            // table name for updates, inserts and deletes
                            
                            $lm->table = 'lm_orders';
                            $lm->identity_name = 'order_id';
                            $lm->grid_sql = "SELECT `lm_orders`.`order_id`, `customer`.`customer_fname`, `lm_orders`.`order_total`, `lm_orders`.`order_total_items`, `lm_orders`.`order_status`,  `lm_orders`.`order_date_created` , `lm_orders`.`order_id` FROM `lm_orders` left join `customer` on `customer`.`customer_id` = `lm_orders`.`customer_id`";
                            //$lm->grid_sql = "SELECT * FROM `lm_orders` left join `customer` on `customer`.`customer_id` = `lm_orders`.`customer_id`";
                            

                            $lm->run();

                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="script.js"></script> <!-- Your custom JavaScript -->
</body>

</html>