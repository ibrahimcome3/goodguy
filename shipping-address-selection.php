<?php
include "includes.php";
if (isset($_SESSION['cart']) and count($_SESSION['cart']) > 0) {
    $er = array();
    $sql = "SELECT * FROM `shipping_address` WHERE `customer_id` =  '" . $_SESSION['uid'] . "'";
    $result = $mysqli->query($sql);
    if ($result) {
        $check2 = mysqli_num_rows($result);
        if ($check2 > 0) {
        } elseif ($check2 == 0) {
            echo "you do not have a shipping address<br/>";
            echo "<a href='add-a-shipping-address.php'>Click here to add an address</a>";
            exit();
        }

    }
} else {
    echo "<a href='index.php'>Click here to shop. shopping cart empty</a>";
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shopping address selection page </title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <style>
        .product-label-123 {
            font-weight: 400;
            font-size: 1.3rem;
            line-height: 1.6rem;
            letter-spacing: -.01em;
            padding: .5rem .9rem;
            min-width: 45px;
            text-align: center;
            color: #fff;
            background-color: #66CC66;

        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <br />
        <?php include "header-for-other-pages.php"; ?>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="container">
                <div style="color: black">
                    <table>
                        <tr>
                            <td style='padding-left: 10px;'>Shipping Cost </td>
                            <td style='padding-left: 10px;'><b id="s1"></b></td>
                        </tr>
                        
                        <tr>
                            <td style='padding-left: 10px;'>Total Cost </td>
                            <td style='padding-left: 10px;'><b id="t1"></b></td>
                        </tr>
                        
                    </table>
                 
                </div>
                <form action="final-order-confirmation-page.php" method="post">
                    <a style=" float : right;" href="add-a-shipping-address.php"><span
                            class="product-label-123 label-new" style="position: relative;">Add a new address</span></a>
                    <table class="table table-hover table-sm">

                        <caption>Shipping Address</caption>
                        <tr>
                            <th scope="col"><b>Select</b></th>
                            <th scope="col"><b>Address</b></th>
                            <th scope="col"><b>Make Changes</b></th>
                        </tr>

                        <?php
                        $s = new Shipment();
                        if ($check2 > 0) {
                            while ($row = $result->fetch_array()) {
                                ?>
                                <tr>
                                    <td><input name="ship-address"
                                            shipping="<?= $s->get_shipping_cost($row['shipping_address_no']) ?>" type="radio"
                                            value="<?= $row['shipping_address_no'] ?>" checked="checked"> </td>
                                    <td>
                                        <?php echo ucwords($row['address1'] . " " . $row['address2'] . " <b>" . $row['state'] . "</b> (Zip:" . $row['zip'] . ")"); ?>
                                    </td>
                                    <td> <a href="edit-shipping_address.php?sno=<?= $row['shipping_address_no'] ?>">Edit</a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                    <input type="hidden" name="shipcost" id="shipcost" value="" />
                    <input type="hidden" name="subtotal" id="subtotal" value=<?= $_SESSION['totalcost'] ?> />
                    <button type="submit" class="btn btn-outline-dark btn-rounded">Next</button>
                </form>
                <br />
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue-index-page.php"; ?>
    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>


    <!-- Sign in / Register Modal -->
    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>
</body>
<script>

    $(document).ready(function () {
        
        function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
      console.log($('input[name="ship-address"]:checked').attr("shipping"));
        let naira = "N";
        $('input[name="ship-address"]').click(function (event) {
            $("#s1").text("N " + numberWithCommas($(this).attr("shipping")));
            $('#t1').text("N " + numberWithCommas((parseInt($('#subtotal').val()) + parseInt($(this).attr("shipping")))));
            $('#shipcost').attr("value", $('input[name="ship-address"]:checked').attr("shipping"));

        });

        //$('input[name="ship-address"]').on('change', function () {

        $('#t1').text("N " + numberWithCommas((parseInt($('#subtotal').val()) + parseInt($('input[name="ship-address"]:checked').attr("shipping")))));
        $("#s1").text("N " + numberWithCommas($('input[name="ship-address"]:checked').attr("shipping")));
        $('#shipcost').attr("value", parseInt($('input[name="ship-address"]:checked').attr("shipping")));
        //});
        /*

        $("button.for-logging-in").click(function(event){
        
         event.preventDefault();
              $("#err").empty();
              var email = $('#singinemail').val();
              var password  = $('#singinpassword').val();
              var err = "";
               
            $.ajax({
               type: "POST",
               url: 'login-process.php',
               dataType: "json",
               data:  { singinemail : email, singinpassword : password },
               success: function(data)
               {
                  if (data.path) {
                    window.location = data.path;
                  }
                  else if(data.error_three === 'Incorrect Password')
                  {
                    alert('Incorrect password');
                  }else if(data.error_two === 'Incorrect Email')
                  {
                    alert('Email id not found');
                  }else{
                        window.location = data.path;
                  }
               }
           });
         });
        
        */

    });

</script>

<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

</html>