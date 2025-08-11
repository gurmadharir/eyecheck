<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Expect $page to be set in the parent file (e.g., $page = 'dashboard';)
$role = $_SESSION['role'] ?? '';
$page = $page ?? '';
?>

<!-- 🔔 Hamburger (outside the sidebar so it’s always visible on mobile) -->
<button id="sidebarToggle" class="sidebar-toggle" aria-label="Open menu" aria-expanded="false">
  <i class="fa-solid fa-bars"></i>
</button>

<!-- 🌫️ Backdrop (also outside so it covers the page when open) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- 📚 Sidebar -->
<aside class="sidebar" role="navigation" aria-label="Primary">
  <h2>EyeCheck</h2>

  <ul class="nav-links">
    <?php if ($role === 'admin'): ?>
      <li>
        <a href="/eyecheck/admin/dashboard.php"
           class="<?= ($page === 'dashboard' || $page === 'profile' || $page === 'create') ? 'active' : '' ?>">
           Dashboard
        </a>
      </li>
      <li>
        <a href="/eyecheck/admin/manage.php"
           class="<?= ($page === 'manage') ? 'active' : '' ?>">
           Management
        </a>
      </li>
      <li>
        <a href="/eyecheck/admin/patient/manage.php"
           class="<?= ($page === 'patients') ? 'active' : '' ?>">
           Patients
        </a>
      </li>
      <li>
        <a href="/eyecheck/admin/manage-user-alerts.php"
           class="<?= ($page === 'user-alerts') ? 'active' : '' ?>">
           User Alerts
        </a>
      </li>
      <li>
        <a href="/eyecheck/admin/manage-logs.php"
           class="<?= ($page === 'logs') ? 'active' : '' ?>">
           User Logs
        </a>
      </li>

    <?php elseif ($role === 'healthcare'): ?>
      <li>
        <a href="/eyecheck/healthcare/dashboard.php"
           class="<?= ($page === 'dashboard' || $page === 'profile') ? 'active' : '' ?>">
           Dashboard
        </a>
      </li>
      <li>
        <a href="/eyecheck/healthcare/patients/upload.php"
           class="<?= ($page === 'upload') ? 'active' : '' ?>">
           Upload
        </a>
      </li>
      <li>
        <a href="/eyecheck/healthcare/patients.php"
           class="<?= ($page === 'patients') ? 'active' : '' ?>">
           Patients
        </a>
      </li>

    <?php elseif ($role === 'patient'): ?>
      <li>
        <a href="/eyecheck/patient/dashboard.php"
           class="<?= ($page === 'dashboard' || $page === 'profile') ? 'active' : '' ?>">
           Dashboard
        </a>
      </li>
      <li>
        <a href="/eyecheck/patient/upload.php"
           class="<?= ($page === 'upload') ? 'active' : '' ?>">
           Upload
        </a>
      </li>
      <li>
        <a href="/eyecheck/patient/past-uploads.php"
           class="<?= ($page === 'past-uploads') ? 'active' : '' ?>">
           Past Uploads
        </a>
      </li>
    <?php endif; ?>
  </ul>

  <div class="version">v2.0</div>
</aside>
