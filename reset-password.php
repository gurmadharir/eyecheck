<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Reset Password</title>
  <link rel="stylesheet" href="css/global.css" />
  <link rel="stylesheet" href="css/auth.css" />
    <link rel="stylesheet" href="css/logout.css" />

</head>
<body>
<?php
// ✅ Only require token (NOT email)
if (!isset($_GET['token']) || strlen($_GET['token']) < 40) {
    echo '
    <div class="auth-container">
      <div class="card error">
        <h1>⚠️ Invalid reset link</h1>
        <p>Sorry, the link is invalid or has expired. Please try requesting a new one.</p>
      </div>
    </div>';
    exit;
}

// Make token available to the form partial
$resetToken = $_GET['token'];
?>
  <div class="auth-container">
    <div class="auth-card">
      <?php include 'partials/auth/reset-password-form.php'; ?>
    </div>
  </div>

  <?php include 'partials/custom-toaster.php'; ?> 
  <script src="js/auth/reset-password.js"></script>
</body>
</html>
