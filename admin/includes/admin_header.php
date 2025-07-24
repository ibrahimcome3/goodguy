<?php
// Ensure a session is started, but only once.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use __DIR__ to create a more reliable path to the connection file.
require_once __DIR__ . '/../../conn.php';

// Check if an admin is logged in. If not, redirect to the login page.
if (!isset($_SESSION['admin_id'])) {
    // Create an absolute URL for redirection to avoid relative path issues.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Correctly determine the base path of the admin directory.
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: {$protocol}{$host}{$uri}/admin_login.php");
    exit;
}

// Set admin-specific variables for use in templates.
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$is_super_admin = $_SESSION['is_super_admin'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoodGuy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Common Admin Styles -->
    <style>
        body {
            font-size: .875rem;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            /* Behind the navbar */
            padding: 70px 0 0;
            /* Height of navbar */
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 70px);
            /* Adjusted for navbar height */
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .navbar {
            min-height: 70px;
        }

        .main-content {
            padding-top: 80px;
            /* Increased padding to ensure content is below the fixed navbar */
            margin-left: 220px;
            /* Adjust this based on your sidebar width */
        }
    </style>
</head>

<body>
    <?php include 'admin_navbar.php'; ?>