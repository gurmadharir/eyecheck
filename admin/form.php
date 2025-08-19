<?php 
require_once '../backend/helpers/auth-check.php';
requireRole('admin'); 
require_once '../config/db.php';

$isEdit = isset($_GET['id']);
$staff = null;

if ($isEdit) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_GET['id']]);
  $staff = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$staff) die("Invalid user ID.");
}

$page = 'manage';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= $isEdit ? 'Edit Staff' : 'Add New Staff' ?> | Admin</title>
  
  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/global.css" />
  <style>
    .form-group { margin-bottom: 15px; }
    .hidden { display: none; }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../partials/topbar.php'; ?>

    <div class="reports-wrapper">
      <div class="table-card">
        <h3><?= $isEdit ? 'EDIT STAFF' : 'NEW STAFF' ?> | ADMIN</h3><br>

        <form class="staff-form" action="../backend/admin/form-handler.php" method="POST" style="max-width: 600px; margin-top: 20px;">
          
          <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($staff['id']) ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="role">Role:</label>
            <select name="role" id="role" required>
              <option value="">Select role</option>
              <option value="admin" <?= $staff && $staff['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="healthcare" <?= $staff && $staff['role'] === 'healthcare' ? 'selected' : '' ?>>Healthcare</option>
            </select>
          </div>

          <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input id="full_name" type="text" name="full_name" required value="<?= htmlspecialchars($staff['full_name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="username" class="d-flex align-items-center justify-content-between">
              <span>Username:</span>
              <?php if (!$isEdit): ?>
              <label class="form-check-label" style="font-weight: normal;">
                <input class="form-check-input" type="checkbox" id="auto-username" checked>
                Auto
              </label>
              <?php endif; ?>
            </label>

            <input id="username" type="text" name="username" required value="<?= htmlspecialchars($staff['username'] ?? '') ?>">
            <input type="hidden" name="username_mode" id="username_mode" value="<?= $isEdit ? 'manual' : 'auto' ?>">
            <small class="text-muted">Auto uses the first name (e.g., “Qudus” → qudus, qudus2, …). Uniqueness enforced on the server.</small>
          </div>

          <div class="form-group">
            <label for="email">Email:</label>
            <input id="email" type="email" name="email" required value="<?= htmlspecialchars($staff['email'] ?? '') ?>">
          </div>

          <div class="form-group <?= ($staff && $staff['role'] === 'healthcare') ? '' : 'hidden' ?>" id="region-group">
            <label for="region-select">Region:</label>
            <select name="region" id="region-select" <?= ($staff && $staff['role'] === 'healthcare') ? 'required' : '' ?>>
              <option value="">Select region</option>
              <?php
              $regions = ["Banadir", "Bay", "Bakool", "Gedo", "Hiiraan", "Middle Shabelle", "Lower Shabelle", "Middle Juba", "Lower Juba", "Galgaduud", "Mudug", "Nugal", "Bari"];
              foreach ($regions as $region) {
                $selected = ($staff && ($staff['healthcare_region'] ?? '') === $region) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($region).'" '.$selected.'>'.htmlspecialchars($region).'</option>';
              }
              ?>
            </select>
          </div>

          <?php if (!$isEdit): ?>
          <div class="form-group">
            <label for="password">Password:</label>
            <input id="password" type="password" name="password" required>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input id="confirm_password" type="password" name="confirm_password" required>
          </div>
          <?php endif; ?>
          
          <button 
            type="submit" 
            id="submitBtn" 
            class="btn-primary" 
            style="
              width: 100%; 
              font-weight: bold; 
              padding: 14px; 
              display: flex; 
              justify-content: center; 
              align-items: center; 
              text-align: center;"
              aria-label="<?= $isEdit ? 'Update staff account' : 'Create new staff account' ?>"
          >
            <?= $isEdit ? 'UPDATE' : 'CREATE' ?>
          </button>

        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../partials/custom-toaster.php'; ?>

<?php if (isset($_SESSION['message'])): ?>
  <div id="toast-data"
       data-message="<?= htmlspecialchars($_SESSION['message']) ?>"
       data-type="<?= htmlspecialchars($_SESSION['msg_type']) ?>">
  </div>
  <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>
<script src="../js/admin/form.js"></script>

</body>
</html>
