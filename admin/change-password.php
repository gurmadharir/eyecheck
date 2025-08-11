<?php
require_once '../backend/helpers/auth-check.php';

requireRole('admin');
$page = 'manage';

// Get the target user ID (to be edited)
$targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Block access if no valid target ID is provided or trying to update self
if (!$targetId || $targetId === $_SESSION['user_id']) {
    header("Location: manage.php");
    exit;
}

// ✅ Force the form to use this ID
$_GET['id'] = $targetId;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Change Password | EyeCheck</title>

  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="../css/profile.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/global.css" />
  <style>
    .password-icon {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      width: 100%;
      color: var(--primary-color);
    }
    .illustration {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px 0;
    }
  </style>
</head>
<body>
<div class="dashboard-wrapper">
  <?php include_once("../partials/sidebar.php"); ?>

  <div class="main-content">
    <?php include_once("../partials/topbar.php"); ?>

    <?php
    $editingUserId = $targetId;
    include_once("../partials/profile/change-password-form.php");
    ?>
  </div>
</div>

<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>

</body>
</html>
