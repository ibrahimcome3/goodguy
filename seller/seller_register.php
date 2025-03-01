<!DOCTYPE html>
<html>

<head>
    <title>Seller Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php
    session_start();
    include "../conn.php";
    require_once '../class/Connn.php';
    require_once '../class/User.php';
    require_once '../class/Seller.php';

    // Check if a user is logged in
    if (!isset($_SESSION['uid'])) {
        // Redirect to login or display an error
        header("Location: ../login.php"); // Replace with your login page
        exit;
    }

    $u = new User();
    $userId = $_SESSION['uid'];
    $user = $u->getUserById($mysqli, $userId);

    // Check if the user already has a vendor account
    $s = new Seller();
    $existingSeller = $s->getSellerByUserId($mysqli, $userId);
    $userVendorStatus = $u->getVendorStatus($mysqli, $userId);

    if ($userVendorStatus == 'approved') {
        echo "<p class='alert alert-warning'>You already have a seller account.</p>";
        exit;
    } elseif ($userVendorStatus == 'pending') {
        echo "<p class='alert alert-warning'>You already requested to become a seller.</p>";
        exit;
    }

    // Get customer's full name
    $customerFullName = $user['customer_fname'] . " " . $user['customer_lname'];

    ?>

    <div class="container mt-5">
        <h2>Seller Registration</h2>
        <p>To become a seller, please provide the following details:</p>
        <form method="post" action="process_seller_registration.php">
            <input type="hidden" name="user_id" value="<?= $userId ?>">

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="use_customer_name" name="use_customer_name"
                        onchange="toggleSellerName()">
                    <label class="form-check-label" for="use_customer_name">
                        Use my name (<?= htmlspecialchars($customerFullName) ?>)
                    </label>
                </div>
                <label for="seller_name" class="form-label">Seller Name:</label>
                <input type="text" class="form-control" id="seller_name" name="seller_name" required
                    value="<?= htmlspecialchars($customerFullName) ?>">
            </div>
            <div class="mb-3">
                <label for="seller_email" class="form-label">Seller Email:</label>
                <input type="email" class="form-control" id="seller_email" name="seller_email"
                    value="<?= htmlspecialchars($user['customer_email']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="seller_phone" class="form-label">Seller Phone:</label>
                <input type="text" class="form-control" id="seller_phone" name="seller_phone">
            </div>
            <div class="mb-3">
                <label for="seller_address" class="form-label">Seller Address:</label>
                <textarea class="form-control" id="seller_address" name="seller_address"></textarea>
            </div>
            <div class="mb-3">
                <label for="seller_business_name" class="form-label">Business Name:</label>
                <textarea class="form-control" id="seller_business_name" name="seller_business_name"></textarea>
            </div>
            <div class="mb-3">
                <label for="seller_description" class="form-label">Seller Description:</label>
                <textarea class="form-control" id="seller_description" name="seller_description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Register as Seller</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSellerName() {
            const checkbox = document.getElementById('use_customer_name');
            const sellerNameInput = document.getElementById('seller_name');
            const customerFullName = "<?= htmlspecialchars($customerFullName) ?>"; // Get PHP variable

            if (checkbox.checked) {
                sellerNameInput.value = customerFullName;
                sellerNameInput.readOnly = true;
            } else {
                sellerNameInput.value = "";
                sellerNameInput.readOnly = false;
            }
        }
        window.onload = toggleSellerName;
    </script>
</body>

</html>