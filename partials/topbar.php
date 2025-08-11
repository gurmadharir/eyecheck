<?php
// ✅ Start session securely
if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Role & name fallback
$role = $role ?? $_SESSION['role'] ?? 'guest';
$page = $page ?? '';
$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

$defaultImage = '/eyecheck/assets/images/user.webp';
$profileImage = $defaultImage;

// ✅ Resolve profile image securely
if (!empty($_SESSION['profile_image']) && in_array($role, ['admin', 'healthcare', 'patient'])) {
    $fileName = basename($_SESSION['profile_image']); // ⚠️ basename() to prevent path traversal
    $relativePath = "/eyecheck/{$role}/uploads/profile/{$fileName}";
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

    // ✅ Only use if image file really exists
    if (file_exists($absolutePath) && is_file($absolutePath)) {
        $profileImage = $relativePath;
    }
}
?>
<div style=" 
  display: flex;
  justify-content: <?= $page === 'dashboard' ? 'space-between' : 'flex-end' ?>;
  align-items: center;
  padding: 20px 30px;
">
  <?php if ($page === 'dashboard'): ?>
    <h1 style="color: #333; line-height: 1.2;">
      <span style="font-size: 0.8em;">Hello,</span><br>
      <span style="font-size: 1.5em; font-weight: bold;"><?= $fullName ?></span>
    </h1>
  <?php endif; ?>

  <div style="display: flex; align-items: center; gap: 20px;">
    <div class="theme-toggle">
      <label class="switch">
        <input type="checkbox" id="themeToggle">
        <span class="slider"><i class="icon">🌙</i></span>
      </label>
    </div>

    <div class="profile">
      <img src="<?= htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8') ?>" 
           alt="Profile" class="profile-pic"
           onerror="this.onerror=null; this.src='<?= $defaultImage ?>';" />

      <div class="profile-dropdown">
        <a href="/eyecheck/<?= $role ?>/profile/update.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
        <a href="/eyecheck/<?= $role ?>/profile/change-password.php"><i class="fas fa-key"></i> Change Password</a>
        <a href="/eyecheck/backend/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</div>
