<?php
session_name('eyecheck_healthcare');
session_start();
$role = 'healthcare';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/auth.css" />
  <link rel="stylesheet" href="../css/forgot.css" />
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <?php include '../partials/flash-message.php'; ?>
    <?php include '../partials/auth/forgot-password-form.php'; ?>
  </div>
</div>
</body>
</html>
