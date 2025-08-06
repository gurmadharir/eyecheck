<?php
$role = 'patient';
session_name('eyecheck_patient');
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Registration</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/auth.css" />
</head>
<body>
  <div id="loadingOverlay">
    <div class="spinner"></div>
    <p>Sending email...</p>
  </div>

  <div class="auth-container">
    <div class="auth-card">
      <?php include '../partials/auth/auth-form-register.php'; ?>
    </div>
  </div>

  <!-- <div id="toast" class="toast"></div> -->

  <script src="../js/auth/register.js"></script>
</body>
</html>
