<?php
require_once '../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';
header('Content-Type: application/json');

// 🟡 Role-based session setup
$role = $_POST['role'] ?? 'patient';
if ($role === 'patient') session_name('eyecheck_patient');
elseif ($role === 'healthcare') session_name('eyecheck_healthcare');
elseif ($role === 'admin') session_name('eyecheck_admin');
session_start();

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/reset_errors.log');
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $token = trim($_POST['token'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (!$token || !$email) {
        throw new Exception('Missing reset token or email.');
    }

    if ($new_password !== $confirm_password) {
        throw new Exception('Passwords do not match.');
    }

    if (strlen($new_password) < 6) {
        throw new Exception('Password must be at least 6 characters.');
    }

    $weakPasswords = ['123456', 'password', '123456789', 'qwerty', 'abc123'];
    if (in_array(strtolower($new_password), $weakPasswords)) {
        throw new Exception('Weak password! Choose a stronger one.');
    }

    // ✅ Get user by token only
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Invalid or expired reset link.');
    }

    // ✅ Validate token expiration
    $now = new DateTime('now', new DateTimeZone('Africa/Mogadishu'));
    $expires = new DateTime($user['reset_expires'], new DateTimeZone('Africa/Mogadishu'));

    if ($now > $expires) {
        throw new Exception('This reset link has expired. Please request a new one.');
    }

    if (strtolower($email) !== strtolower($user['email'])) {
        throw new Exception('Email does not match the reset request.');
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $update->execute([$hashed, $user['id']]);

    logActivity(
    $user['id'],
    $user['role'],
    'RESET_PASSWORD',
    ucfirst($user['role']) . " '{$user['username']}' reset their password."
    );

    echo json_encode(['success' => true, 'message' => 'Password reset successfully. You can now log in.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
