<?php session_start(); $role = 'healthcare'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/auth.css" />
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <?php include '../partials/flash-message.php'; ?>
      <?php include '../partials/auth/reset-password-form.php'; ?>
    </div>
  </div>

  <script src="../js/auth/reset-password.js"></script>
</body>
</html>
