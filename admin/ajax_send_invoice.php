<?php
// filepath: c:\wamp64\www\goodguy\admin\ajax_send_invoice.php
require_once __DIR__ . "/../includes.php";
require_once __DIR__ . "/../class/Order.php";
require_once __DIR__ . "/../vendor/autoload.php"; // For PHPMailer and TCPDF
require_once __DIR__ . "/../mail_config.php"; // Mail configuration


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use TCPDF as PDF;

// Set headers at the beginning
header('Content-Type: application/json');

// Check for admin login
session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get request data
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$additionalEmails = isset($_POST['additional_emails']) ? $_POST['additional_emails'] : '';

if (!$orderId) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Initialize order object and get order details
    $orderObj = new Order($pdo);
    $order = $orderObj->getOrderDetails($orderId);

    if (!$order) {
        throw new Exception("Order not found");
    }

    // Get customer email from order
    $customerEmail = $order['customer_email']; // Correct key from getOrderDetails

    // Generate PDF invoice
    $pdfPath = generateInvoicePDF($order, $orderObj);

    // Send email
    $emailResult = sendInvoiceEmail($customerEmail, $additionalEmails, $order, $pdfPath);

    // Clean up temporary PDF file
    @unlink($pdfPath);

    echo json_encode(['success' => true, 'message' => 'Invoice sent successfully']);
} catch (Exception $e) {
    error_log('Error sending invoice: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Generate PDF invoice for an order
 *
 * @param array $order Order details
 * @param Order $orderObj Order object for getting additional data
 * @return string Path to the generated PDF file
 */
function generateInvoicePDF($order, $orderObj)
{
    // Create new PDF document
    $pdf = new PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('GoodGuy Shop');
    $pdf->SetAuthor('GoodGuy Shop');
    $pdf->SetTitle('Invoice #' . $order['order_id']);
    $pdf->SetSubject('Invoice #' . $order['order_id']);

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add a page
    $pdf->AddPage();

    // Get store information and logo
    $storeName = "GoodGuy Shop";
    $storeAddress = "123 Main Street, City, Country";
    $storePhone = "+2348051067944";
    $storeEmail = "care@goodguyng.com";

    // Logo
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/goodguy/assets/images/goodguy.svg'; // Use SVG for better quality
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 15, 30, 0, '', '', 'T', false, 300, '', false, false, 0);
    }

    // Set font
    $pdf->SetFont('helvetica', 'B', 14);

    // Title
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Invoice #: ' . $order['order_id'], 0, 1, 'R');
    $pdf->Cell(0, 5, 'Date: ' . date('F j, Y', strtotime($order['order_date_created'])), 0, 1, 'R');

    $pdf->Ln(10);

    // Store info
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 5, 'From:', 0, 0, 'L');
    $pdf->Cell(90, 5, 'Bill To:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    // Get shipping address
    $shippingAddress = $orderObj->getOrderShippingAddress($order['order_shipping_address']);
    $shippingAddressFormatted = 'N/A';
    if ($shippingAddress) {
        $stateName = $orderObj->getShippingAddressStateName((int) $order['order_shipping_address']);
        $shippingAddressFormatted = htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']) . "\n" .
            htmlspecialchars($shippingAddress['address1']) . "\n" .
            (!empty($shippingAddress['address2']) ? htmlspecialchars($shippingAddress['address2']) . "\n" : '') .
            htmlspecialchars($shippingAddress['city']) . ", " . htmlspecialchars($stateName) . " " . htmlspecialchars($shippingAddress['zip'] ?? '') . "\n" .
            htmlspecialchars($shippingAddress['country']);
    }

    $pdf->Cell(90, 5, $storeName, 0, 0, 'L');
    $pdf->Cell(90, 5, htmlspecialchars($order['customer_name']), 0, 1, 'L');
    $pdf->Cell(90, 5, $storeAddress, 0, 0, 'L');
    $pdf->Cell(90, 5, htmlspecialchars($order['customer_email']), 0, 1, 'L');
    $pdf->Cell(90, 5, 'Phone: ' . $storePhone, 0, 0, 'L');
    $pdf->MultiCell(90, 5, $shippingAddressFormatted, 0, 'L');
    $pdf->Cell(90, 5, 'Email: ' . $storeEmail, 0, 1, 'L');

    $pdf->Ln(10);

    // Order items
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);

    // Table header
    $pdf->Cell(80, 7, 'Product', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Price', 1, 0, 'R', true);
    $pdf->Cell(30, 7, 'Quantity', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Total', 1, 1, 'R', true);

    // Get order items
    $items = $orderObj->getOrderItems($order['order_id']);

    $pdf->SetFont('helvetica', '', 10);
    foreach ($items as $item) {
        $lineTotal = (float) ($item['item_price'] ?? 0) * (int) ($item['quwantitiyofitem'] ?? 0);
        $pdf->Cell(80, 7, $item['description'], 'LRB', 0, 'L');
        $pdf->Cell(30, 7, '₦' . number_format((float) ($item['item_price'] ?? 0), 2), 'LRB', 0, 'R');
        $pdf->Cell(30, 7, (int) ($item['quwantitiyofitem'] ?? 0), 'LRB', 0, 'C');
        $pdf->Cell(40, 7, '₦' . number_format($lineTotal, 2), 'LRB', 1, 'R');
    }

    // Totals
    $pdf->Ln(5);
    $pdf->Cell(110, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, '₦' . number_format((float) ($order['order_subtotal'] ?? 0), 2), 0, 1, 'R');

    if (isset($order['shipping_cost']) && (float) $order['shipping_cost'] > 0) {
        $pdf->Cell(110, 7, '', 0, 0);
        $pdf->Cell(30, 7, 'Shipping:', 0, 0, 'R');
        $pdf->Cell(40, 7, '₦' . number_format((float) ($order['shipping_cost'] ?? 0), 2), 0, 1, 'R');
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(110, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'Total:', 0, 0, 'R');
    $pdf->Cell(40, 7, '₦' . number_format((float) ($order['order_total'] ?? 0), 2), 0, 1, 'R');

    // Footer note
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');

    // Generate temporary file path
    $tempFilePath = tempnam(sys_get_temp_dir(), 'invoice_') . '.pdf';

    // Save PDF to file
    $pdf->Output($tempFilePath, 'F');

    return $tempFilePath;
}

/**
 * Send invoice email with PDF attachment
 *
 * @param string $customerEmail Primary recipient email
 * @param string $additionalEmails Comma-separated list of additional emails
 * @param array $order Order details
 * @param string $pdfPath Path to the PDF file to attach
 * @return bool Whether the email was sent successfully
 */
function sendInvoiceEmail($customerEmail, $additionalEmails, $order, $pdfPath)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($customerEmail);

        // Add additional recipients if provided
        if (!empty($additionalEmails)) {
            $emails = array_map('trim', explode(',', $additionalEmails));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($email);
                }
            }
        }

        // Add reply-to
        $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);

        // Attachments
        $mail->addAttachment($pdfPath, 'Invoice_' . $order['order_id'] . '.pdf');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Invoice #' . $order['order_id'] . ' from GoodGuy Shop';

        // Email body
        $emailBody = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="cid:logo" alt="GoodGuy Shop Logo" style="max-width: 150px;">
                </div>

                <h2 style="color: #444;">Thank you for your order!</h2>
                <p>Dear ' . htmlspecialchars($order['customer_name']) . ',</p>

                <p>Thank you for shopping with GoodGuy Shop. Your invoice for order #' . $order['order_id'] . ' is attached to this email.</p>

                <div style="background-color: #f7f7f7; padding: 15px; margin: 20px 0; border-radius: 5px;">
                    <p><strong>Order Number:</strong> #' . $order['order_id'] . '</p>
                    <p><strong>Order Date:</strong> ' . date('F j, Y', strtotime($order['order_date_created'])) . '</p>
                    <p><strong>Order Total:</strong> ₦' . number_format((float) ($order['order_total'] ?? 0), 2) . '</p>
                </div>

                <p>If you have any questions about your order, please contact our customer service team at <a href="mailto:care@goodguyng.com">care@goodguyng.com</a>.</p>

                <p>Thank you again for your business!</p>

                <p>Best regards,<br>
                The GoodGuy Shop Team</p>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center;">
                    <p>&copy; ' . date('Y') . ' GoodGuy Shop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $emailBody;

        // Add logo as embedded image
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/goodguy/assets/images/goodguy.svg'; // Adjust path
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo');
        }

        // Plain text alternative
        $mail->AltBody = 'Thank you for your order #' . $order['order_id'] . ' from GoodGuy Shop. Your invoice is attached to this email.';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        throw new Exception("Email could not be sent: " . $mail->ErrorInfo);
    }
}