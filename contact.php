<?php
// File: contact.php

// Start session if not already started (best practice: do this in includes.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes.php"; // Should provide $pdo and other necessary setups
// require_once "conn.php"; // Already included via includes.php if $pdo is defined there

$errors = [];
$smsg = '';
$form_data = [
    'firstname' => '',
    'lastname' => '',
    'contactemail' => '',
    'subject' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_contact_form"])) {
    $form_data['firstname'] = trim($_POST['firstname'] ?? '');
    $form_data['lastname'] = trim($_POST['lastname'] ?? '');
    $form_data['contactemail'] = trim($_POST['contactemail'] ?? '');
    $form_data['subject'] = trim($_POST['subject'] ?? '');

    if (empty($form_data['firstname'])) {
        $errors['firstname'] = "Please fill in your first name.";
    }
    if (empty($form_data['lastname'])) {
        $errors['lastname'] = "Please fill in your last name.";
    }
    if (empty($form_data['subject'])) {
        $errors['subject'] = "Please fill in the subject of your message.";
    }
    if (!filter_var($form_data['contactemail'], FILTER_VALIDATE_EMAIL)) {
        $errors['contactemail'] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        try {
            // Ensure $pdo is available from includes.php
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new Exception("Database connection is not available.");
            }

            $sql = "INSERT INTO `contact_us` (fname, lname, email, subject) VALUES (:fname, :lname, :email, :subject)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':fname', $form_data['firstname']);
            $stmt->bindParam(':lname', $form_data['lastname']);
            $stmt->bindParam(':email', $form_data['contactemail']);
            $stmt->bindParam(':subject', $form_data['subject']);

            if ($stmt->execute()) {
                $smsg = "<div class='alert alert-success' role='alert'><b>Your message was successfully sent.</b></div>";
                // Clear form data after successful submission
                $form_data = array_fill_keys(array_keys($form_data), '');
            } else {
                $smsg = "<div class='alert alert-danger' role='alert'><b>Could not send your message. Please try again.</b></div>";
                error_log("Failed to insert contact message: " . implode(", ", $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            $smsg = "<div class='alert alert-danger' role='alert'><b>Database error. Please try again later.</b></div>";
            error_log("PDOException in contact.php: " . $e->getMessage());
        } catch (Exception $e) {
            $smsg = "<div class='alert alert-danger' role='alert'><b>An unexpected error occurred. " . htmlspecialchars($e->getMessage()) . "</b></div>";
            error_log("Exception in contact.php: " . $e->getMessage());
        }
    } else {
        $smsg = "<div class='alert alert-danger' role='alert'><b>Please correct the errors in the form.</b></div>";
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contact Us - GoodGuyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
    <style>
        .form-group small.text-danger {
            display: block;
            margin-top: .25rem;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">
                <div class="container">
                    <h1 class="page-title">Contact us at GoodGuyng.com</h1>
                </div><!-- End .container -->
            </div><!-- End .page-header -->



            <div class="page-content pb-3">
                <div class="container">
                    <!-- Image Section -->
                    <div class="row">
                        <div class="col offset-lg-1">
                            <div class="about-text text-center mt-3 mb-5">
                                <img src="assets/images/contact.png" alt="Contact GoodGuyng.com" class="img-fluid"
                                    style="max-height: 100px; border-radius: 8px;">
                            </div>
                        </div>


                        <div class="col offset-lg-1">
                            <div class="about-text  mt-3 mb-5">
                                <p><b>Email:</b> <a href="mailto:care@GoodGuyng.com">care@GoodGuyng.com</a></p>
                                <p><b>Phone Number:</b> <a href="tel:+2348051067944">+2348051067944</a></p>
                                <p><b>Address:</b> No 31 Saint Finbarr's Road, Akoka, Yaba, Lagos, Nigeria.
                                    <br>Landmark: Before Zenith Bank if coming from University of Lagos.
                                </p>
                            </div><!-- End .about-text -->
                        </div><!-- End .col-lg-10 offset-1 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->

                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="text-center mx-auto mb-5">
                                <h2 class="title">Get in Touch</h2>
                                <p>We'd love to hear from you! Please fill out the form below and we'll get back to you
                                    as soon as possible.</p>
                            </div>

                            <?php if (!empty($smsg))
                                echo $smsg; ?>

                            <form action="contact.php" method="post" novalidate>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="firstname">First Name*</label>
                                            <input type="text"
                                                class="form-control <?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>"
                                                id="firstname" name="firstname" placeholder="Your first name"
                                                value="<?php echo htmlspecialchars($form_data['firstname']); ?>"
                                                required>
                                            <?php if (isset($errors['firstname'])): ?><small
                                                    class="text-danger"><?php echo $errors['firstname']; ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="lastname">Last Name*</label>
                                            <input type="text"
                                                class="form-control <?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>"
                                                id="lastname" name="lastname" placeholder="Your last name"
                                                value="<?php echo htmlspecialchars($form_data['lastname']); ?>"
                                                required>
                                            <?php if (isset($errors['lastname'])): ?><small
                                                    class="text-danger"><?php echo $errors['lastname']; ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="contactemail">Email Address*</label>
                                    <input type="email"
                                        class="form-control <?php echo isset($errors['contactemail']) ? 'is-invalid' : ''; ?>"
                                        id="contactemail" name="contactemail" placeholder="your.email@example.com"
                                        value="<?php echo htmlspecialchars($form_data['contactemail']); ?>" required>
                                    <?php if (isset($errors['contactemail'])): ?><small
                                            class="text-danger"><?php echo $errors['contactemail']; ?></small><?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="subject">Subject*</label>
                                    <textarea id="subject" name="subject"
                                        class="form-control <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?>"
                                        placeholder="Write your subject here..." rows="5"
                                        required><?php echo htmlspecialchars($form_data['subject']); ?></textarea>
                                    <?php if (isset($errors['subject'])): ?><small
                                            class="text-danger"><?php echo $errors['subject']; ?></small><?php endif; ?>
                                </div>

                                <div class="text-center">
                                    <input type="submit" name="submit_contact_form"
                                        class="btn btn-outline-primary-2 btn-minwidth-sm" value="Submit Message">
                                </div>
                            </form>
                        </div><!-- End .col-lg-10 offset-lg-1 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->

                <div class="mb-5"></div><!-- Spacer -->

                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="brands-text text-center mx-auto mb-6">
                                <h2 class="title">We will always deliver</h2><!-- End .title -->
                            </div><!-- End .brands-text -->
                        </div><!-- End .col-lg-10 offset-lg-1 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->

            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; // Corrected filename if it was a typo ?>

    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>

    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>
</body>

</html>