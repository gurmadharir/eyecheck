<?php
require_once '../config/db.php';
session_start();

function showPage($type, $message) {
    $title = $type === 'success' ? 'Verification Success' : 'Verification Failed';
    $icon = $type === 'success' ? '🎉 Verified!' : '⚠️ Verification Failed';
    $cssClass = $type === 'success' ? 'success' : 'error';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>$title</title>
  
  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
  <div class="verify-wrapper">
    <div class="card $cssClass">
      <h1>$icon</h1>
      <p>$message</p>
    </div>
  </div>
</body>
</html>
HTML;
}

// ✅ Validate token presence
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    showPage('error', 'Invalid verification link.');
    exit;
}

$token = trim($_GET['token']);

// ✅ Find user in pending_users
$stmt = $pdo->prepare("SELECT * FROM pending_users WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    showPage('error', 'Invalid or expired verification link.');
    exit;
}

// ✅ Check expiration (30 mins)
$createdAt = strtotime($user['created_at']);
if ((time() - $createdAt) > (30 * 60)) {
    $pdo->prepare("DELETE FROM pending_users WHERE token = ?")->execute([$token]);
    showPage('error', '⏰ This verification link has expired. Please register again.');
    exit;
}

// ✅ Move to users table
$insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password, created_at)
                         VALUES (?, ?, ?, ?, NOW())");

$insert->execute([
    $user['full_name'],
    $user['username'],
    $user['email'],
    $user['password']
]);

// ✅ Remove from pending_users
$pdo->prepare("DELETE FROM pending_users WHERE id = ?")->execute([$user['id']]);

// ✅ Final success page
showPage('success', '✅ Your email has been successfully verified! You can now log in to EyeCheck.');
