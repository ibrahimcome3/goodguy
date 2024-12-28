<!DOCTYPE html>
<?php
require_once "includes.php";

if (isset($_SESSION['uid'])) {
    header("Location: shipping-address-selection.php");
    exit();
}

?>


<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Validation page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header-for-other-pages.php";
        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page  pt-8 pb-8 pt-md-12 pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">
                    <div class="form-box">
                        <div class="form-tab">

                            <div class="center">
                                <div class="row"><a href="login.php" style=""
                                        class="btn btn-primary btn-shadow form-control">Login</a></div>
                                <p style="text-align: center; margin-bottom: 8px;"><b>or</b></p>
                                <div class="row"><a href="register.php" style=""
                                        class="btn btn-primary btn-shadow form-control ">Sign Up</a></div>
                            </div><!-- .End .tab-pane -->

                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
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
    <?php include "jsfile.php"; ?>



</body>


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

</html>