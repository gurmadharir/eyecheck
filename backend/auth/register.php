<?php
require_once '../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';

require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/register_errors.log');
error_reporting(E_ALL);

// 🧹 Clean up old pending registrations
try {
    $pdo->prepare("DELETE FROM pending_users WHERE created_at < (NOW() - INTERVAL 30 MINUTE)")->execute();
} catch (PDOException $e) {
    error_log("❌ Cleanup failed: " . $e->getMessage());
}

// ✅ Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ✅ Sanitize inputs
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = trim($_POST['password'] ?? '');

// ✅ Validate full name
if (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
    echo json_encode(['success' => false, 'message' => "Full name must only contain letters and spaces."]);
    exit;
}

// ✅ Validate password strength
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => "Password must be at least 6 characters long."]);
    exit;
}

$weakPasswords = ['123456', 'password', '123456789', 'qwerty', 'abc123', '111111', '000000'];
if (in_array(strtolower($password), $weakPasswords)) {
    echo json_encode(['success' => false, 'message' => "Weak password! Choose a stronger one."]);
    exit;
}

// ✅ Check for duplicate email or username across both tables
try {
    $check = $pdo->prepare("
        SELECT id FROM users WHERE username = ? OR email = ?
        UNION
        SELECT id FROM pending_users WHERE username = ? OR email = ?
    ");
    $check->execute([$username, $email, $username, $email]);

    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => "Username or email already taken."]);
        exit;
    }
} catch (PDOException $e) {
    error_log("❌ Duplicate check failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Server error. Try again later."]);
    exit;
}

// ✅ Generate token and hashed password
$token = bin2hex(random_bytes(32));
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$verifyLink = "http://localhost/eyecheck/patient/verify.php?token=$token";

// ✅ Send verification email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'visioncare.ai@gmail.com';
    $mail->Password = 'snqv vvso tyiq sqsl'; // 🔐 Consider storing in ENV
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('visioncare.ai@gmail.com', 'EyeCheck');
    $mail->addAddress($email, $full_name);
    $mail->isHTML(true);
    $mail->Subject = 'Verify your Eyecheck account';
    $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                <h2 style='color: #0066cc;'>👁️ Welcome to Eyecheck!</h2>
                <p>Hello <strong>$full_name</strong>,</p>

                <p>Thank you for registering with <strong>EyeCheck</strong> — your trusted tool for early eye condition detection.</p>

                <p>To activate your account and keep it secure, please verify your email address within <strong>30 minutes</strong> by clicking the button below:</p>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$verifyLink' style='background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;'>✅ Verify My Account</a>
                </p>

                <p>If the button doesn’t work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #0066cc;'>$verifyLink</p>

                <hr style='margin: 40px 0;'>
                <p><strong>Why verify?</strong></p>
                <ul>
                    <li>🔐 Secure your EyeCheck account</li>
                    <li>📈 Access your dashboard and detection reports</li>
                    <li>📬 Get important updates and alerts</li>
                </ul>

                <p>If you didn’t sign up for EyeCheck, just ignore this message. Your data will be automatically deleted after 30 minutes. 🕒</p>

                <p style='margin-top: 40px;'>Regards,<br><strong>The EyeCheck Team</strong></p>

                <p style='font-size: 12px; color: #999;'>This is an automated email — please do not reply.</p>
            </div>
    ";
    $mail->send();
} catch (Exception $e) {
    error_log("❌ PHPMailer Error: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => "Could not send verification email."]);
    exit;
}

// ✅ Store user in pending table
try {
    $stmt = $pdo->prepare("INSERT INTO pending_users (full_name, username, email, password, token, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$full_name, $username, $email, $hashedPassword, $token]);

    logActivity(
    null,
    'patient',
    'REGISTER_ATTEMPT',
    "New patient registration: username '$username', email '$email'"
    );

} catch (PDOException $e) {
    error_log("❌ DB Insert failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Server error. Could not complete registration."]);
    exit;
}

// ✅ Respond to frontend
echo json_encode([
    'success' => true,
    'redirect' => "/eyecheck/patient/register.php"
]);
