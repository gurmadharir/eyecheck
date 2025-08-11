<?php
// One universal reset handler (no role, no email)
declare(strict_types=1);

require_once '../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';
header('Content-Type: application/json');

// Log errors to file (hide on screen)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/reset_errors.log');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // === Inputs ===
    $token             = trim($_POST['token'] ?? '');
    $new_password      = trim($_POST['new_password'] ?? '');
    $confirm_password  = trim($_POST['confirm_password'] ?? '');

    // === Validate ===
    if ($token === '' || strlen($token) < 40) {
        throw new Exception('Invalid or missing reset token.');
    }
    if ($new_password === '' || $confirm_password === '') {
        throw new Exception('Password fields are required.');
    }
    if ($new_password !== $confirm_password) {
        throw new Exception('Passwords do not match.');
    }
    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters.');
    }
    $weak = ['123456', 'password', '123456789', 'qwerty', 'abc123'];
    if (in_array(strtolower($new_password), $weak, true)) {
        throw new Exception('Weak password! Choose a stronger one.');
    }

    // === Validate token + expiry using DB UTC clock ===
    // Make sure your "forgot" script sets: reset_expires = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR)
    $stmt = $pdo->prepare("
        SELECT id, username, role
        FROM users
        WHERE reset_token = ?
          AND reset_expires > UTC_TIMESTAMP()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('This reset link is invalid or has expired. Please request a new one.');
    }

    // === Update password & clear token ===
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("
        UPDATE users
        SET password = ?, reset_token = NULL, reset_expires = NULL
        WHERE id = ?
    ");
    $upd->execute([$hash, $user['id']]);

    // Optional audit log (best-effort)
    try {
        logActivity((int)$user['id'], (string)($user['role'] ?? 'unknown'), 'RESET_PASSWORD',
            "User '{$user['username']}' reset their password.");
    } catch (Throwable $t) { /* ignore logging errors */ }

    echo json_encode(['success' => true, 'message' => 'Password reset successfully. You can now log in.']);
} catch (Throwable $e) {
    error_log('Reset error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
