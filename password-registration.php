<!DOCTYPE html>
<?php
include "includes.php";
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
    header("location: register.php"); // Redirect to the appropriate step if incomplete
    exit();
}
?>
<html lang="en">


<!-- molla/checkout.html  22 Nov 2019 09:55:06 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Password Registration Setup</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <br />
        <?php include "header-for-other-pages.php"; ?>
        <main class="main">
            <div class="page-content">
                <center>
                    <h4>Step 3 of 3</h4>
                    <?php if (isset($_SESSION['error_message']))
                        echo $_SESSION['error_message']; ?>
                </center>
                <div class="checkout">
                    <div class="container">
                        <form action="password-reg-processor.php" method="post">
                            <div class="row">
                                <div class="col-lg-9">
                                    <h2 class="checkout-title">Set your Password</h2><!-- End .checkout-title -->
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label>Password *</label>
                                            <input type="password" name="p1" class="form-control" required>
                                        </div><!-- End .col-sm-6 -->
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label>Confirm Password *</label>
                                            <input type="password" name="p2" class="form-control" required>
                                        </div><!-- End .col-sm-6 -->
                                    </div><!-- End .row -->
                                    <button type="submit" href="#" class="btn btn-outline-dark btn-rounded"><i
                                            class="icon-long-arrow-right"></i><span>Complete
                                            Registration</span></button>

                                </div><!-- End .col-lg-9 -->

                            </div><!-- End .row -->
                        </form>
                    </div><!-- End .container -->
                </div><!-- End .checkout -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <footer class="footer">

            <?php include "footer.php"; ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; ?>
    <?php include "login-module.php"; ?>
    <?php include "jsfile.php"; ?>
</body>


<!-- molla/checkout.html  22 Nov 2019 09:55:06 GMT -->

</html>