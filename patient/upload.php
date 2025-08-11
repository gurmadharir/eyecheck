<?php
require_once '../backend/helpers/auth-check.php';

// Ensure only patients can access this file
if ($_SESSION['role'] !== 'patient') {
  header("Location: ../unauthorized.php");
  exit;
}

require_once '../config/db.php';
require_once '../backend/helpers/load-patient-profile.php';

$role = $_SESSION['role'] ?? 'patient';
$page = 'upload';

// Fetch patient profile
$info = getPatientProfile($_SESSION['user_id']);

// Extract fields
$readonly = $info['readonly'] ?? false;
$existing_name = $info['name'] ?? '';
$existing_contact = $info['contact'] ?? '';
$existing_home_town = $info['town'] ?? '';
$existing_gender = $info['gender'] ?? '';
$existing_dob = $info['dob'] ?? '';
$existing_region = $info['region'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Upload | Patient</title>

  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="../css/upload.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/global.css" />
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../partials/topbar.php'; ?>
    <?php include '../partials/custom-toaster.php'; ?>

    <?php include '../partials/upload-form.php'; ?>
  </div>
</div>

<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>

<script src="../js/upload.js"></script>
</body>
</html>
