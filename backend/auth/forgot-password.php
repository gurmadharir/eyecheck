<?php
require_once '../../config/db.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Africa/Mogadishu'); // 🕒 Ensure correct timezone

header('Content-Type: application/json');
session_start();
ini_set('display_errors', 1); // 🔧 Enable error display for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/forgot_errors.log');
error_reporting(E_ALL);

// ✅ Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ✅ Collect and validate input
$email = trim($_POST['email'] ?? '');
$role  = trim($_POST['role'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

if (!in_array($role, ['patient', 'healthcare', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user role.']);
    exit;
}

// ✅ Check if user exists
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND role = ?");
$stmt->execute([$email, $role]);
$user = $stmt->fetch();

if ($user) {
    // ✅ Clear old reset token
    $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ?")
        ->execute([$user['id']]);

    // ✅ Create new token and expiry (correct timezone)
    $token   = bin2hex(random_bytes(32));
    $now     = new DateTime('now', new DateTimeZone('Africa/Mogadishu'));
    $expires = $now->modify('+1 hour')->format("Y-m-d H:i:s");

    $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
        ->execute([$token, $expires, $user['id']]);

    $resetLink = "http://localhost/eyecheck/$role/reset-password.php?token=$token";

    // ✅ Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'visioncare.ai@gmail.com';
        $mail->Password = 'snqv vvso tyiq sqsl';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('visioncare.ai@gmail.com', 'EyeCheck');
        $mail->addAddress($email, $user['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'EyeCheck Password Reset';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                <h2 style='color: #0066cc;'>🔐 Reset Your Password</h2>
                <p>Hello <strong>{$user['full_name']}</strong>,</p>
                <p>You requested to reset your EyeCheck account password.</p>
                <p>Click the button below to reset it. This link will expire in <strong>1 hour</strong>:</p>
                <p style='text-align: left; margin: 30px 0;'>
                    <a href='$resetLink' style='background-color: #00796b; color: white; padding: 12px 26px; text-decoration: none; border-radius: 6px;'>🔁 Reset Password</a>
                </p>
                <p>If you didn’t request a password reset, please ignore this email.</p>
                <p style='font-size: 12px; color: #888;'>This is an automated message. Please do not reply.</p>
            </div>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Forgot Mail Error: " . $mail->ErrorInfo);
    }
}

sleep(2); // 🕒 To prevent user enumeration
echo json_encode(['success' => true, 'message' => 'We’ve sent you a reset link. Please check your email.']);
