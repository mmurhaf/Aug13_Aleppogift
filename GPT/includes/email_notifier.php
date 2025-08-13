<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer via Composer

function sendOrderConfirmationEmail($toEmail, $toName, $orderId, $invoicePath) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.ipage.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sales@aleppogift.com';
        $mail->Password   = 'Salem1972#i';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('sales@aleppogift.com', 'AleppoGift');
        $mail->addAddress($toEmail, $toName);

        // Attachments
        $mail->addAttachment($invoicePath);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation #$orderId";
        $mail->Body    = "
            <h3>Thank you for your order, $toName!</h3>
            <p>Your order #$orderId has been received and is being processed.</p>
            <p>You can find the attached invoice in this email.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
