<!DOCTYPE html>

<?php
if ($_GET['success'] != '1') {
    header("Location: password-reset.php");
    exit();
}
?>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Password Success Page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
</head>

<body>
    <div class="page-wrapper">
        <div class="container">

            <?php

            include "header_main.php";
            ?>

        </div>
        <main class="main">
            <div class="container">
                <br />
                <?php echo '<div class="alert alert-primary" role="alert">Your Password was reset successfully.</div>'; ?>
                <div class="login-page"><a href="login.php">click here to login</a></div>
            </div>
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php" ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <?php include "mobile-menue-index-page.php"; ?>


    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>

    <?php include "jsfile.php"; ?>
</body>

</html>