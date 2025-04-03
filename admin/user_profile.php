<?php
session_start();
include "../conn.php";
require_once '../class/User.php';
require_once '../class/Seller.php';

$u = new User();
$s = new Seller();
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;


// Check if user_id is provided
if ($userId === null) {
    echo "<p>No user ID provided.</p>";
    exit;
}

// Fetch user and seller details
$userDetails = $u->getUserDetailsById($mysqli, $userId);
$sellerDetails = $s->getSellerByUserId($mysqli, $userId);

// Check if user exists
if (!$userDetails) {
    echo "<p>User not found.</p>";
    exit;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: sans-serif;
            /* Use a sans-serif font like GitHub/Stack Overflow */
        }

        .container {
            margin-top: 20px;
        }

        .profile-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            /* Add a subtle shadow */
        }

        .profile-card h2 {
            margin-top: 0;
        }

        .profile-card strong {
            display: block;
            margin-bottom: 5px;
        }

        .profile-card p {
            margin-bottom: 10px;
        }

        .profile-card a.btn {
            margin-top: 15px;
        }

        .manage-phone-button {
            background-color: #f8f9fa;
            /* Light gray background */
            color: #333;
            /* Dark gray text */
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.875rem;
            /* Adjust font size as needed */
            cursor: pointer;
            /* Make it clear it's clickable */
        }

        .manage-phone-button:hover {
            background-color: #e9ecef;
            /* Slightly lighter gray on hover */
        }
    </style>
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container">
        <div class="profile-card">
            <h2>User Profile</h2>
            <div class="mb-3">
                <strong>Name:</strong> <?= htmlspecialchars($userDetails['username']) ?>
            </div>
            <div class="mb-3">
                <strong>Email:</strong> <?= htmlspecialchars($userDetails['customer_email']) ?>
            </div>

            <?php
            $phoneNumbers = $u->get_phone_number();

            if ($phoneNumbers) {
                foreach ($phoneNumbers as $phoneNumber) {
                    echo "<div class='mb-3'><strong>Phone Number:</strong> " . htmlspecialchars($phoneNumber['PhoneNumber']) . "</div>";
                }
            } else {
                echo "<div class='mb-3'><strong>Phone Numbers:</strong> No phone numbers found.</div>";
            }
            ?>
            <button class="manage-phone-button mb-3"
                onclick="window.location.href='../user/manage_phone_numbers.php?user_id=<?= $userDetails['customer_id'] ?>'">Manage
                Phone Numbers</button>



            <div class="mb-3">
                <strong>Address1:</strong> <?= htmlspecialchars($userDetails['customer_address1']) ?>
            </div>
            <div class="mb-3">
                <strong>Address2:</strong> <?= htmlspecialchars($userDetails['customer_address2']) ?>
            </div>
            <?php if ($sellerDetails): ?>
                <div class="mb-3">
                    <strong>Business Name:</strong> <?= htmlspecialchars($sellerDetails['seller_business_name']) ?>
                </div>
                <div class="mb-3">
                    <strong>Description:</strong> <?= htmlspecialchars($sellerDetails['seller_description']) ?>
                </div>
            <?php endif; ?>
            <a href="edit_user.php?user_id=<?= $userDetails['customer_id'] ?>" class="btn btn-primary">Edit</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>