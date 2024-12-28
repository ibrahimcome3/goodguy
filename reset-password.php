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


               <?php

         include "header-for-other-pages.php";
        ?>

                 
        <main class="main">

<?php
                if($_GET['email'] && $_GET['token']) {
                    include "conn.php";
                    $email = $_GET['email'];
                    $token = $_GET['token'];

                    $query = mysqli_query($mysqli, "SELECT * FROM `customer` WHERE `reset_link_token`='".$token."' and `customer_email`='".$email."';");

                    $current_date = date("Y-m-d H:i:s");

                    if (mysqli_num_rows($query) > 0) {

                        $row = mysqli_fetch_array($query);

                        if($row['expiry_date'] >= $current_date) { ?>
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
                            <form action="process/update-password.php" method="post">
                                <input type="hidden" name="email" value="<?php echo $email; ?>">
                                <input type="hidden" name="reset_link_token" value="<?php echo $token; ?>">
                                <div class="form-group">
                                    <label for="new-password">Password</label>
                                    <input type="password" name="password" class="form-control"  id="new-password">
                                </div>                
                                <div class="form-group">
                                    <label for="confirm-password">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control"  id="confirm-password">
                                </div>
                                <input type="submit" name="submit" class="btn btn-outline-primary-2">
                            </form>
                        </div>
                        <?php } 
                    } else {
                        echo "<p>This forget password link has been expired</p>"; exit();
                    }
                }else{
                  header("Location: password-reset.php?error=incorrect email or security token");  
                  exit();
                }
?>

      	</div><!-- End .tab-content -->
						</div><!-- End .form-tab -->
            		</div><!-- End .form-box -->
            	</div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
       
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