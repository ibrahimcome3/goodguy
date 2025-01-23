<?php
include "includes.php";
include "class/invoice.php";
$invoice = new Invoice(82);
$order = new Order(82);
$p = new ProductItem();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoodGuy Template</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .header {
            background-color: aliceblue;
            /* Or your preferred color */
            padding: 10px 0;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 37px;
            /* Adjust as needed */
            height: auto;
            margin-right: 10px;
        }

        .brand-name {
            font-size: 26px;
            /* Adjust size as needed */
            font-weight: 600;
            /* Slightly bolder */
            color: #333;
            /* Dark gray or your brand color */
            letter-spacing: 1px;
            /* Adds a bit of spacing between letters */
            /* Optional: Add a subtle text shadow for depth */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            margin-top: -8px;
            /* Adjust this value as needed */
        }


        @media (max-width: 768px) {

            /* Adjust breakpoint as needed */
            .brand-name {
                font-size: 20px;
                /* Smaller font size on smaller screens */
            }
        }

        .logo path {
            /* Or other SVG element selector if needed */
            fill: blue;
            /* Sets the fill color to blue */
            stroke: blue;
            /* If your SVG uses strokes, set the stroke color too */
        }

        img.logo {
            filter: hue-rotate(100deg) saturate(50%);
            /* Adjust these values */
        }

        img.logo {
            filter: drop-shadow(-8px 8px -8px blue);
            /* Play with these values */
        }

        @media print {
            body {
                background-color: white;
                /* Force white background when printing */
                color: black;
                /* Force black text when printing */

            }

            .d-print-none {
                display: none;
            }

            /* Hide elements you don't want to print: */
            .no-print {
                display: none;
            }
        }

        .product-image {
            max-width: 50px;
            /* Adjust initial max-width as needed */
            height: auto;
            /* Maintain aspect ratio */
            display: block;
            /* Prevents a small space below the image in some cases */
        }

        @media (max-width: 576px) {

            /* Example breakpoint for smaller screens */
            .product-image {
                max-width: 60px;
                /* Smaller image on smaller screens */
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="container">
            <div class="logo-container">
                <img src="assets/images/goodguy.svg" alt="goodguyng.com logo" class="logo">
                <span class="brand-name">goodguyng.com</span>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div>

                <h3>Order Summery</h3>

                <div class="row">
                    <div class="col">
                        <?php echo $invoice->getOrderInfromation();
                        ?>
                    </div>




                    <div class="col">
                        <div><b>Shippig address</b></div>
                        <div>
                            <?php echo $invoice->getShippingAddress();
                            ?>
                        </div>
                    </div>
                </div>



                <br />

                <?php
                $products = $order->getOrderItems(82);
                //   var_dump($products);
                if ($products && count($products) > 0) {  // Check if $products is not null and has items
                    $grandTotal = 0;
                    $sn = 1; // Initialize serial number
                    $totalItems = 0;
                    $totalDiscount = 0; // Initialize total discount
                    ?>
                    <table class="table">

                        <tr>
                            <th>S/N</th>
                            <th>SKU</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Qty</th>
                            <th>Tax</th>

                            <th>Total</th>
                        </tr>


                        <?php


                        $taxAmount = 0;
                        foreach ($products as $product):
                            $totalItems += $product['quwantitiyofitem'];
                            $imageUrl = $p->get_image($product['InventoryItemID']); // Correct variable name
                            if (!$imageUrl)
                                $imageUrl = "e.jpeg";
                            $productID = $product_obj->get_product_id($product['InventoryItemID']);
                            $inventoryItemID = $product['InventoryItemID']; // Get the inventoryItemID
                    
                            if ($product_obj->check_dirtory_resized_600($productID, $inventoryItemID)) {
                                $imageUrl = "products/product-$productID/product-$productID-image/inventory-$productID-$inventoryItemID/resized_600/" . basename($imageUrl);
                            }
                            $itemTotal = $product['cost'] * $product['quwantitiyofitem'];

                            $discountAmount = 0;
                            if (isset($product['discount']) && $product['discount'] > 0) {  // Check if 'discount' key exists
                                $discountAmount = $itemTotal * ($product['discount'] / 100);
                                $itemTotal -= $discountAmount; // Reduce item total by discount amount
                                // echo $itemTotal;
                                $totalDiscount += $discountAmount; // Add to total discount
                            }


                            $taxAmount = ($product['tax'] > 0) ? ($itemTotal * ($product['tax'] / 100)) : 0;
                            $itemTotalWithTaxAndDiscount = $itemTotal + $taxAmount;

                            $grandTotal += $itemTotalWithTaxAndDiscount;


                            ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <th>SDFR564BGYRF</th>
                                <td><img src="<?= $imageUrl ?>" alt="Product Image" class="product-image"></td>
                                <td><?= $product['description'] ?></td>
                                <td><?= number_format($product['cost'], 2) ?></td>
                                <td><?= number_format($discountAmount, 2) ?></td>
                                <td><?= $product['quwantitiyofitem'] ?></td>
                                <td><?= $product['tax'] ?></td>

                                <!-- ... other table data ... -->

                                <td><?= number_format($itemTotalWithTaxAndDiscount, 2) ?></td>
                                <!-- Display the correct total -->



                            <?php endforeach; ?>


                            <!-- ... other footer rows ... -->
                        <tr>
                            <td colspan="8" class="text-right"><strong>Total Discount:</strong></td>
                            <td><strong><?= number_format($totalDiscount, 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-right"><strong>Grand Total:</strong></td>
                            <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-right"><strong>Total Items:</strong></td>
                            <td><strong><?= $totalItems ?></strong></td>
                        </tr>

                    </table>
                <?php } else { ?>
                    <p>No products found for this order.</p>
                <?php } ?>
                <div class="row">
                    <div class="col">
                        <h5>Payment Method</h5>
                        <form id="payment-form">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="cod" value="cod"
                                    checked>
                                <label class="form-check-label" for="cod">
                                    Cash on Delivery
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="card"
                                    value="card">
                                <label class="form-check-label" for="card">
                                    Card Payment
                                </label>
                            </div>
                            <br />
                            <button type="submit" class="btn btn-primary mt-3" id="submit-payment">Place order</button>

                        </form>
                    </div>
                </div>

            </div>

        </div>

    </main>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Assuming jQuery is included

        $(document).ready(function () {
            $("#payment-form").submit(function (event) {
                event.preventDefault(); // Prevent default form submission

                var selectedPaymentMethod = $("input[name='paymentMethod']:checked").val();

                // Send data to server using AJAX
                $.ajax({
                    url: "process_payment.php", // Create this server-side script
                    type: "POST",
                    data: { paymentMethod: selectedPaymentMethod, orderID: 82 }, // Include any other needed data
                    success: function (response) {
                        // Handle successful payment processing
                        alert("Payment method updated: " + selectedPaymentMethod);
                        // Update the invoice display, redirect, etc.
                    },
                    error: function (xhr, status, error) {
                        // Handle errors
                        console.error("Payment processing failed:", error);
                        alert("Error updating payment method.");
                    }
                });
            });
        });


    </script>
</body>

</html>