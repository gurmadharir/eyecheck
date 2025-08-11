<?php 
require_once '../backend/helpers/auth-check.php';
requireRole('admin'); 

$page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Dashboard | Admin</title>

  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <meta charset="UTF-8" />
  <link rel="stylesheet" href="../css/dashboard.css" />
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

    <?php include '../partials/dashboard/stats-cards.php'; ?>
    <?php include '../partials/dashboard/charts-wrapper.php'; ?>
  </div>
</div>

<script>const userRole = <?= json_encode($_SESSION['role']) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>
<script src="../js/dashboard/dashboard.js"></script>
</body>
</html>
