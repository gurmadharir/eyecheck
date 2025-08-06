<?php
session_name("eyecheck_admin");
session_start();
require_once '../../config/db.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$isEdit = isset($_POST['id']) && is_numeric($_POST['id']);
$id = $isEdit ? (int)$_POST['id'] : null;

function respond($success, $message, $redirect = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Invalid request method.");
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    respond(false, "Unauthorized access.");
}

$role       = strtolower(trim($_POST['role'] ?? ''));
$full_name  = trim($_POST['full_name'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$region     = trim($_POST['region'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

$errors = [];

// Basic validation
if (!$role || !$full_name || !$username || !$email) {
    $errors[] = "Role, full name, username, and email are required.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}
if (!preg_match('/^[a-zA-Z0-9.]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and dots.";
}
if (!preg_match("/^[a-zA-Z' ]+$/", $full_name)) {
    $errors[] = "Full name must contain only letters, spaces, or apostrophes.";
}
if ($role === 'healthcare' && empty($region)) {
    $errors[] = "Region is required for healthcare staff.";
}

// Only super admin can manage other admins
$specialAdmin = ['username' => 'superadmin', 'email' => 'eyecheckhealthcare@gmail.com'];
$currentUserStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$currentUserStmt->execute([$_SESSION['user_id']]);
$currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);

$isSuperAdmin = $currentUser && (
    $currentUser['username'] === $specialAdmin['username'] ||
    $currentUser['email'] === $specialAdmin['email']
);
if ($role === 'admin' && !$isSuperAdmin) {
    $errors[] = "Only the super administrator can manage admin accounts.";
}

// Password validation (ONLY for new user)
if (!$isEdit) {
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    $weak = ['123456', 'password', '123456789', 'qwerty', 'abc123', '000000'];
    if (in_array(strtolower($password), $weak)) {
        $errors[] = "Weak password. Choose a stronger one.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
}

// Check for duplicate username/email
$dupSQL = $isEdit
    ? "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id"
    : "SELECT id FROM users WHERE username = :username OR email = :email";

$dupStmt = $pdo->prepare($dupSQL);
$dupStmt->execute($isEdit
    ? [':username' => $username, ':email' => $email, ':id' => $id]
    : [':username' => $username, ':email' => $email]);

if ($dupStmt->rowCount() > 0) {
    $errors[] = "Username or email already exists.";
}

if (!empty($errors)) {
    respond(false, implode("<br>", $errors));
}

try {
    if ($isEdit) {
        // Fetch current values to detect changes
        $stmt = $pdo->prepare("SELECT role, full_name, username, email, healthcare_region FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $existing['role'] === $role &&
            $existing['full_name'] === $full_name &&
            $existing['username'] === $username &&
            $existing['email'] === $email &&
            ($role !== 'healthcare' || $existing['healthcare_region'] === $region)
        ) {
            respond(false, "No changes detected.");
        }

        // Update user without password
        $pdo->prepare("
            UPDATE users SET 
                role = :role,
                full_name = :full_name,
                username = :username,
                email = :email,
                healthcare_region = :region
            WHERE id = :id
        ")->execute([
            ':role' => $role,
            ':full_name' => $full_name,
            ':username' => $username,
            ':email' => $email,
            ':region' => $role === 'healthcare' ? $region : null,
            ':id' => $id
        ]);

        respond(true, "Staff updated successfully.", "./manage.php");

    } else {
        // Insert new user
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (role, full_name, username, email, healthcare_region, password)
            VALUES (:role, :full_name, :username, :email, :region, :password)
        ")->execute([
            ':role' => $role,
            ':full_name' => $full_name,
            ':username' => $username,
            ':email' => $email,
            ':region' => $role === 'healthcare' ? $region : null,
            ':password' => $hashed
        ]);

        respond(true, ucfirst($role) . " account created successfully.", "./manage.php");
    }
} catch (PDOException $e) {
    respond(false, "Database error: " . $e->getMessage());
}
