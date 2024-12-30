<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Password Reset Page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">

        <?php include "header-for-other-pages.php"; ?>

        <main class="main">
            <div class="container">
                <div class="login-page">
                    <div class="container">
                        <?php

                        if (isset($_GET['error'])) {
                            $error = $_GET['error'];
                            switch ($error) {
                                case 'password_mismatch':
                                    echo '<div class="alert alert-danger" role="alert">Passwords do not match. Please try again.</div>';
                                    break;
                                case 'invalid_token':
                                    echo '<div class="alert alert-danger" role="alert">Invalid or expired password reset link.</div>';
                                    break;
                                case 'database': //add this case for database error
                                    echo '<div class="alert alert-danger" role="alert">There was a database error. Please try again later.</div>';
                                    break;
                                default:
                                    echo '<div class="alert alert-danger" role="alert">An unknown error occurred.</div>';
                                    break;
                            }
                        }
                        ?>
                        <?php if (!isset($_GET['error'])) { ?>
                            <div class="form-box" style="border: 1px solid blue">
                                <div class="form-tab">
                                    <ul class="nav nav-pills nav-fill" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active">Reset your Password</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">

                                        <?php
                                        if (isset($_GET['user_id']) && isset($_GET['token'])) {
                                            include "conn.php";
                                            $user_id = $_GET['user_id'];
                                            $token = $_GET['token'];

                                            $query = mysqli_query($mysqli, "SELECT * FROM `customer` WHERE `reset_link_token`='$token' and `customer_id`='$user_id'");

                                            $current_date = date("Y-m-d H:i:s");

                                            if (mysqli_num_rows($query) > 0) {
                                                $row = mysqli_fetch_array($query);

                                                if ($row['expiry_date'] >= $current_date) {
                                                    ?>
                                                    <form action="process/update-password.php" method="post">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['customer_id']; ?>">
                                                        <input type="hidden" name="reset_link_token" value="<?php echo $token; ?>">
                                                        <div class="form-group">
                                                            <label for="new-password">Password</label>
                                                            <input type="password" name="password" class="form-control"
                                                                id="new-password" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="confirm-password">Confirm Password</label>
                                                            <input type="password" name="confirm_password" class="form-control"
                                                                id="confirm-password" required>
                                                        </div>
                                                        <input type="submit" name="submit" class="btn btn-outline-primary-2"
                                                            value="Reset Password">
                                                    </form>

                                                    <?php
                                                } else {
                                                    echo '<div class="alert alert-danger" role="alert">This password reset link has expired.
                                                <a href="password-reset.php">click here to reset the password again</a>
                                                </div>';
                                                }
                                            } else {
                                                echo '<div class="alert alert-danger" role="alert">Invalid or expired reset link.</div>';
                                            }
                                        } else {
                                            echo '<div class="alert alert-danger" role="alert">Missing required parameters.</div>';
                                        }
                                        ?>

                                    </div><!-- End .tab-content -->
                                </div><!-- End .form-tab -->

                            <?php } ?>

                        </div><!-- End .form-box -->


                    </div><!-- End .container -->
                </div><!-- End .login-page section-bg -->
            </div>
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php" ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->


    <?php include "jsfile.php"; ?>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>

</body>

</html>