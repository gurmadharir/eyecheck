<?php
session_name("eyecheck_admin");
session_start();

require_once '../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';


// 🔐 Require authenticated admin
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    $_SESSION['message'] = "Unauthorized access.";
    $_SESSION['msg_type'] = 'error';
    header("Location: ../../admin/manage.php");
    exit();
}

// 🔒 Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['msg_type'] = 'error';
    header("Location: ../../admin/manage.php");
    exit();
}

// 🔍 Sanitize and validate inputs
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$role = in_array($_POST['role'] ?? '', ['admin', 'healthcare']) ? $_POST['role'] : null;
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$region = trim($_POST['region'] ?? '');

$errors = [];

// ✅ Validate required fields
if (!$id || !$role || !$full_name || !$username || !$email) {
    $errors[] = "All fields are required.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}
if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $errors[] = "Username must be 3–30 characters and contain only letters, numbers, and underscores.";
}
if (!preg_match("/^[a-zA-Z' ]{2,60}$/", $full_name)) {
    $errors[] = "Full name must be 2–60 characters and only contain letters and apostrophes.";
}
if ($role === 'healthcare' && empty($region)) {
    $errors[] = "Region is required for healthcare staff.";
}

// ❌ Handle errors
if (!empty($errors)) {
    $_SESSION['message'] = htmlspecialchars(implode('<br>', $errors));
    $_SESSION['msg_type'] = 'error';
    header("Location: ../../admin/manage.php?role=$role");
    exit();
}

try {
    // 🚫 Check if username/email already used by another user
    $check = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
    $check->execute([
        ':username' => $username,
        ':email' => $email,
        ':id' => $id
    ]);
    if ($check->rowCount()) {
        $_SESSION['message'] = "Username or email already in use.";
        $_SESSION['msg_type'] = 'error';
        header("Location: ../../admin/manage.php?role=$role");
        exit();
    }

    // ✅ Proceed with update
    $update = $pdo->prepare("
        UPDATE users SET 
            full_name = :full_name,
            username = :username,
            email = :email,
            healthcare_region = :region
        WHERE id = :id
    ");
    $update->execute([
        ':full_name' => $full_name,
        ':username' => $username,
        ':email' => $email,
        ':region' => ($role === 'healthcare') ? $region : null,
        ':id' => $id
    ]);

    // LOG
    logActivity($_SESSION['user_id'], 'admin', 'UPDATE_USER', "Updated $role (ID: $id, Username: $username)", $id);

    $_SESSION['message'] = ucfirst($role) . " updated successfully.";
    $_SESSION['msg_type'] = 'success';
    header("Location: ../../admin/manage.php?role=$role");
    exit();

} catch (PDOException $e) {
    error_log("Admin update error: " . $e->getMessage());

    // LOG
    logActivity($_SESSION['user_id'], 'admin', 'UPDATE_VALIDATION_ERROR', "Validation failed updating $role (ID: $id): " . implode(' | ', $errors));
    
    $_SESSION['message'] = "Database error. Please try again later.";
    $_SESSION['msg_type'] = 'error';
    header("Location: ../../admin/manage.php?role=$role");
    exit();
}
