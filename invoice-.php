<?php
include "includes.php";
require_once "class/invoice.php";
require_once "class/Order.php";
$invoice = new Invoice(82);
$order = new Order(82);
$p = new ProductItem();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <?php include "htlm-includes.php/metadata.php"; ?>

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


        .btn-github {
            background-color: #28a745;
            /* GitHub Green */
            color: white;
            border: none;
            padding: 10px 20px;
            /* Adjust padding as needed */
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: block;
            /* Makes the button a block element */
            margin: 20px auto;
            /* Centers the button horizontally */
            width: 100%;
            /* Stretches the button to full width */
            max-width: 300px;
            /* Optional: set a maximum width */
        }

        .btn-github:hover {
            background-color: #218838;
            /* Slightly darker green on hover */
        }

        @media print {

            /* Hide the button when printing */
            .btn-github {
                display: none !important;
            }
        }

        /* Basic styling for payment options */
        .payment-option {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            /* Optional: rounded corners */
            cursor: pointer;
            /* Makes the whole option clickable */
        }

        .payment-option input[type="radio"] {
            margin-right: 10px;
            /* Space between radio button and label */
            vertical-align: middle;
            /* Align radio button with label text */
        }

        .payment-icon {
            /* Placeholder for payment icons */
            display: inline-block;
            width: 20px;
            height: 20px;

            /* Replace with actual icon or image */
            margin-right: 5px;
            vertical-align: middle;
        }


        .payment-name {
            font-weight: 500;
            /* Slightly bolder font-weight */
        }

        .payment-description {
            font-size: 0.9em;
            color: #666;
            /* Slightly lighter text color for description */
            margin-top: 5px;
        }

        /* Styling for checked/selected option */
        .payment-option input[type="radio"]:checked+label {
            /* Targets the label next to the checked radio */
            font-weight: bold;
            /* Example: make text bold */
            /* Add other styles as needed */
        }

        .payment-option:hover {
            /* Hover effect */
            background-color: #f5f5f5;
            /* Light gray background on hover */
            /* Add other hover styles */
        }

        .payment-icon {
            display: inline-block;
            width: 24px;
            /* Adjust size as needed */
            height: 24px;
            /* Adjust size as needed */
            vertical-align: middle;
            /* Vertically align with text */
            margin-right: 2px;
            margin-top: -4px;
            /* Adjust spacing as needed */
        }

        /* Optional: If using inline SVG, you can control the fill color */
        .payment-icon svg {
            fill: #333;
            /* Or your desired color */
        }
    </style>
</head>

<body>
    <?php include "header-for-other-pages.php"; ?>


    <main>

        <div class="container">
            <input type="hidden" id="orderID" name="orderID" value="<?php echo $invoice->orderId; ?>">

            <a href="javascript:history.back()" class="btn btn-secondary mt-3">Back</a>
            <div style="border: 1px dashed black; padding: 18px; margin: 30px;">

                <h4 class="invoice-title mt-3 text-center">Order Summery</h4>

                <hr />
                <div class="row invoice-header">
                    <div class="col">
                        <img src="assets/images/goodguy.svg" alt="goodguyng.com logo" class="logo text-center">
                    </div>
                    <div class="col">
                        <button style="background-color: #28a745 !important;" class="btn btn-github d-print-none"
                            onclick="window.print()">Print Invoice</button>
                    </div>
                    <div class="col text-right">
                        <h2>INVOICE</h2>
                        <p>Invoice Date: <?php echo date("Y-m-d"); ?></p>
                    </div>
                </div>

                <hr />
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
                            <td colspan="9" class="text-right"><strong>Total Discount:</strong></td>
                            <td><strong><?= number_format($totalDiscount, 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="9" class="text-right"><strong>Grand Total:</strong></td>
                            <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="9" class="text-right"><strong>Total Items:</strong></td>
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
                            <div class="payment-option">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="cod" value="cod"
                                    checked>
                                <label class="form-check-label" for="cod">
                                    <span class="payment-icon"> <img src="assets/images/icons/money2.svg" alt="Card"
                                            class="payment-icon"> </span> <span class="payment-name">Cash on
                                        Delivery</span>
                                </label>
                                <p class="payment-description">Pay with cash when you receive your order.</p>
                            </div>

                            <div class="payment-option">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="card"
                                    value="card">
                                <label class="form-check-label" for="card">
                                    <span class="payment-icon"> <img src="assets/images/icons/mastercard.svg" alt="Card"
                                            class="payment-icon" /> </span> <span class="payment-name">Card
                                        Payment</span>
                                </label>
                                <p class="payment-description">Pay securely with your credit or debit card.</p>
                            </div>

                            <!-- Add more payment options as needed -->

                        </form>

                    </div>
                </div>
                <div class="row">
                    <div class="col"><button type="submit" class="btn btn-primary mt-3 btn-github"
                            id="submit-payment">Place order</button>
                    </div>
                </div>
            </div>

        </div>

    </main>


    <script>
        // Assuming jQuery is included
        $(document).ready(function () {
            $("#submit-payment").click(function (event) {
                event.preventDefault();


                var selectedPaymentMethod = $("input[name='paymentMethod']:checked").val();
                var orderID = $("#orderID").val();

                alert(selectedPaymentMethod);

                $.ajax({
                    url: "process_payment.php",
                    type: "POST",
                    data: { paymentMethod: selectedPaymentMethod, orderID: orderID },
                    success: function (response) {

                        if (response.success) {
                            if (selectedPaymentMethod === 'cod') {
                                alert("Invoice sent to customer email and order concluded successfully.");
                            } else {
                                alert("Payment method updated successfully.");
                            }
                            //Optionally redirect to a thank you page or other location.
                            window.location.href = "thankyou.php";
                        } else {
                            alert("Error: " + response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Payment processing failed:", error);
                        alert("An error occurred. Please try again later.");
                    }
                });
            });
        });



    </script>
</body>

</html>