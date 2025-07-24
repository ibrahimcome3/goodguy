<?php
session_start();
include "../conn.php"; // For $mysqli connection
require_once '../class/Admin.php'; // Now using Admin class

$errorMessage = '';

// If admin is already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php"); // Or your admin dashboard page
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $usernameOrEmail = trim($_POST['username']);
        $password = $_POST['password']; // Password as entered by user

        if (empty($usernameOrEmail) || empty($password)) {
            $errorMessage = "Username/Email and Password are required.";
        } else {
            $adminInstance = new Admin($pdo); // Instantiate Admin class

            // Call the loginAdmin method
            if ($adminInstance->loginAdmin($usernameOrEmail, $password)) {
                // Login was successful, session is set by the method
                header("Location: index.php"); // Redirect to admin dashboard
                exit;
            } else {
                // loginAdmin returned false, meaning login failed
                // Determine a more specific error message if possible, or use a generic one.
                // For now, a generic message is fine. The method itself logs DB errors.
                $errorMessage = "Invalid username/email or password, or you do not have administrator privileges.";
            }
        }
    } else {
        $errorMessage = "Please enter both username/email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - GoodGuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
        }

        .login-container h2 {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-floating label {
            padding-left: 0.5rem;
            /* Adjust if needed */
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="admin_login.php">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email"
                    required autofocus>
                <label for="username">Username or Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                    required>
                <label for="password">Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-muted text-center">&copy; GoodGuy <?= date("Y") ?></p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>