<?php
// filepath: c:\wamp64\www\goodguy\class\Sms.php

namespace PHPMailer\PHPMailer;

/**
 * Simple SMS sending class for GoodGuy
 */
class Sms
{
    /**
     * Send an SMS message
     * 
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message to send
     * @return bool True on success, false on failure
     */
    public function send($phoneNumber, $message)
    {
        // For now, this is a placeholder. You should integrate with an actual SMS provider.
        // Some popular SMS gateways in Nigeria include Termii, Twilio, BulkSMS, etc.

        try {
            // OPTION 1: Log the message for now (until you implement a real SMS gateway)
            $logFile = __DIR__ . '/../logs/sms_log.txt';
            $logDir = dirname($logFile);

            // Create logs directory if it doesn't exist
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logMessage = date('Y-m-d H:i:s') . " | To: $phoneNumber | Message: $message\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);

            // OPTION 2: Uncomment and modify this code when you're ready to implement a real SMS gateway
            /*
            // Example using Termii API (you would need to sign up and get API credentials)
            $apiKey = 'YOUR_TERMII_API_KEY';
            $senderId = 'GoodGuy';

            $payload = [
                'to' => $phoneNumber,
                'from' => $senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $apiKey,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.ng.termii.com/api/sms/send');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($payload))
            ]);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log("cURL Error when sending SMS: " . $err);
                return false;
            }

            $responseData = json_decode($response, true);
            return isset($responseData['message_id']);
            */

            return true; // For now, always return success

        } catch (\Exception $e) {
            error_log("Error sending SMS: " . $e->getMessage());
            return false;
        }
    }
}