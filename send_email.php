<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

function sendEmail($toEmail, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Email configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // SMTP host for Gmail
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Your Gmail address
        $mail->Password = ''; // Your Gmail app-specific password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        

        // Enable debugging for detailed logs (disable in production)
        $mail->SMTPDebug = 2; // Set to 2 for debugging

        // Set sender and recipient
        $mail->setFrom('', 'Publication System'); // Replace with a valid email
        $mail->addAddress($toEmail); // Recipient's email

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'Email could not be sent. Error: ' . $mail->ErrorInfo;
    }
}
?>
