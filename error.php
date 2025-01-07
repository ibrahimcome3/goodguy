<!DOCTYPE html>
<html>

<head>
    <title>Password Reset Email Sent</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <br />
        <div class="alert alert-danger mt-5" role="alert">
            <?php
            if (isset($_GET['message'])) {
                echo $_GET['message'];
            }

            ?>
        </div>
    </div>
</body>

</html>