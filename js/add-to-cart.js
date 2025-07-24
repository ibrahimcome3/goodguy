            $(document).ready(function () {

            // Add to Cart Button Handler
            $('.page-wrapper').on('click', '.submit-cart', function (e) {

                e.preventDefault();
                var $button = $(this);
                var inventoryItemId = $button.attr('product-info');
                if (!inventoryItemId || $button.prop('disabled')) return;

                $button.prop('disabled', true).find('span').text('Adding...');
                $.ajax({
                    type: "POST", url: 'cart_ajax.php',
                    data: { action: 'add_item', inventory_product_id: inventoryItemId, qty: 1 },
                    dataType: 'json',
                    success: function (response) {
                        console.log(response);
                        if (response && response.success) {
                            // Update cart count icon
                            if (typeof response.cartCount !== 'undefined') {
                                $('.cart-count').text(response.cartCount);
                                $('.cart-dropdown > a').addClass('cart-updated-animation');
                                setTimeout(function () { $('.cart-dropdown > a').removeClass('cart-updated-animation'); }, 1000);
                            }

                            // Update cart dropdown content
                            var $dropdownMenu = $('.cart-dropdown .dropdown-menu');
                            if (response.cartCount > 0) {
                                // Ensure the full dropdown structure for a non-empty cart is present
                                // This structure is based on your header_main.php
                                if ($dropdownMenu.find('.dropdown-cart-products').length === 0) {
                                    $dropdownMenu.html(
                                        '<div class="dropdown-cart-products"></div>' +
                                        '<div class="dropdown-cart-total">' +
                                        '    <span>Total</span>' +
                                        '    <span class="cart-total-price"></span>' +
                                        '</div>' +
                                        '<div class="dropdown-cart-action">' +
                                        '    <a href="cart.php" class="btn btn-primary">View Cart</a>' +
                                        '    <a href="checkout-process-validation.php" class="btn btn-outline-primary-2"><span>Checkout</span><i class="icon-long-arrow-right"></i></a>' +
                                        '</div>'
                                    );
                                }
                                // Populate items and total
                                if (typeof response.cartItemsHtml !== 'undefined') {
                                    $dropdownMenu.find('.dropdown-cart-products').html(response.cartItemsHtml);
                                }
                                if (typeof response.cartTotalFormatted !== 'undefined') {
                                    $dropdownMenu.find('.cart-total-price').html(response.cartTotalFormatted);
                                }
                            } else {
                                // Cart is empty, show the empty message
                                $dropdownMenu.html('<p class="text-center p-3">Your cart is empty.</p>');
                            }

                            $button.find('span').text('Added!');
                            setTimeout(function () { $button.prop('disabled', false).find('span').text('add to cart'); }, 1500);
                        } else {
                            alert(response.message || "Could not add item.");
                            $button.prop('disabled', false).find('span').text('add to cart');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Add to Cart AJAX Error:", status, error, xhr.responseText);
                        alert("Error adding item. Please try again.");
                        $button.prop('disabled', false).find('span').text('add to cart');
                    }
                });
            });

            // Wishlist Button Handler
            $('.page-wrapper').on('click', '.btn-wishlist', function (e) {
                e.preventDefault();
                var $button = $(this);
                var productId = $button.data('product-id');
                if (!productId || $button.prop('disabled') || $button.hasClass('added-to-wishlist')) return;

                $button.prop('disabled', true).addClass('load-more-loading').find('span').text('Adding...');
                $.ajax({
                    type: 'POST', url: 'add_to_wishlist.php',
                    data: { product_id: productId }, // Ensure backend expects 'product_id'
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            if (typeof response.wishlistCount !== 'undefined') {
                                $('.wishlist-count').text(response.wishlistCount);
                            }
                            $button.removeClass('load-more-loading').addClass('added-to-wishlist')
                                .prop('disabled', false).attr('title', 'In Wishlist').find('span').text('In Wishlist');
                        } else {
                            alert(response.message || 'Could not add item.');
                            $button.removeClass('load-more-loading').prop('disabled', false).find('span').text('add to wishlist');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Wishlist AJAX Error:", status, error);
                        alert('Error adding to wishlist.');
                        $button.removeClass('load-more-loading').prop('disabled', false).find('span').text('add to wishlist');
                    }
                });
            });
      

            // Handler for removing items from the cart dropdown
            $('.cart-dropdown .dropdown-menu').on('click', '.btn-remove-dropdown-item', function(e) {
                e.preventDefault();
                var $button = $(this);
                var itemIdToRemove = $button.data('item-id'); // Get InventoryItemID
                if (!itemIdToRemove || $button.prop('disabled')) {
                    return;
                }

                // Optional: Add a visual cue that it's working
                $button.find('i').removeClass('icon-close').addClass('icon-spinner icon-spin');
                $button.prop('disabled', true);

                $.ajax({
                    type: "POST",
                    url: "cart_ajax.php",
                    data: {
                        action: 'remove_item',
                        item_id: itemIdToRemove
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response && response.success) {
                            // Update cart count icon
                            if (typeof response.cartCount !== 'undefined') {
                                $('.cart-count').text(response.cartCount);
                            }

                            // Update cart dropdown content
                            var $dropdownMenu = $('.cart-dropdown .dropdown-menu');
                            if (response.cartCount > 0) {
                                if (typeof response.cartItemsHtml !== 'undefined') {
                                    $dropdownMenu.find('.dropdown-cart-products').html(response.cartItemsHtml);
                                }
                                if (typeof response.cartTotalFormatted !== 'undefined') {
                                    $dropdownMenu.find('.cart-total-price').html(response.cartTotalFormatted);
                                }
                            } else {
                                // Cart is empty, show the empty message and remove total/actions
                                $dropdownMenu.html('<p class="text-center p-3">Your cart is empty.</p>');
                            }
                        } else {
                            alert(response.message || "Could not remove item.");
                            $button.find('i').removeClass('icon-spinner icon-spin').addClass('icon-close');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#response').text("Error: " + status + " - " + error + "\n" + xhr.responseText);
                        $button.find('i').removeClass('icon-spinner icon-spin').addClass('icon-close');
                        $button.prop('disabled', false);
                    }
                });
            });
    

            // Handler for removing items from the cart dropdown
            $('.cart-dropdown .dropdown-menu').on('click', '.btn-remove-dropdown-item', function(e) {
                e.preventDefault();
                var $button = $(this);
                var itemIdToRemove = $button.data('item-id'); // Get InventoryItemID
                // alert(1); // This was your test alert, can be removed
                if (!itemIdToRemove || $button.prop('disabled')) {
                    return;
                }

                // Optional: Add a visual cue that it's working
                $button.find('i').removeClass('icon-close').addClass('icon-spinner icon-spin');
                $button.prop('disabled', true);

                $.ajax({
                    type: "POST",
                    url: "cart_ajax.php", // Path to your PHP script
                    data: {
                        action: 'remove_item',
                        item_id: itemIdToRemove // Ensure cart_ajax.php expects 'item_id'
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response && response.success) {
                            // Update cart count icon
                            if (typeof response.cartCount !== 'undefined') {
                                $('.cart-count').text(response.cartCount);
                            }

                            // Update cart dropdown content
                            var $dropdownMenu = $('.cart-dropdown .dropdown-menu');
                            if (response.cartCount > 0) {
                                if (typeof response.cartItemsHtml !== 'undefined') {
                                    $dropdownMenu.find('.dropdown-cart-products').html(response.cartItemsHtml);
                                }
                                if (typeof response.cartTotalFormatted !== 'undefined') {
                                    $dropdownMenu.find('.cart-total-price').html(response.cartTotalFormatted);
                                }
                            } else {
                                // Cart is empty, show the empty message and remove total/actions
                                $dropdownMenu.html('<p class="text-center p-3">Your cart is empty.</p>');
                            }
                        } else {
                            alert(response.message || "Could not remove item.");
                            $button.find('i').removeClass('icon-spinner icon-spin').addClass('icon-close');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        // $('#response').text("Error: " + status + " - " + error + "\n" + xhr.responseText); // If you have an element with id="response"
                        console.error("Remove from Cart AJAX Error:", status, error, xhr.responseText);
                        alert("Error removing item. Please try again.");
                        $button.find('i').removeClass('icon-spinner icon-spin').addClass('icon-close');
                        $button.prop('disabled', false);
                    }
                });
            });
        

    });


    