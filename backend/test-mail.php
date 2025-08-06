<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'eyecheckhealthcare@gmail.com';
    $mail->Password = 'htvyclcrtuxqylmt';  // App password (not Gmail login)
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('eyecheckhealthcare@gmail.com', 'EyeCheck Test');
    $mail->addAddress('your_email@example.com');  // Replace with your email

    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body = '✅ This is a test email from EyeCheck using PHPMailer.';

    $mail->send();
    echo '✅ Test email sent successfully.';
} catch (Exception $e) {
    echo '❌ Mailer Error: ' . $mail->ErrorInfo;
}
