<!DOCTYPE HTML>
<?php require_once "includes.php";
require_once "conn.php";
?>
<html lang="en">


<!-- molla/about-2.html  22 Nov 2019 10:03:54 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <title>Contact</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
    </body>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header_main.php";
        ?>

        <main class="main">
            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">
                <div class="container">
                    <h1 class="page-title">Contact us at GoodGuyng.com</h1>
                </div><!-- End .container -->
            </div><!-- End .page-header -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content pb-3">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="about-text text-center mt-4">
                                <p><b>Email: </b> care@GoodGuyng.com</p>
                                <p><b>Phone Number: </b> +2348051067944</p>
                                <p><b>Address: </b> No 31 saint finberss road. Akoka lagos Yaba, Nigeria. Land Mark:
                                    before Zenith bank if you are coming from University of Lagos </p>
                            </div><!-- End .about-text -->
                        </div><!-- End .col-lg-10 offset-1 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->

                <div class="mb-2"></div><!-- End .mb-2 -->

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

            <section>
                <?php
                if (!empty($_POST["submit_contact_form"])) {

                    $fn = $_POST['firstname'];
                    $ln = $_POST['lastname'];
                    $subj = $_POST['subject'];
                    $email = $_POST['contactemail'];


                    $lnerror = empty($ln) ? "Please fill your last name" : null;
                    $subjecterror = empty($subj) ? "Please fill your subject of your message" : null;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emailerror = null;
                    } else {
                        $emailerror = "Incorrect email address";
                    }

                    $fnerror = empty($fn) ? "Please fill your firstname" : null;

                    if (is_null($lnerror) && is_null($subjecterror) && is_null($emailerror) && is_null($fnerror)) {
                        $sql = "INSERT INTO `contact_us`(`id`, `fname`, `lname`, `email`, `subject`) VALUES (null,'$fn','$ln','$email','$subj')";
                        $result = $mysqli->query($sql);
                        if ($result) {
                            $smsg = "<div class='alert alert-secondary' role='alert'><b>Your message was suscessfully sent</b></div> <br/>";
                            unset($_POST);
                        }
                    } else {
                        $smsg = "<div class='alert alert-danger' role='alert'><b>Your have an error</b></div> <br/>";
                    }
                }
                ?>

                <script type="text/babel">
                    import React, { useState } from "https://unpkg.com/react@18/umd/react.development.js";
                    //import ReactDOM from "https://cdn.skypack.dev/react-dom@17.0.1";


                    ReactDOM.render(<ContactForm />, document.getElementById('root'));

                    function ContactForm() {
                        return (
                            <form action="" method="post">
                                <div className="form-group">
                                    <label htmlFor="fname">First Name</label>
                                    <input type="text" className="form-control" id="fname" name="firstname" placeholder="Your name.." />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="lname">Last Name</label>
                                    <input type="text" className="form-control" id="lname" name="lastname" placeholder="Your last name.." />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="email">Email Address</label>
                                    <input type="text" className="form-control" id="email" name="contactemail" placeholder="myemail@anyemail.com" />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="subject">Subject</label>
                                    <textarea name="subject" className="form-control" placeholder="Write something.." rows={4} cols={40} />
                                </div>
                                <div className="form-group">
                                    <input type="submit" name="submit_contact_form" className="for-logging-in btn btn-outline-primary-2" value="Submit" />
                                </div>

                            </form>
                        )

                    }


                </script>

                <div class="container">

                    <div id="root"></div>
                    <?php echo isset($smsg) ? $smsg : "" ?>
                    <form action="" method="post">

                        <div class="form-group">
                            <label for="fname">First Name*</label>
                            <input type="text" class="form-control" id="fname" name="firstname"
                                placeholder="Your name.."
                                value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" />
                            <small style="color: red"><?php if (isset($fnerror)) if (!is_null($fnerror))
                                echo $fnerror; ?></small>
                        </div>
                        <div class="form-group">
                            <label for="lname">Last Name*</label>
                            <input type="text" class="form-control" id="lname" name="lastname"
                                placeholder="Your last name.."
                                value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" />
                            <small style="color: red"><?php if (isset($lnerror)) if (!is_null($lnerror))
                                echo $lnerror; ?></small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="text" class="form-control" id="email" name="contactemail"
                                placeholder="myemail@anyemail.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                            <small style="color: red"><?php if (isset($emailerror)) if (!is_null($emailerror))
                                echo $emailerror; ?></small>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject*</label>
                            <textarea id="subject" class="form-control" name="subject" placeholder="Write something.."
                                style="height:200px"><?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?></textarea>
                            <small style="color: red"><?php if (isset($subjecterror)) if (!is_null($subjecterror))
                                echo $subjecterror; ?></small>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="submit_contact_form"
                                class="for-logging-in btn btn-outline-primary-2" value="Submit">
                        </div>

                    </form>
                </div>
            </section>
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; ?>

    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>

    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>
</body>


<!-- molla/about-2.html  22 Nov 2019 10:04:01 GMT -->

</html>