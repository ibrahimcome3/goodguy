<!DOCTYPE html>
 
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Password Reset Page</title>
	<?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
    <div class="container">

               <?php

         include "header-for-other-pages.php";
        ?>

                   </div>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                          <?php  //echo breadcrumbs();  ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->
             <div class="container">
            <div class="login-page" >
                <div class="container">
            		<div class="form-box" style="border: 1px solid blue">
            			<div class="form-tab">
	            			<ul class="nav nav-pills nav-fill" role="tablist">
							    <li class="nav-item">
							        <a class="nav-link">Reset your Password</a>
							    </li>
							</ul>
							<div class="tab-content">
							        <?php if(isset($_GET['er'])){ ?>
							        <div style="margin-top: 10px;"><center><h5><span class="badge text-bg-danger rounded-pill"><?php if(isset($_GET['er'])){ echo $_GET['er'];} ?></span></h5></center></div>
							    	<?php } ?>
									<form action="process/_recovery.php" method="post">
							    		<div class="form-group">
							    			<label for="singin-email-2">Email address *</label>
							    			<input type="text" class="form-control" id="singin-email-2" name="email" required>
							    		</div><!-- End .form-group -->
							    		<div class="form-footer">
							    			<button type="submit" name="submit" class="btn btn-outline-primary-2">
			                					<span>RESET PASSWORD</span>
			            						<i class="icon-long-arrow-right"></i>
			                				</button>

							    		</div><!-- End .form-footer -->
							    	</form>
							    
							</div><!-- End .tab-content -->
						</div><!-- End .form-tab -->
            		</div><!-- End .form-box -->
            	</div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
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