    <div class="mobile-menu-container mobile-menu-light">
        <div class="mobile-menu-wrapper">
            <span class="mobile-menu-close"><i class="icon-close"></i></span>

            <form action="product-search.php" method="get" class="mobile-search">
                <label for="mobile-search" class="sr-only">Search</label>
                <input type="search" class="form-control" name="q" id="mobile-search" placeholder="Search in..." required>
                <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
            </form>

            <ul class="nav nav-pills-mobile nav-border-anim" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" id="mobile-cats-link" data-toggle="tab" href="#mobile-cats-tab" role="tab" aria-controls="mobile-cats-tab" aria-selected="false">Categories</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="mobile-menu-tab" role="tabpanel" aria-labelledby="mobile-menu-link">
                    <nav class="mobile-nav">
                        <ul class="mobile-menu">
                            <li class="active">
                                <a href="index.php">Home</a>
                            </li>
                            <li>
                                <a href="index.php">Shop</a>
                                <ul>

                                 <!--   <li><a href="product-category-fullwidth.html"><span>Product Category Fullwidth<span class="tip tip-new">New</span></span></a></li>  -->
                                    <li><a href="cart_.php">Cart</a></li>
                                    <li><a href="checkout.php">Checkout</a></li>
                                    <li><a href="wishlist.php">Wishlist</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#" class="sf-with-ul"><span>Product Category<span class="tip tip-new">New</span></span></a>
                                <ul>
                                     <?php
                                      $object = new Category();
                                      $stmt = $object->get_parent_category();
                                       while ($row = $stmt->fetch()) {

                                     ?>
                                    <li><a href="<?php echo 'category.php?catid='.$row['cat']  ?>"><?=ucwords(strtolower ($row['cat']))?></a></li>

                                    <?php
                                    }
                                    ?>
                                </ul>

                            </li>
                            <li>
                                <a href="#">Other pages</a>
                                <ul>
                                    <li>
                                        <a href="about.php">About</a>
                                    </li>
                                    <li>
                                        <a href="contact.php">Contact</a>

                                    </li>
                                    <li><a href="login.php">Login</a></li>



                                </ul>
                            </li>


                        </ul>
                    </nav><!-- End .mobile-nav -->
                </div><!-- .End .tab-pane -->

            </div><!-- End .tab-content -->

            <div class="social-icons">
                <a href="https://web.facebook.com/profile.php?id=61560944252129"  class="social-icon" target="_blank" title="Facebook"><i class="icon-facebook-f"></i></a>
                <a href="https://x.com/goodguyng_store" class="social-icon" target="_blank" title="Twitter"><i class="icon-twitter"></i></a>
                <a href="https://www.instagram.com/goodguynigeriastore?igsh=MTE2YXl4cWVnNW02bQ==" class="social-icon" target="_blank" title="Instagram"><i class="icon-instagram"></i></a>
                <a href="https://www.youtube.com/channel/UCFtSGmwHVjQbxj-FDqKzjRA" class="social-icon" target="_blank" title="Youtube"><i class="icon-youtube"></i></a>
            </div><!-- End .social-icons -->
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->