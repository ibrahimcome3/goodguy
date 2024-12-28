<!DOCTYPE HTML>
<?php require_once "includes.php"; ?>
<html lang="en">
<!-- molla/about-2.html  22 Nov 2019 10:03:54 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>About Us</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
    <?php
         include "header-for-other-pages.php";
    ?>

        <main class="main">
            <div class="container">
									<div class="text-center"><h2><b>Recover Account</b></h2></div>
									<form  action="process/_recovery.php" method="post" role="form" autocomplete="off">
										<div class="form-group">
											<label for="email">Email Address</label>
											<input type="email" name="email" id="email" tabindex="1" class="form-control" placeholder="Email Address"  autocomplete="off" required="">
										</div>
										<div class="form-group">
											<div class="row">
												<div class="col-lg-6 col-lg-offset-3 col-md-6 col-md-offset-3 col-sm-12 col-xs-12">
													<input type="submit" name="submit" id="recover-submit" tabindex="2" class="form-control btn btn-success" value="Recover Account">
												</div>
                                           
											</div>
										</div>
										<input type="hidden" class="hide" name="token" id="token" value="9c32b1c06a319e3e293a63b5ecce5f39">
									</form>
							  </div>
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

    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>
</body>


<!-- molla/about-2.html  22 Nov 2019 10:04:01 GMT -->
</html>