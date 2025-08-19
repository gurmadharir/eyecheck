<?php
session_name("eyecheck_admin");
session_start();

require_once '../../config/db.php';
header("Content-Type: application/json");

// Only admins
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$isSuperAdmin = !empty($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1;

$targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : null;

if ($targetId <= 0 || ($status !== 0 && $status !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

if ($targetId === $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own status.']);
    exit();
}

// Protect admin accounts for non-super-admins
$stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmtRole->execute([':id' => $targetId]);
$targetRole = $stmtRole->fetchColumn();
if ($targetRole === 'admin' && !$isSuperAdmin) {
    echo json_encode(['success' => false, 'message' => 'Only super admins can change admin status.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $targetId]);

    $msg = $status ? 'User activated.' : 'User deactivated.';
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (PDOException $e) {
    error_log("Toggle status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
