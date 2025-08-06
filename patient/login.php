<?php
require_once '../backend/helpers/guest-only.php'; 
$role = "patient";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Login</title>
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
