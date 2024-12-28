<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);
$message = "
 <div style='background-color: aliceblue;'>
        <div style='margin-top: 5px; border: 0px solid red; padding: 10px;'>
            <div
                style='border: 0px solid blue; text-align: center; padding: 5px 0px 5px 0px; background-color:#F6F1EE; color: Date;'>
                <span style='text-transform: uppercase;  font-weight: 900;'>Invoice for Item order #12343</span>
                (goodguyng.com)
            </div>
            <div style='margin-top: 10px;'>
                <div style='margin-top: 2px'><b>Order No:</b> 0433453</div>
                <div style='margin-top: 2px'><b>Order Date:</b> 16-sep-2025</div>
                <div style='margin-top: 2px'><b>Invoice Date:</b> 15-oct-2024</div>
            </div>
        </div>
        <div style='margin-top: 10px; border: 0px solid blue; padding: 10px;'>
            <div>

                <div style='text-decoration: underline; font-weight: 900;'>Delivery Address</div>
                <div>
                    No 31 saint finbers road akoka lagos<br />
                    <b>Phone Number:</b> 08051067944
                </div>
                <div>
                    <b>Email Address:</b> Ibrahimcome3@gmail.com
                </div>
            </div>
        </div>
        <br />
        <table border='1'
            style='background-color:#FFFFCC;border-collapse:collapse; border:0px solid #FFCC00;color:#000000;width:100%'
            cellpadding='3' cellspacing='3'>

            <tr>
                <th>#</th>
                <th>Item Description</th>
                <th>Picture</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Order Date</th>
                <th>Order Status</th>
                <th>Possibly delivery date</th>
            </tr>
            <tr>
                <td>1</td>
                <td>iphone 15</td>
                <td><img src='https://rukminim2.flixcart.com/image/312/312/xif0q/mobile/k/l/l/-original-imagtc5fz9spysyk.jpeg?q=70'
                        width='50' />
                </td>
                <td>Naira 1,700,000</td>
                <td>2(x)</td>
                <td>3,300,000</td>
                <td>16-10-2022</td>
                <td>Not delivered</td>
                <td>15-2-23 to 17-2-23</td>
            </tr>
            <tr>
                <td>1</td>
                <td>iphone 15</td>
                <td><img src='https://rukminim2.flixcart.com/image/312/312/xif0q/mobile/k/l/l/-original-imagtc5fz9spysyk.jpeg?q=70'
                        width='50' />
                </td>
                <td>Naira 1,700,000</td>
                <td>2(x)</td>
                <td>3,300,000</td>
                <td>16-10-2022</td>
                <td>Not delivered</td>
                <td>15-2-23 to 17-2-23</td>
            </tr>
            <tr>
                <td>1</td>
                <td>iphone 15</td>
                <td><img src='https://rukminim2.flixcart.com/image/312/312/xif0q/mobile/k/l/l/-original-imagtc5fz9spysyk.jpeg?q=70'
                        width='50' />
                </td>
                <td>Naira 1,700,000</td>
                <td>2(x)</td>
                <td>3,300,000</td>
                <td>16-10-2022</td>
                <td>Not delivered</td>
                <td>15-2-23 to 17-2-23</td>
            </tr>
            <tr>
                <td>1</td>
                <td>iphone 15</td>
                <td><img src='https://rukminim2.flixcart.com/image/312/312/xif0q/mobile/k/l/l/-original-imagtc5fz9spysyk.jpeg?q=70'
                        width='50' />
                </td>
                <td>Naira 1,700,000</td>
                <td>2(x)</td>
                <td>3,300,000</td>
                <td>16-10-2022</td>
                <td>Not delivered</td>
                <td>15-2-23 to 17-2-23</td>
            </tr>

            <tr>
                <td></td>
                <td>Tax</td>
                <td colspan=''>7%</td>
            </tr>
            <tr>
                <td></td>
                <td>Tatal Cost</td>
                <td colspan=''>Naira 3,449,443.65</td>
            </tr>
        </table>
    </div>
</body>
";
try {
    //Server settings
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    //$mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.hostinger.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   =  true;                                   //Enable SMTP authentication
    $mail->Username   = 'care@goodguyng.com';                     //SMTP username
    $mail->Password   = 'PPassword12@';                               //SMTP password
    $mail->Port       =  465;  

    //Recipients
    $mail->setFrom('care@goodguyng.com', 'XXXXX.com order');
    $mail->addAddress('aliyumrm99@gmail.com');     //Add a recipient
              //Name is optional


    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = $message;
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    //var_dump($mail);
    $mail->send();
    
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}