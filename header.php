<?php
// Start the session at the VERY beginning of the file!
session_start();

// ... (Rest of your header.php code) ...
?>
<header class="header header-10 header-intro-clearance">
    <div class="header-top">
        <div class="container header-dropdown">
            <div class="header-left">
                <a href="tel:#"><i class="icon-phone"></i>Call: +2348051067944</a>
            </div><!-- End .header-left -->

            <div class="header-right">

                <ul class="top-menu">
                    <li>

                        <ul>
                            <li class=""><a href="about.php">About us</a></li>
                            <?php if (!isset($_SESSION["uid"])) { ?>
                                <li class="login"><a href="login.php">Sign in / Sign up</a></li>
                            <?php } else { ?>
                                <li class="login"><a href="logout.php">log out</a></li>
                                <li class="login"><a href="dashboard.php"><i class="icon-user"></i>Dashboard</a></li>
                            <?php } ?>

                        </ul>
                    </li>
                </ul><!-- End .top-menu -->
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-top -->

    <div class="header-middle">
        <div class="container">
            <div class="header-left h-100 d-flex align-items-center justify-content-center">
                <button class="mobile-menu-toggler">
                    <span class="sr-only">Toggle mobile menu</span>
                    <i class="icon-bars"></i>
                </button>

                <a href="index.php" class="logo">
                    <div class="h-100 d-flex align-items-center justify-content-center">
                        <div style="color: red"><img src="assets/images/goodguy.svg" alt="goodguyng.com logo"
                                width="30"></div>
                        <div style="margin-left: 10px; 
                                    font-size: 20px; color: black; margin-top:-8px; font-weight: bold;">
                            goodguyng.com</div>
                    </div>
                </a>
            </div><!-- End .header-left -->

            <div class=" header-center">
                <div
                    class="header-search header-search-extended header-search-visible header-search-no-radius d-none d-lg-block">
                    <a href="" class="search-toggle" role="button"><i class="icon-search"></i></a>
                    <form action="product-search.php" method="get">
                        <div class="header-search-wrapper search-wrapper-wide">

                            <label for="q" class="sr-only">Search</label>
                            <input type="search" class="form-control" name="q" id="q" placeholder="Search product ..."
                                required>
                            <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                        </div><!-- End .header-search-wrapper -->
                    </form>
                </div><!-- End .header-search -->
            </div>

            <div class="header-right">
                <div class="header-dropdown-link">


                    <a href="wishlist.php" class="wishlist-link">
                        <i class="icon-heart-o"></i>
                        <span class="wishlist-count">0</span>
                        <span class="wishlist-txt">Wishlist</span>
                    </a>

                    <div class="dropdown cart-dropdown">
                        <!--<a href="cart_.php" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">-->
                        <a href="cart_.php" class="dropdown-toggle" role="button" data-display="static">
                            <i class="icon-shopping-cart"></i>
                            <span class="cart-count">0</span>
                            <span class="cart-txt">Cart</span>
                        </a>




                    </div><!-- End .cart-dropdown -->
                </div>
            </div><!-- End .header-right -->
        </div><!-- End .container -->
    </div><!-- End .header-middle -->

    <div class="sticky-wrapper" style="">
        <div class="header-bottom sticky-header">
            <div class="container">
                <div class="header-left">
                    <div class="dropdown category-dropdown">
                        <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false" data-display="static" title="Browse Categories">
                            Browse Categories </i>
                        </a>

                        <div class="dropdown-menu">
                            <nav class="side-nav">
                                <ul class="menu-vertical sf-arrows sf-js-enabled" style="touch-action: pan-y;">
                                    <?php
                                    $object = new Category();
                                    $stmt = $object->get_parent_category();
                                    ?>


                                    <?php
                                    while ($row = $stmt->fetch()) {
                                        ?>
                                        <li class="megamenu-container">
                                            <a class="sf-with-ul"
                                                href="<?php echo 'category.php?catid=' . $row['cat'] ?>"><?= ucfirst(strtolower($row['cat'])) ?></a>
                                        </li>

                                        <?php

                                    }
                                    ?>



                                </ul><!-- End .menu-vertical -->
                            </nav><!-- End .side-nav -->
                        </div><!-- End .dropdown-menu -->
                    </div><!-- End .category-dropdown -->
                </div><!-- End .col-lg-3 -->

                <div class="header-right">
                    <i class="la la-lightbulb-o"></i>
                    <p><span>sale on selected items Up to 30% Off</span></p>
                </div>
            </div><!-- End .container -->
        </div><!-- End .header-bottom -->
    </div>
</header><!-- End .header -->