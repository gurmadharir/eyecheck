<?php
ob_clean(); 
header('Content-Type: application/json');
require_once('../../helpers/auth-check.php');
requireRole('admin');
require_once('../../../config/db.php');
require_once('../../helpers/log-activity.php'); 


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/SMTP.php';
require_once __DIR__ . '/../../phpmailer/Exception.php';

$user_id = $_POST['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
  exit;
}

// Fetch user email and name from pending_users table
$stmt = $pdo->prepare("SELECT full_name, email, created_at FROM pending_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo json_encode(['success' => false, 'message' => 'User not found or not pending']);
  exit;
}

// Compose email message (customize as needed)
$subject = "Reminder: Complete Your Registration";
$expires = new DateTime($user['created_at']);
$expires->modify('+2 days');
$expiresStr = $expires->format('Y-m-d H:i');

$message = "
<div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
  <h2 style='color: #0066cc;'>⏳ Reminder: Complete Your Registration</h2>
  <p>Dear <strong>{$user['full_name']}</strong>,</p>
  <p>Your registration is pending and will expire on <strong>{$expiresStr}</strong>.</p>
  <p>🚨 Please complete your registration before this time to avoid losing access.</p>
  <p>🙏 Thank you,<br><strong>EyeCheck Team</strong></p>
</div>
";

// Send email using PHPMailer
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
  $mail->addAddress($user['email'], $user['full_name']);
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $message;

  $mail->send();
  
  // ✅ Log success
  logActivity($_SESSION['user_id'], 'admin', 'SEND_REMINDER_EMAIL', "Sent reminder email to pending user (ID: $user_id, Email: {$user['email']})", $user_id);

  echo json_encode(['success' => true, 'message' => 'Reminder email sent successfully']);
} catch (Exception $e) {
  error_log("❌ PHPMailer error: " . $mail->ErrorInfo);

  logActivity($_SESSION['user_id'], 'admin', 'SEND_REMINDER_EMAIL_FAILED', "Failed to send reminder email to pending user (ID: $user_id): " . $mail->ErrorInfo, $user_id);
  echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
