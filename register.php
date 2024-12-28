<!DOCTYPE html>
<?php  
require_once "includes.php";
?>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>email - registration</title>
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
                 
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page  pt-8 pb-8 pt-md-12 pb-md-12 pt-lg-17 pb-lg-17">
            	<div class="container">
            		<div class="form-box">
            			<div class="form-tab">

                                <center><h4>Step 1 of 3</h4></center>
                                    <br/>
							    <div class="">
							        <p><b>Register</b></p>
							    	<form action="check-email-exist.php" method="post">
							    	     <?php if(isset($_GET["reply"])) { if($_GET["reply"] == "yes"){ echo "<p style='text-align: center'><span style='color: #CC0000'>Email already exist</span></p>";}  } ?>
							    		<div class="form-group">
							    			<label for="register-email-2">Your email address *</label>
							    			<input type="email" class="form-control" id="register-email-2" name="register_email" required>
							    		</div><!-- End .form-group -->



							    		<div class="form-footer">
							    			<button type="submit" class="btn btn-outline-primary-2">
			                					<span>SIGN UP</span>
			            						<i class="icon-long-arrow-right"></i>
			                				</button>

			                				<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="register-policy-2" required>
												<label class="custom-control-label" for="register-policy-2">I agree to the <a href="privacy-policy.php">privacy policy</a> *</label>
											</div><!-- End .custom-checkbox -->
							    		</div><!-- End .form-footer -->
							    	</form>
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

    <!-- Plugins JS File -->
   <?php include "jsfile.php"; ?>

<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->
</html>