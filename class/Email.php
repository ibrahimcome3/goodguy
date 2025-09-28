<?php

class Email
{
    /**
     * Sends an email.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The subject of the email.
     * @param string $message The HTML body of the email.
     * @param string $from The sender's email address.
     * @return bool True on success, false on failure.
     */
    public function send(string $to, string $subject, string $message, string $from = 'no-reply@goodguy.com'): bool
    {
        // In a production environment, you would use a library like PHPMailer or a transactional email service.
        // For local development, using PHP's mail() function is often unreliable.
        // Instead, we will log the email to a file for verification.

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: GoodGuy Admin <' . $from . '>' . "\r\n";

        // Check if the logs directory exists, if not, create it.
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logMessage = "---- New Email ----\n" .
            "Timestamp: " . date('Y-m-d H:i:s') . "\n" .
            "To: {$to}\n" .
            "From: {$from}\n" .
            "Subject: {$subject}\n" .
            "Body: \n{$message}\n" .
            "-------------------\n\n";

        // Append to a log file
        if (file_put_contents($logDir . '/emails.log', $logMessage, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to email log file: " . $logDir . '/emails.log');
            return false;
        }

        // For local dev, we'll return true to simulate a successful send.
        // In production, you would use: return mail($to, $subject, $message, $headers);
        return true;
    }
}