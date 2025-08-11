<?php
// File: /eyecheck/backend/auth/forgot-password.php
declare(strict_types=1);

require_once '../../config/db.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Africa/Mogadishu');
header('Content-Type: application/json');
session_start();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/forgot_errors.log');
error_reporting(E_ALL);

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
  exit;
}

// Validate input
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
  exit;
}

try {
  // Look up by email ONLY (no role)
  $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    // Clear any previous tokens
    $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ?")
        ->execute([$user['id']]);

    // Generate token + expiry
    $token   = bin2hex(random_bytes(32)); // 64 chars
    $expires = (new DateTime('now', new DateTimeZone('Africa/Mogadishu')))
                ->modify('+1 hour')->format('Y-m-d H:i:s');

    $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
        ->execute([$token, $expires, $user['id']]);

    // 🔗 Single shared reset page for ALL users
    // Adjust BASE_URL for your environment (prod/staging)
    $BASE_URL = 'http://localhost/eyecheck';
    $resetLink = $BASE_URL . "/reset-password.php?token={$token}";

    // Send email (PHPMailer)
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->CharSet = 'UTF-8';
      $mail->Encoding = 'base64';
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;

      // ⚠️ Use an App Password and move these to env/secure config in production
      $mail->Username = 'visioncare.ai@gmail.com';
      $mail->Password = 'snqv vvso tyiq sqsl';

      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;

      $mail->setFrom('visioncare.ai@gmail.com', 'EyeCheck');
      $mail->addAddress($user['email'], $user['full_name']);
      $mail->isHTML(true);
      $mail->Subject = 'EyeCheck Password Reset';

      $mail->Body = "
        <div style='font-family: Arial, sans-serif; color:#333; padding:20px'>
          <h2 style='color:#0066cc'>🔐 Reset Your Password</h2>
          <p>Hello <strong>".htmlspecialchars($user['full_name'])."</strong>,</p>
          <p>You requested to reset your EyeCheck account password.</p>
          <p>Click the button below to reset it. This link will expire in <strong>1 hour</strong>:</p>
          <p style='margin:30px 0'>
            <a href='{$resetLink}' style='background:#00796b; color:#fff; padding:12px 26px; text-decoration:none; border-radius:6px'>🔁 Reset Password</a>
          </p>
          <p>If you didn’t request a password reset, please ignore this email.</p>
          <p style='font-size:12px; color:#888'>This is an automated message. Please do not reply.</p>
        </div>
      ";

      $mail->send();
    } catch (Exception $e) {
      error_log("Forgot Mail Error: " . $mail->ErrorInfo);
    }
  }

  // Anti-enumeration: always return success
  sleep(2);
  echo json_encode(['success' => true, 'message' => 'We’ve sent you a reset link. Please check your email.']);
} catch (Throwable $t) {
  error_log('Forgot handler error: ' . $t->getMessage());
  echo json_encode(['success' => true, 'message' => 'We’ve sent you a reset link. Please check your email.']);
}
