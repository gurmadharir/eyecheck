<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin');

$page = 'patients';
$isSpecialAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1; // 🔹 for toggle perms
$currentUserId  = (int)($_SESSION['user_id'] ?? 0);                                      // 🔹 used by JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patients | Admin</title>

  <!-- Theme bootstrap CSS -->
  <script src="../../js/theme-init.js"></script>

  <link rel="stylesheet" href="../../css/records.css" />
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

    <div class="reports-wrapper">
      <div class="table-card">
        <div class="table-controls">
          <h3 id="pageTitle">Patients</h3>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="reportSearch" placeholder="Search..." />
            <div class="filter-wrapper">
              <button class="filter-btn"><i class="fas fa-filter"></i></button>
              <div class="filter-dropdown-tooltip" id="filterDropdown">
                <label for="dateFilter"><i class="fas fa-calendar-alt"></i> Sort:</label>
                <select id="dateFilter">
                  <option value="latest">Latest First</option>
                  <option value="oldest">Oldest First</option>
                </select>

                <label for="statusFilter"><i class="fas fa-user-check"></i> Status:</label>
                <select id="statusFilter">
                  <option value="all" selected>All</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>

                <label><i class="fas fa-calendar"></i> Date Range:</label>
                <input type="date" id="startDate" />
                <input type="date" id="endDate" />
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width: 5%">#</th>
                <th style="width: 30%">Name</th>
                <th style="width: 30%">Email</th>
                <th style="width: 20%">Registered</th>
                <th style="width: 15%">Actions</th>
              </tr>
            </thead>
            <tbody id="records-table-body"></tbody>
          </table>
        </div>

        <div id="pagination" class="pagination" style="margin-top: 20px; text-align: center;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Delete/Status Modal (shared) -->
<div class="delete-modal-overlay">
  <div class="delete-modal">
    <div class="delete-icon"><i class="fas fa-trash-alt"></i></div>
    <h2>Confirm Deletion</h2>
    <p>This patient will be removed permanently.</p>
    <div class="buttons">
      <button class="cancel-btn">Cancel</button>
      <button class="delete-btn delete-confirm-btn">Delete</button>
    </div>
  </div>
</div>

<!-- 🔹 Hidden flags for JS (needed for activate/deactivate button visibility & “self” rule) -->
<input type="hidden" id="currentAdminId" value="<?= $currentUserId ?>">
<input type="hidden" id="isSpecialAdmin" value="<?= $isSpecialAdmin ? '1' : '0' ?>">

<!-- Toast -->
<div id="toast" class="toast">
  <i class="fas fa-info-circle"></i>
  <span id="toastMessage">Action complete!</span>
</div>

<script src="../../js/sidebar-toggle.js" defer></script>
<script src="../../js/theme-toggle.js"></script>
<script src="../../js/toggle-dropdown.js"></script>

<script>
  window.adminRecordsConfig = {
    apiUrl: "/eyecheck/backend/admin/get-patients.php",
    searchId: "reportSearch",
    sortId: "dateFilter",
    statusId: "statusFilter",        // 🔹 explicitly pass status control id
    startDateId: "startDate",
    endDateId: "endDate",
    tableBodyId: "records-table-body",
    paginationId: "pagination",
    showViewButton: true,
    viewBasePath: "view.php"
  };
</script>

<!-- Your unified patients manage script (with activate/deactivate logic) -->
<script src="../../js/admin/manage.js"></script>
<script src="../../js/admin/user-alerts.js"></script>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); backdrop-filter: blur(3px); z-index: 9999; align-items:center; justify-content:center;">
  <div class="spinner" style="width: 60px; height: 60px; border: 6px solid #ccc; border-top-color: #00c9a7; border-radius: 50%; animation: spin 1s linear infinite;"></div>
</div>

<style>
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
</body>
</html>
