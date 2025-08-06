<?php
session_start();
$role = 'admin';
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
    <?php include '../partials/auth/forgot-password-form.php'; ?>
  </div>
</div>
</body>
</html>
