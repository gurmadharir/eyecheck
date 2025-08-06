<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('../phpmailer/PHPMailer.php');
require_once('../phpmailer/SMTP.php');
require_once('../phpmailer/Exception.php');

/**
 * Sends a password reset email using PHPMailer.
 *
 * @param string $toEmail - Recipient's email address.
 * @param string $resetLink - URL for resetting the password.
 * @return bool - True on success, false on failure.
 */
function sendResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'eyecheckhealthcare@gmail.com';  // ✅ Use official EyeCheck email
        $mail->Password   = 'wvtcpptylcmserby';               // ✅ Use secure app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email headers
        $mail->setFrom('eyecheckhealthcare@gmail.com', 'EyeCheck');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your EyeCheck Password';

        // Email body (escaped link for security)
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $mail->Body = "Click the link below to reset your password:<br><br><a href='$safeLink'>$safeLink</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo); // Log to server
        return false;
    }
}
