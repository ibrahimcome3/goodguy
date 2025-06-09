<?php
session_start();
//var_dump($_SESSION['cart']);
include "conn.php";
try {

    $sql = "SELECT `shipping_address_no`, `customer_id`, `address1`, `address2`, `state`, `zip`, `city` FROM `shipping_address` WHERE `customer_id`= " . $_SESSION['uid'] . "and shipping_address_no = " . $_GET['sno'];
    $res = $mysqli->query($sql);
    if ($res) {
        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
        }

    }

} catch (Exception $e) {
    echo $e->getMessage();
    exit();

}

if (!empty($_POST)) {
    $customer_id = $_SESSION['uid'];
    $streetaddress1 = $_POST['streetaddress1'];
    $streetaddress2 = $_POST['streetaddress2'];
    $country = 'NIGERIA';
    $state = $_POST['state'];
    $city = $_POST['city'];
    $zip = $_POST['zip'];

    if (empty($city)) {
        $error = "City field is empty";

    } else if (empty($zip)) {
        $error = "Zip field is empty";

    } else if (empty($customer_id)) {
        header("Location: login.php");
        exit(5);

    } else if (empty($state)) {
        $error = "State field is empty";

    } else if (empty($streetaddress1)) {
        $error = "Address line one is empty";

    } else if (empty($streetaddress2)) {
        $error = "Address line toe is empty";


    } else {
        try {
            $sql = "INSERT INTO `shipping_address` (`shipping_address_no`, `customer_id`, `address1`, `address2`, `state`, city,  `zip`) VALUES (NULL, '$customer_id', '$streetaddress1', '$streetaddress2', '$state', '$city', '$zip');";
            $res = $mysqli->query($sql);

            if ($res) {
                header("Location: shipping-address-selection.php");
            } else {
                throw new Exception('Could not submit your form. pls try again later');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            exit();

        }
    }



    var_dump($_POST);
    /*
    else{
    $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname', `customer_city`= '$city',`customer_email`= '$email',`customer_address1`='$streetaddress1',`customer_address2`='$streetaddress2',`customer_country`='$country',`customer_state`='$state',`customer_phone`= '$phone_number',`customer_zip`='$zip',`customer_status`='MEMBER',`password`='$password',`profile_image`='anonymous.jpg' WHERE customer_id = $customer_id ";
    echo $sql;
    $res = $mysqli->query($sql);
    if($res){
        header("Location: complete-registration-confermation-page.php");
    }

    }
    */
}

?>
<!DOCTYPE html>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Shippinh address registration page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header_main.php";
        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Pages</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Login</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8  pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">

                    <div class="row">
                        <div class="col-lg-9">
                            <form action="" method="post">
                                <h2 class="checkout-title">Edit your address</h2><!-- End .checkout-title -->
                                <label>Street address *</label>
                                <input type="text" class="form-control" name="streetaddress1" value=<?= $row['address1'] ?> placeholder="House number and Street name" required>
                                <input type="text" class="form-control" name="streetaddress2" value=<?= $row['address2'] ?> placeholder="Appartments, suite, unit etc ..." required>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>Town / City *</label>
                                        <input type="text" value=<?= $row['city'] ?> name="city" class="form-control"
                                            required>
                                    </div><!-- End .col-sm-6 -->

                                    <div class="col-sm-6">
                                        <label>State *</label>

                                        <select name="state" id="state" class="form-control">
                                            <option value="-1">select state </option>
                                            <?php
                                            $ship = new Shipment();
                                            $s = $ship->get_shipment_state();
                                            while ($row = mysqli_fetch_array($s)) {
                                                var_dump($row);
                                                ?>

                                                <option value="<?= $row['state_id'] ?>">
                                                    <?= $row['state_name'] ?>

                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div><!-- End .col-sm-6 -->
                                </div><!-- End .row -->

                                <div class="row">
                                    <div class=" col-sm-6">
                                        <label for="sortby" style="color: blue;">Select shipping area *</label>

                                        <select name="shipment" id="shipment" class="form-control">
                                            <option shipment-price="0.00" value="-1">select shipping
                                                cost</option>
                                            <?php
                                            $ship = new Shipment();
                                            $s = $ship->get_shipment_area();
                                            while ($row = mysqli_fetch_array($s)) {
                                                ?>

                                                <option shipment-price="<?= $row['area_cost'] ?>"
                                                    value="<?= $row['area_id'] ?>">
                                                    <?= $row['area_name'] ?>
                                                    <?= "(N" . $row['area_cost'] . ")" ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>Postcode / ZIP *</label>
                                        <input type="text" name="zip" value=<?= $row['zip'] ?> class="form-control">
                                    </div><!-- End .col-sm-6 -->

                                </div><!-- End .row -->
                                <button type="submit" class="btn btn-primary btn-round">
                                    <span>Save</span><i class="icon-long-arrow-right"></i>
                                </button>
                            </form>
                        </div><!-- End .col-lg-9 -->

                    </div><!-- End .row -->



                </div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->

        <footer class="footer">

            <?php include "footer.php"; ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; ?>

    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>

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
</body>


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

</html>