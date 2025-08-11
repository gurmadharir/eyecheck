<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="css/global.css" />
  <link rel="stylesheet" href="css/auth.css" />
</head>
<body>
  <div id="loadingOverlay">
    <div class="spinner"></div>
    <p>Sending reset link...</p>
  </div>

  <div class="auth-container">
    <div class="auth-card">
      <?php include 'partials/auth/forgot-password-form.php'; ?>
    </div>
  </div>

  <script src="js/auth/forgot-password.js"></script>
</body>
</html>
