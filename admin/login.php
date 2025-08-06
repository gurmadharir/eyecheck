<?php
require_once '../backend/helpers/guest-only.php'; 
$role = 'admin';

if (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'admin') {
  header("Location: dashboard.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="robots" content="noindex">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/auth.css" />
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <?php include '../partials/auth/login-form.php'; ?>
    </div>
  </div>

  <script src="../js/auth/login.js"></script>
</body>
</html>
