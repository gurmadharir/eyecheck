<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin');

$page = 'patients';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patients | Admin</title>
  <link rel="stylesheet" href="../../css/global.css" />
  <link rel="stylesheet" href="../../css/records.css" />
  <link rel="stylesheet" href="../../css/theme.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
                <label><i class="fas fa-calendar"></i> Date Range:</label>
                <input type="date" id="startDate" />
                <input type="date" id="endDate" />
              </div>
            </div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="records-table-body"></tbody>
        </table>

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
<div id="toast" class="toast">
  <i class="fas fa-info-circle"></i>
  <span id="toastMessage">Action complete!</span>
</div>


<script src="../../js/theme.js"></script>

<script>
  window.adminRecordsConfig = {
    apiUrl: "/eyecheck/backend/admin/get-patients.php",
    searchId: "reportSearch",
    sortId: "dateFilter",
    startDateId: "startDate",
    endDateId: "endDate",
    tableBodyId: "records-table-body",
    paginationId: "pagination",
    showViewButton: true,
    viewBasePath: "view.php" 

  };
</script>

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
