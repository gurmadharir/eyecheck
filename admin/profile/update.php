<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin');

require_once '../../config/db.php';

$role = $_SESSION['role'];
$page = 'profile';

$editingUserId = $_SESSION['user_id'];

// Fetch current user's data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$editingUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ❗ Redirect if user not found or role mismatch
if (!$user || $user['role'] !== 'admin') {
    header("Location: ../admin/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Update Profile | Admin</title>
  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>
  
  <link rel="stylesheet" href="../../css/profile.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/global.css" />
</head>
<body>

<div class="dashboard-wrapper">
  <?php include '../../partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include '../../partials/topbar.php'; ?>

    <div class="edit-profile-wrapper">
      <?php include '../../partials/profile/edit-profile-form.php'; ?>
    </div>
  </div>
</div>

<script>const userRole = "<?php echo htmlspecialchars($role); ?>";</script>
<script src="../../js/theme-toggle.js"></script>
<script src="../../js/sidebar-toggle.js" defer></script>

</body>
</html>
