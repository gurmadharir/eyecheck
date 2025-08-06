]<?php
header('Content-Type: application/json');
require_once('../../helpers/auth-check.php');
requireRole('admin');
require_once('../../../config/db.php');
require_once __DIR__ . '/../../helpers/log-activity.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/SMTP.php';
require_once __DIR__ . '/../../phpmailer/Exception.php';

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
  echo json_encode(['success' => false, 'message' => 'User ID is required']);
  exit;
}

// ✅ Fetch user directly (user_id is passed from frontend!)
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND role = 'patient'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo json_encode(['success' => false, 'message' => 'User not found or not a patient']);
  exit;
}

// 📧 Compose email
$subject = "⚠️ Health Alert: Possible Conjunctivitis Detected";
$message = "
<div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
  <h2 style='color: #cc0000;'>🚨 EyeCheck Health Warning</h2>
  <p>Dear <strong>{$user['full_name']}</strong>,</p>
  <p>Our system has flagged one of your recent eye scan results with signs of <strong>Conjunctivitis</strong> (pink eye) 👁️‍🗨️.</p>
  <p>⚠️ This condition can be contagious or worsen if left untreated.</p>
  <p>🩺 We strongly advise you to visit a healthcare provider or eye specialist as soon as possible for a professional diagnosis and treatment.</p>
  <br>
  <p>Stay safe,<br><strong>EyeCheck Team 🩺</strong></p>
</div>
";

// ✉️ Send email
$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->CharSet = 'UTF-8';
  $mail->Encoding = 'base64';
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'visioncare.ai@gmail.com';
  $mail->Password = 'snqv vvso tyiq sqsl'; // 🔐 Suggest using env variable
  $mail->SMTPSecure = 'tls';
  $mail->Port = 587;

  $mail->setFrom('visioncare.ai@gmail.com', 'EyeCheck');
  $mail->addAddress($user['email'], $user['full_name']);
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $message;

  $mail->send();

  // ✅ Increment warning count
  $pdo->prepare("UPDATE users SET warnings_sent = warnings_sent + 1 WHERE id = ?")
      ->execute([$user_id]);
      
  // LOG
  logActivity($_SESSION['user_id'], 'admin', 'SEND_WARNING_EMAIL', "Sent health warning to patient (ID: {$user['id']}, Email: {$user['email']})", $user['id']);

  echo json_encode(['success' => true, 'message' => 'Health warning email sent to patient']);

} catch (Exception $e) {
  error_log("❌ PHPMailer error (patient): " . $mail->ErrorInfo);

  // Log
  logActivity($_SESSION['user_id'], 'admin', 'SEND_WARNING_EMAIL_FAILED', "Failed to send warning to patient (ID: {$user_id}): " . $mail->ErrorInfo, $user_id);

  echo json_encode(['success' => false, 'message' => 'Failed to send health warning email']);

}
