<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('healthcare');

require_once '../../config/db.php';

$role = $_SESSION['role'] ?? 'healthcare';
$page = 'profile';

$editingUserId = $_SESSION['user_id'];

// Fetch current user's data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$editingUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ❗ Redirect if user not found or role mismatch
if (!$user || $user['role'] !== 'healthcare') {
    header("Location: ../healthcare/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Update Profile | Healthcare</title>
  <link rel="stylesheet" href="../../css/global.css" />
  <link rel="stylesheet" href="../../css/theme.css" />
  <link rel="stylesheet" href="../../css/profile.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
<script src="../../js/theme.js"></script>
</body>
</html>
