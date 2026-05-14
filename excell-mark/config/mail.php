<?php
// Note: This requires PHPMailer. If you don't have it yet via Composer,
// this script will simulate sending by logging the OTP and returning true.
// To use for real, run: composer require phpmailer/phpmailer inside excell-mark/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to load composer autoloader if it exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function sendOTP($email, $otp_code) {
    // Stub for testing if PHPMailer isn't installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("OTP for $email is $otp_code");
        return true; 
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        // IMPORTANT: Replace with actual credentials
        $mail->Username   = 'your_gmail@gmail.com'; 
        $mail->Password   = 'your_app_password';    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@excellmark.com', 'ExcellMark System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your ExcellMark Login OTP';
        $mail->Body    = "Your verification code is: <b style='font-size: 24px; color: #4f72ff;'>$otp_code</b><br>It expires in 5 minutes.";
        $mail->AltBody = "Your verification code is: $otp_code. It expires in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
