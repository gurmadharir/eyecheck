<?php
require_once '../backend/helpers/auth-check.php';
requireRole('admin'); 

$page = 'manage';
$isSpecialAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Staff | Admin</title>

  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="../css/records.css" />
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
    <br><br>

    <?php if ($isSpecialAdmin): ?>
    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
      <a href="form.php" class="btn-primary" style="padding: 10px 18px; font-size: 0.9rem; text-decoration:none;">
        <i class="fas fa-user-plus"></i> Create
      </a>
    </div>
    <?php endif; ?>

    <div class="reports-wrapper">
      <div class="table-card">
        <div class="table-controls">
          <h3 id="pageTitle">Admins</h3>
          <div class="controls-right" style="display:flex;gap:10px;align-items:center;">
            <input type="text" id="reportSearch" placeholder="Search..." style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 0.95rem;" />
            <div class="filter-wrapper">
              <button class="filter-btn" aria-haspopup="true" aria-expanded="false" aria-controls="filterDropdown" title="Filter">
                <i class="fas fa-filter"></i>
              </button>
              <div class="filter-dropdown-tooltip" id="filterDropdown">
                <label for="roleFilter"><i class="fas fa-user-tag"></i> Role:</label>
                <select id="roleFilter">
                  <?php if ($isSpecialAdmin): ?>
                    <option value="admin" selected>Admin</option>
                  <?php endif; ?>
                  <option value="healthcare" <?= !$isSpecialAdmin ? 'selected' : '' ?>>Healthcare</option>
                </select>

                <label for="dateFilter"><i class="fas fa-calendar-alt"></i> Sort by Date:</label>
                <select id="dateFilter">
                  <option value="latest">Latest First</option>
                  <option value="oldest">Oldest First</option>
                </select>

                <div id="regionFilterWrapper" style="display:none;">
                  <label for="regionFilter"><i class="fas fa-map-marker-alt"></i> Region:</label>
                  <select id="regionFilter">
                    <option value="all">All</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Make table responsive like in patient uploads -->
        <div class="table-responsive">
          <table>
            <thead id="table-header"></thead>
            <tbody id="records-table-body"></tbody>
          </table>
        </div>

        <div id="pagination" class="pagination" style="margin-top: 20px; text-align: center;"></div>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="isSpecialAdmin" value="<?= $isSpecialAdmin ? '1' : '0' ?>">

<?php include '../partials/delete-toast.php'; ?>
<script src="../js/delete-toast.js"></script>
<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>
<script src="../js/admin/manage.js"></script>

<script>
// Mobile-friendly filter dropdown toggle
document.addEventListener("DOMContentLoaded", () => {
  const filterBtn = document.querySelector(".filter-btn");
  const dropdown = document.getElementById("filterDropdown");

  filterBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdown.classList.toggle("show");
    const expanded = filterBtn.getAttribute("aria-expanded") === "true";
    filterBtn.setAttribute("aria-expanded", String(!expanded));
  });

  document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
      dropdown.classList.remove("show");
      filterBtn.setAttribute("aria-expanded", "false");
    }
  });
});
</script>

</body>
</html>
