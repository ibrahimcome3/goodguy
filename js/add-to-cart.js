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
                    data: { inventory_product_id: inventoryItemId, qty: 1 },
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.success) {
                            if (typeof response.cartCount !== 'undefined') {
                                $('.cart-count').text(response.cartCount);
                                $('.cart-dropdown > a').addClass('cart-updated-animation');
                                setTimeout(function () { $('.cart-dropdown > a').removeClass('cart-updated-animation'); }, 1000);
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
        });




    