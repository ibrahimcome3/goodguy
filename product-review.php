<?php require_once "includes.php";   ?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Review Page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <script src="rateit.js-master\rateit.js-master\scripts\jquery.rateit.js"></script>
    <link href="//rawgit.com/gjunge/rateit.js/master/scripts/rateit.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div class="page-wrapper">
        <?php
         include "header-for-other-pages.php";
        ?>

        <main class="main">

            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">
                <div class="container">
                    <ol class="breadcrumb">
                          <?php  echo breadcrumbs();  ?>
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
                            <select id="backing15b"  name="rate" data-rateit-valuesrc="index">
                                <option value="">none</option>
                                <option value="bad">Bad</option>
                                <option value="ok" selected="selected">OK</option>
                                <option value="great">Great</option>
                                <option value="good">Good</option>
                                <option value="excellent">Excellent</option>
                            </select>
                                    <div class="rateit" data-rateit-backingfld="#backing15b"></div>
                                    <label for="reply-message" class="sr-only">Review</label>
                                    <input  value =<?= $_GET['product_id']; ?> hidden="hidden" name='icudrop' />
                                    <input  value =<?= $_GET['inventory-item']; ?> hidden="hidden" name='inventory-item' />
                                    <textarea name="reply-message" id="reply-message" cols="30" rows="4" class="form-control" required placeholder="Product Review *"></textarea>
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
</body>



</html>