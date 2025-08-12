<?php
require_once '../backend/helpers/auth-check.php';
requireRole('admin');

$page = 'user-alerts';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage user - Alerts | Admin</title>

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

    <div class="reports-wrapper">
      <div class="table-card">
        <div class="table-controls">
          <h3 id="pageTitle">Alerts</h3>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="reportSearch" placeholder="Search..." />
            <div class="filter-wrapper">
                <button class="filter-btn"><i class="fas fa-filter"></i></button>
                <div class="filter-dropdown-tooltip" id="filterDropdown">
                    <label for="statusFilter"><i class="fas fa-filter"></i> Filter:</label>
                    <select id="statusFilter">
                    <option value="pending" selected>Pending Users</option>
                    <option value="flagged">Flagged Patients</option>
                    </select>
                    <label for="dateSort"><i class="fas fa-calendar-alt"></i> Sort:</label>
                    <select id="dateSort">
                    <option value="latest">Latest First</option>
                    <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead id="tableHeadRow">
            </thead>
            <tbody id="records-table-body"></tbody>
          </table>
        </div>

        <div id="pagination" class="pagination" style="margin-top: 20px; text-align: center;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
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

<!-- Toast -->
<div id="toast" class="toast danger">
  <i class="fas fa-trash-alt"></i>
  <span id="toastMessage">Deleted successfully!</span>
</div>

<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>

<script>
  window.adminRecordsConfig = {
    apiUrl: "/eyecheck/backend/admin/get-user-alerts.php",
    searchId: "reportSearch",
    sortId: "dateSort",
    filterId: "statusFilter",
    tableBodyId: "records-table-body",
    paginationId: "pagination",
    showViewButton: false,
  };
</script>
<script src="../js/admin/user-alerts.js"></script>

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
