<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <form id="paymentForm" method="POST" action="process_payment.php">
        <label>Order ID:</label>
        <input type="test" name="orderID" value="82">

        <h2>Select Payment Method:</h2>
        <label>
            <input type="radio" name="paymentMethod" value="cod" checked> Cash on Delivery (COD)
        </label><br>
        <label>
            <input type="radio" name="paymentMethod" value="flutterwave"> Flutterwave
        </label><br>
        <label>
            <input type="radio" name="paymentMethod" value="paystack"> Paystack
        </label><br>

        <label>
            <input type="radio" name="paymentMethod" value="card"> Card
        </label><br>
        <label>
            <input type="radio" name="paymentMethod" value="paypal"> PayPal <button type="submit">Submit
                Payment</button>

        </label><br><br>

        <input type="submit" value="Submit Payment">


    </form>

</body>

</html>