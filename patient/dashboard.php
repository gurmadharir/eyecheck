<?php 
require_once '../backend/helpers/auth-check.php';
requireRole('patient');

$page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Dashboard | Patient</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/theme.css" />
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<div class="dashboard-wrapper">
  <?php include '../partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include '../partials/topbar.php'; ?>
    <?php include '../partials/dashboard/stats-cards.php'; ?>
    <?php include '../partials/dashboard/charts-wrapper.php'; ?>
  </div>
</div>

<script>const userRole = <?= json_encode($_SESSION['role']) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/theme.js"></script>
<script src="../js/dashboard/dashboard.js"></script>
</body>
</html>
