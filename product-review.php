<?php
session_start();
require_once "includes.php";
if (!isset($_SESSION['uid'])) {
    // Store the intended destination to redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?message=Please login to write a review.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Review Page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link href="node_modules/star-rating.js/dist/star-rating.css" rel="stylesheet">

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">

</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header_main.php";
        ?>

        <main class="main">

            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-9">
                            <div class="comments">
                                <h3 class="title">Leave a review</h3><!-- End .title -->
                            </div><!-- End .comments -->
                            <div class="reply">

                                <form action="product-review-submitter.php" method="post">
                                    <div class="form-group">
                                        <label for="review-title">Review Title</label>
                                        <input type="text" class="form-control" id="review-title" name="review_title"
                                            placeholder="e.g., Great product! (Optional)" maxlength="250">
                                    </div>

                                    <div class="form-group">
                                        <label>Your Rating *</label>
                                        <!-- The star-rating.js library will enhance this select element -->
                                        <div>
                                            <select class="star-rating" name="rate" required>
                                                <option value="">Select a rating</option>
                                                <option value="5">Excellent</option>
                                                <option value="4">Very Good</option>
                                                <option value="3">Average</option>
                                                <option value="2">Fair</option>
                                                <option value="1">Poor</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="reply-message">Your Review *</label>
                                        <textarea name="reply-message" id="reply-message" cols="30" rows="4"
                                            class="form-control" required
                                            placeholder="Write your review here..."></textarea>
                                    </div>

                                    <!-- Hidden fields -->
                                    <input value="<?= htmlspecialchars($_GET['product_id'] ?? '') ?>" type="hidden"
                                        name='icudrop' />
                                    <input value="<?= htmlspecialchars($_GET['inventory-item'] ?? '') ?>" type="hidden"
                                        name='inventory-item' />

                                    <button type="submit" class="btn btn-outline-primary-2">
                                        <span>POST REVIEW</span>
                                        <i class="icon-long-arrow-right"></i>
                                    </button>
                                </form>
                            </div><!-- End .reply -->
                        </div><!-- End .col-lg-9 -->
                        <!-- End .col-lg-3 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .page-content -->
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
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    <script src="node_modules/star-rating.js/dist/star-rating.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var stars = new StarRating('select.star-rating', {
                // any specific options for star-rating.js can go here
            });
        });
    </script>
</body>



</html>