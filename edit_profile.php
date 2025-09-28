<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes.php"; // Main include file

// Ensure user is logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['error_message'] = "You must be logged in to edit your profile.";
    header("Location: login.php"); // Redirect to login page
    exit();
}

$user_id = $_SESSION['uid'];
$user_data = null;
$error_messages = [];
$success_message = '';

// Ensure User class is available
if (!isset($user) || !($user instanceof User)) {
    if (class_exists('User') && isset($pdo)) {
        $user = new User($pdo);
    } else {
        // This is a critical failure; User class or PDO is not available.
        error_log("User class or PDO not available in edit_profile.php");
        die("A critical error occurred. Please contact support.");
    }
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle ALL POST requests (main profile update OR phone actions)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify CSRF token for ANY POST action first
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_messages[] = "Invalid CSRF token. Please try again.";
        // Regenerate token immediately if an invalid one was submitted to prevent reuse of the session's current token on a refresh.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // CSRF Token is VALID, now determine the action

        if (isset($_POST['phone_action'])) {
            // --- HANDLE PHONE ACTIONS ---
            $phone_action = $_POST['phone_action'];

            if ($phone_action === 'add_phone') {
                $new_phone_number_input = filter_input(INPUT_POST, 'new_phone_number', FILTER_SANITIZE_STRING);
                if (!empty($new_phone_number_input)) {
                    // The addPhoneNumber method now returns the new phone_id on success or an error string
                    $addResult = $user->addPhoneNumber($user_id, $new_phone_number_input);

                    if (is_numeric($addResult) && $addResult > 0) {
                        // On success, redirect to the verification page
                        header("Location: verify-phone.php?phone_id=" . $addResult);
                        exit();
                    } else {
                        // If it's a string, it's an error message
                        if (is_string($addResult)) {
                            $_SESSION['error_message'] = $addResult;
                        } else {
                            $_SESSION['error_message'] = "Failed to add phone number. It might already exist or an error occurred.";
                        }
                    }
                } else {
                    $_SESSION['error_message'] = "Phone number cannot be empty.";
                }
            } elseif ($phone_action === 'delete_phone' && isset($_POST['phone_id'])) {
                $phone_id_to_delete = filter_var($_POST['phone_id'], FILTER_VALIDATE_INT);
                if ($user->deleteUserPhoneNumber($phone_id_to_delete, $user_id)) {
                    $_SESSION['success_message'] = "Phone number deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete phone number.";
                }
            } elseif ($phone_action === 'set_default_phone' && isset($_POST['phone_id'])) {
                $phone_id_to_default = filter_var($_POST['phone_id'], FILTER_VALIDATE_INT);
                if ($user->setDefaultUserPhoneNumber($phone_id_to_default, $user_id)) {
                    $_SESSION['success_message'] = "Default phone number set successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to set default phone number.";
                }
            }
            // Regenerate CSRF token and redirect for ALL phone actions
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: edit_profile.php");
            exit();

        } else {
            // --- HANDLE MAIN PROFILE UPDATE ---
            $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
            $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            if (empty($firstname))
                $error_messages[] = "First name is required.";
            if (empty($lastname))
                $error_messages[] = "Last name is required.";
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                $error_messages[] = "A valid email is required.";

            if (empty($error_messages)) {
                try {
                    $update_data = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email];
                    if ($user->updateUserProfile($user_id, $update_data)) {
                        $success_message = "Profile updated successfully!"; // Display on current page
                        $_SESSION['user_firstname'] = $firstname;
                    } else {
                        $error_messages[] = "Failed to update profile. Please try again.";
                    }
                } catch (Exception $e) {
                    error_log("Error updating profile for user ID {$user_id}: " . $e->getMessage());
                    $error_messages[] = "An error occurred while updating your profile.";
                }
            }
            // Regenerate CSRF token after main profile update attempt (whether success or validation fail)
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

// Fetch current user data for display
try {
    $user_data = $user->getUserById($user_id); // Assuming this method exists in your User class
    if (!$user_data) {

        $_SESSION['error_message'] = "Could not retrieve your profile data.";
        header("Location: index.php"); // Or dashboard
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching profile data for user ID {$user_id}: " . $e->getMessage());
    die("A critical error occurred while fetching your profile. Please contact support.");
}

// Fetch phone numbers for display
$phone_numbers_list = [];
if (method_exists($user, 'getPhoneNumbersByUserId')) { // Check if method exists
    $phone_numbers_list = $user->getPhoneNumbersByUserId($user_id);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Profile - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <!-- You might want a specific CSS file for forms or profile pages -->
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">

                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-3">Edit Your Profile</h2>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success text-center"><?= htmlspecialchars($_SESSION['success_message']); ?>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success text-center"><?= htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error_messages)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($error_messages as $msg): ?>
                                    <li><?= htmlspecialchars($msg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($user_data): ?>
                        <form action="edit_profile.php" method="POST" class="form-card">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="firstname">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="firstname" name="firstname"
                                            value="<?= htmlspecialchars($user_data['firstname'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="lastname">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="lastname" name="lastname"
                                            value="<?= htmlspecialchars($user_data['lastname'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address:</label>
                                <div class="input-group">
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($user_data['customer_email'] ?? ''); ?>" required>
                                    <?php if ($user->isEmailVerified($user_id)): ?>
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-success text-white">
                                                <i class="icon-check"></i> Verified
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group-append">
                                            <a href="verify_email.php" class="btn btn-warning"
                                                title="Click to verify your email">
                                                <i class="icon-exclamation-circle"></i> Verify Email
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$user->isEmailVerified($user_id)): ?>
                                    <small class="form-text text-muted">Please verify your email address to unlock all account
                                        features.</small>
                                <?php endif; ?>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>

                        <hr class="my-5">

                        <h3 class="text-center mb-3">Manage Phone Numbers</h3>

                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger text-center"><?= htmlspecialchars($_SESSION['error_message']); ?>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <?php if (!empty($phone_numbers_list)): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Phone Number</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($phone_numbers_list as $phone): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($phone['PhoneNumber']); ?></td>
                                            <td>
                                                <?php if ($phone['default_']): ?>
                                                    <span class="badge badge-success">Default</span> &nbsp;
                                                <?php endif; ?>
                                                <?php if (!$phone['is_verified']): ?>
                                                    <span class="badge badge-warning">Unverified</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info">Verified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$phone['default_'] && $phone['is_verified']): ?>
                                                    <form action="edit_profile.php" method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="phone_id" value="<?= $phone['phone_id']; ?>">
                                                        <input type="hidden" name="phone_action" value="set_default_phone">
                                                        <button type="submit" class="btn btn-sm btn-info">Set as Default</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if (!$phone['is_verified']): ?>
                                                    <a href="verify-phone.php?phone_id=<?= $phone['phone_id']; ?>"
                                                        class="btn btn-sm btn-success">
                                                        Verify Now
                                                    </a>
                                                <?php endif; ?>
                                                <form action="edit_profile.php" method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('Are you sure you want to delete this phone number?');">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="phone_id" value="<?= $phone['phone_id']; ?>">
                                                    <input type="hidden" name="phone_action" value="delete_phone">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">You have no saved phone numbers.</p>
                        <?php endif; ?>

                        <h4 class="mt-4">Add New Phone Number</h4>
                        <form action="edit_profile.php" method="POST" class="form-card">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>" />
                            <input type="hidden" name="phone_action" value="add_phone">
                            <div class="form-group">
                                <label for="new_phone_number">New Phone Number</label>
                                <input type="tel" class="form-control" id="new_phone_number" name="new_phone_number"
                                    placeholder="Enter new phone number" required>
                            </div>
                            <?php if ($user->hasUnverifiedPhoneNumber($user_id)): ?>
                                <p class="text-warning">You must verify your pending phone number before adding a new one.</p>
                            <?php else: ?>
                                <button type="submit" class="btn btn-success">Add Phone</button>
                            <?php endif; ?>
                        </form>

                    <?php else: ?>
                        <p class="text-center">Could not load profile data. Please try again later.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include "footer.php"; ?>
    </div>

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>
    <?php include "jsfile.php"; ?>
</body>

</html>