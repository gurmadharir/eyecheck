<?php
require_once '../backend/helpers/auth-check.php';
requireRole('admin');

$page = 'logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Logs | Admin</title>

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
          <h3 id="pageTitle">System Logs</h3>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="logSearch" placeholder="Search..." />

            <div class="filter-wrapper">
              <button class="filter-btn"><i class="fas fa-filter"></i></button>
              <div class="filter-dropdown-tooltip" id="filterDropdown">
                <label for="roleFilter"><i class="fas fa-user-tag"></i> Role:</label>
                <select id="roleFilter">
                  <option value="">All</option>
                  <option value="admin">Admin</option>
                  <option value="healthcare">Healthcare</option>
                  <option value="patient">Patient</option>
                </select>

                <div id="roleUsersList" class="role-users-list"></div>
                
                <label for="actionFilter"><i class="fas fa-bolt"></i> Action:</label>
                <select id="actionFilter">
                  <option value="">All</option>
                  <option value="LOGIN">Login</option>
                  <option value="LOGOUT">Logout</option>
                  <option value="UPLOAD_IMAGE">Upload</option>
                  <option value="DELETE_UPLOAD">Delete Upload</option>
                  <option value="SEND_WARNING">Send Warning</option>
                  <option value="UPDATE_PROFILE">Update Profile</option>
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
              <tr>
                <th style="width: 5%">#</th>
                <th style="width: 15%">Timestamp</th>
                <th style="width: 15%">User</th>
                <th style="width: 10%">Role</th>
                <th style="width: 20%">Action</th>
                <th style="width: 10%">Target</th>
                <th style="width: 15%">Description</th>
                <th style="width: 15%">IP</th>
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

<!-- 🔍 Modal for user-specific logs -->
<div id="userLogsModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeUserLogs">&times;</span>
    <h3 id="userLogsTitle">User Logs</h3>
    <br>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 15%;">Timestamp</th>
            <th style="width: 15%;">Action</th>
            <th style="width: 35%;">Description</th>
            <th style="width: 10%;">IP</th>
          </tr>
        </thead>
        <tbody id="userLogsBody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast" class="toast success">
  <i class="fas fa-circle-check"></i>
  <span id="toastMessage">Action completed!</span>
</div>

<script src="../js/toggle-dropdown.js"></script>
<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>

<script>
  window.adminLogsConfig = {
    apiUrl: "/eyecheck/backend/admin/get-logs.php",
    searchId: "logSearch",
    sortId: "dateSort",
    filterId: ["roleFilter", "actionFilter"], // multiple filters
    tableBodyId: "records-table-body",
    paginationId: "pagination",
    roleUsersListId: "roleUsersList"
  };
</script>
<script src="../js/admin/logs.js"></script>

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
