<!DOCTYPE HTML>
<?php require_once "includes.php";
require_once "conn.php";
?>
<html lang="en">


<!-- molla/about-2.html  22 Nov 2019 10:03:54 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <title>Contact</title>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <style>
        .gfg li {
            margin-bottom: 20px;
        }
    </style>

</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header_main.php";
        ?>

        <main class="main">
            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">
                <div class="container">
                    <h1 class="page-title">Contact us at GoodGuyng.com</h1>
                </div><!-- End .container -->
            </div><!-- End .page-header -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->



            <section>



                <div class="container">
                    <h3>Frequently asked questions (FAQs)</h3>
                    <ul class="gfg">
                        <li>
                            <p><b>Why should I sell on Goodguyng?</b></p>
                            <p>Goodguy is a trusted e-commerce platform with over thousands of customers across Nigeria.
                                By selling on Goodguy, you gain access to a vast customer base. The cost of doing
                                business on Goodguy is also low, providing a lucrative opportunity for sellers.</p>
                        </li>
                        <li>
                            <p><b>Can I offer both products and services on Goodguyng.com?</b></p>
                            <p>Currently, Goodguy allows sellers to offer only physical products for sale on the
                                platform. However, as a third-party service provider, you can offer specific services to
                                Godguyng.com sellers to assist them in growing their businesses.</p>
                        </li>
                        <li>
                            <p></b><b>Who decides the price of my products?</b></p>
                            <p>As a seller on goodguyng.com, you have full control over the pricing of your products.
                                You can set the price based on your business strategy and the market dynamics. The
                                seller dashboard also provides analysis and recommendations to help you determine the
                                optimal price for your products.</p>
                        </li>
                        <li>
                            <p><b>What are the charges for selling on goodguyng.com?</b></p>
                            <p>goodguyng.com does not charge any fees for listing your products on its platform.
                                However, upon a successful sale, there is a small marketplace fee applicable as a
                                percentage of the selling price. You can refer to the goodguyng.com Seller Fee details
                                for more information.</p>
                        </li>
                        <li>
                            <p><b>Will I get charged for listing products on goodguyng.com?</b></p>
                            <p>No, there are no charges for listing your products on goodguyng.com. Listing your
                                products is free of cost.</p>
                        </li>
                        <li>
                            <p><b>How and when do I get paid?</b></p>
                            <p>Once your product is picked up and successfully delivered to the customer, you will
                                receive payment within as fast as 7* days. Payments are securely and regularly
                                transferred directly to your registered bank account after deducting the relevant
                                goodguyng fees.</p>
                        </li>

                        <li>
                            <p><b>Do you offer protection against damaged or missing goods from seller?</b></p>
                            <p>Sellers are eligible for monetary compensation for orders where the returned products
                                have been damaged or missing.</p>
                        </li>

                        <li>
                            <p><b>I am having trouble during registration. Can I get some help?</b></p>
                            <p>If you are facing any issues during the registration process, please provide your details
                                in our contact page. Our team will promptly assist you with your registration.</p>
                        </li>
                    </ul>

                </div>
            </section>
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