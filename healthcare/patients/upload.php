<?php
require_once '../../backend/helpers/auth-check.php';

// Ensure only healthcare staff can access this page
if ($_SESSION['role'] !== 'healthcare') {
  header("Location: ../../unauthorized.php");
  exit;
}

require_once '../../config/db.php';

$role = 'healthcare';
$page = 'upload';

// Set all variables to blank since healthcare staff create new patients
$readonly = false;
$existing_name = '';
$existing_contact = '';
$existing_home_town = '';
$existing_gender = '';
$existing_dob = '';
$existing_region = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>New Patient | Healthcare</title>
  <link rel="stylesheet" href="../../css/global.css" />
  <link rel="stylesheet" href="../../css/upload.css" />
  <link rel="stylesheet" href="../../css/theme.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../../partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../../partials/topbar.php'; ?>
    <?php include '../../partials/custom-toaster.php'; ?>

    <?php include '../../partials/upload-form.php'; ?>
  </div>
</div>
<script src="../../js/theme.js"></script>
<script src="../../js/upload.js"></script>
</body>
</html>
