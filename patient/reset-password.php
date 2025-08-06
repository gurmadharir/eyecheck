<?php 
$role = 'patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/auth.css" />
</head>
<body>
<?php
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    echo '
    <div class="auth-container">
      <div class="card error">
        <h1>⚠️ Invalid reset link</h1>
        <p>Sorry, the link is invalid or has expired. Please try requesting a new one.</p>
      </div>
    </div>';
    exit;
}
?>
  <div class="auth-container">
    <div class="auth-card">
      <?php include '../partials/auth/reset-password-form.php'; ?>
    </div>
  </div>

  <?php include '../partials/custom-toaster.php'; ?> 
  <script src="../js/auth/reset-password.js"></script>
</body>
</html>
