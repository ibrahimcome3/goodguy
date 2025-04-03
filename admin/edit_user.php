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
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container">
        <h1>Edit User</h1>
        <form method="post" action="process_edit_user.php">
            <input type="hidden" name="user_id" value="<?= $userDetails['customer_id'] ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username"
                    value="<?= htmlspecialchars($userDetails['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($userDetails['customer_email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone:</label>
                <input type="tel" class="form-control" id="phone" name="phone"
                    value="<?= htmlspecialchars($userDetails['customer_phone']) ?>">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address:</label>
                <textarea class="form-control" id="address" name="address1"
                    required><?= trim(htmlspecialchars($userDetails['customer_address1'])) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="address2" class="form-label">Address 2:</label>
                <textarea class="form-control" id="address2" name="address2">
                    <?= trim(htmlspecialchars($userDetails['customer_address2'])) ?></textarea>
            </div>
            <?php if ($sellerDetails): ?>
                <div class="mb-3">
                    <label for="businessName" class="form-label">Business Name:</label>
                    <input type="text" class="form-control" id="businessName" name="businessName"
                        value="<?= htmlspecialchars($sellerDetails['seller_business_name']) ?>">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea class="form-control" id="description"
                        name="description"><?= htmlspecialchars($sellerDetails['seller_description']) ?></textarea>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>