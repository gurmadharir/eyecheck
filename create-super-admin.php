<?php
require_once './config/db.php';

// Set super admin details
$username = 'Super Admin';
$email = 'eyecheckhealthcare@gmail.com';
$password = 'EyeCheck@2024'; // You can change this if needed
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';
$isSuperAdmin = 1;

// Check if user already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    echo "❌ A user with this email already exists: <strong>$email</strong>";
    exit();
}

// Insert super admin
$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password, role, is_super_admin, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$success = $stmt->execute([$username, $email, $hashedPassword, $role, $isSuperAdmin]);

if ($success) {
    echo "✅ Super admin account created successfully!<br>";
    echo "📧 Email: <strong>$email</strong><br>";
    echo "🔐 Password: <strong>$password</strong><br>";
} else {
    echo "❌ Failed to create the super admin account.";
}
