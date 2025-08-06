<?php
require_once '../backend/helpers/auth-check.php';
requireRole('patient');


$role = $_SESSION['role'] ?? 'patient';

$page = 'past-uploads';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Uploads | EyeCheck</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/records.css" />
  <link rel="stylesheet" href="../css/theme.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<div class="dashboard-wrapper">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">

    <?php include '../partials/topbar.php'; ?>

    <div class="reports-wrapper">
      <div class="table-card">
        <div class="table-controls">
          <h3>Past Uploads</h3>
          <div style="display: flex; gap: 10px; align-items: center;">
            <div class="filter-wrapper">
              <button class="filter-btn"><i class="fas fa-filter"></i></button>
              <div class="filter-dropdown-tooltip" id="filterDropdown">
                <label for="resultFilter"><i class="fas fa-stethoscope"></i> Result:</label>
                <select id="resultFilter">
                  <option value="all">All</option>
                  <option value="Conjunctivitis">Conjunctivitis</option>
                  <option value="Negative">Negative</option>
                </select>

                <label for="dateFilter"><i class="fas fa-calendar-alt"></i> Sort:</label>
                <select id="dateFilter">
                  <option value="latest">Latest First</option>
                  <option value="oldest">Oldest First</option>
                </select>

                <label for="startDate"><i class="fas fa-calendar-day"></i> From:</label>
                <input type="date" id="startDate" />

                <label for="endDate"><i class="fas fa-calendar-day"></i> To:</label>
                <input type="date" id="endDate" />
              </div>
            </div>
          </div>
        </div>

        <table>
          <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 20%;">Image</th>
            <th style="width: 20%;">Result</th>
            <th style="width: 15%;">Uploaded</th>
            <th style="width: 10%;">Action</th>
          </tr>
          </thead>
          <tbody id="records-table-body">
            <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
          </tbody>
        </table>

        <div id="pagination" class="pagination" style="margin-top: 20px;"></div>
      </div>
    </div>

  </div>
</div>

<?php include '../partials/delete-toast.php'; ?>
<script src="../js/delete-toast.js"></script>

<script src="../js/theme.js"></script>
<script src="../js/patient-uploads.js"></script>

<script>
// Toggle dropdown
document.addEventListener("DOMContentLoaded", () => {
  const filterBtn = document.querySelector(".filter-btn");
  const dropdown = document.getElementById("filterDropdown");

  filterBtn.addEventListener("click", e => {
    e.stopPropagation();
    dropdown.classList.toggle("show");
  });

  document.addEventListener("click", e => {
    if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
      dropdown.classList.remove("show");
    }
  });
});
</script>
</body>
</html>
