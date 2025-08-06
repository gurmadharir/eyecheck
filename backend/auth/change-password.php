<?php
// --- Smart dynamic session init ---
if (session_status() === PHP_SESSION_NONE) {
    // Manually scan the Referer (more reliable than REQUEST_URI in backend scripts)
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if (strpos($referer, '/admin/') !== false) {
        session_name('eyecheck_admin');
    } elseif (strpos($referer, '/healthcare/') !== false) {
        session_name('eyecheck_healthcare');
    } elseif (strpos($referer, '/patient/') !== false) {
        session_name('eyecheck_patient');
    } else {
        session_name('eyecheck_default');
    }

    session_start();
}



require_once('../../config/db.php');
require_once __DIR__ . '/../helpers/log-activity.php';

header('Content-Type: application/json');

// --- Auth check ---
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$sessionUserId = (int) $_SESSION['user_id'];
$sessionRole = $_SESSION['role'];
$targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $sessionUserId;

file_put_contents('../../debug-change-password.log', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'session_user_id' => $sessionUserId,
    'session_role' => $sessionRole,
    'is_super_admin_session' => $_SESSION['is_super_admin'] ?? 'NOT SET',
    'posted_user_id' => $_POST['user_id'] ?? 'NOT POSTED',
    'target_user_id' => $targetUserId,
    'is_self' => ($targetUserId === $sessionUserId),
    'from_url' => $_SERVER['REQUEST_URI'],
    'raw_post' => $_POST
], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

$isSelf = ($targetUserId === $sessionUserId);

try {
    // 🔄 Fetch target user's password, role, and super admin status
    $stmt = $pdo->prepare("SELECT id, password, role, is_super_admin FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    $targetUserRole = $user['role'];
    $targetIsSuperAdmin = (int) $user['is_super_admin'];

    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($isSelf) {
        // 🔐 Require current password when changing own password
        $currentPassword = $_POST['current_password'] ?? '';
        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception("Current password is incorrect.");
        }
    } else {
        // 🔐 Admin editing another user's password
        if ($sessionRole !== 'admin') {
            throw new Exception("Only admin can change other users' passwords.");
        }

        // 🚫 Block admin from editing other admins unless they are super admin
        if ($targetUserRole === 'admin') {
            if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
                throw new Exception("Only super admin can change another admin's password.");
            }
        }
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception("Passwords do not match.");
    }

    // 🛡️ Password strength check
    if (strlen($newPassword) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    $weakPasswords = ['123456', 'password', '123456789', 'qwerty', 'abc123', '111111', '11111111', '000000'];
    if (in_array(strtolower($newPassword), $weakPasswords)) {
        throw new Exception("Weak password! Choose a stronger one.");
    }

    if (password_verify($newPassword, $user['password'])) {
        throw new Exception("New password must be different from the old one.");
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$update->execute([$hashedPassword, $targetUserId])) {
        throw new Exception("Failed to update password.");
    }

    // ✅ Log the password change
    if ($isSelf) {
        // User changed their own password
        logActivity(
            $sessionUserId,
            $sessionRole,
            'CHANGE_PASSWORD',
            ucfirst($sessionRole) . " '{$user['id']}' changed their own password"
        );
    } else {
        // Admin changed another user's password
        logActivity(
            $sessionUserId,
            $sessionRole,
            'CHANGE_PASSWORD_OTHER',
            "Admin '{$sessionUserId}' changed password of {$targetUserRole} '{$targetUserId}'"
        );
    }

    session_regenerate_id(true);

    $redirectPath = $isSelf 
    ? "/eyecheck/$sessionRole/dashboard.php" 
    : ($sessionRole === 'admin' ? "/eyecheck/admin/manage.php" : "/eyecheck/$sessionRole/dashboard.php");

    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully.',
        'redirect' => $redirectPath
    ]);

    exit();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
