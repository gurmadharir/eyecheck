<?php
session_name('eyecheck_admin');
session_start();
$role = 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="../../css/global.css" />
  <link rel="stylesheet" href="../../css/auth.css" />
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <?php include '../../partials/flash-message.php'; ?>
    <?php include '../../partials/auth/reset-password-form.php'; ?>
  </div>
</div>
</body>
</html>
