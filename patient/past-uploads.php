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

  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/records.css" />
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../partials/topbar.php'; ?>

    <div class="reports-wrapper">
      <div class="table-card">
        <!-- Header row -->
        <div class="table-controls">
          <h3>Past Uploads</h3>
          <div class="controls-right">
            <div class="filter-wrapper">
              <button class="filter-btn" aria-haspopup="true" aria-expanded="false" aria-controls="filterDropdown" title="Filter">
                <i class="fa fa-filter"></i>
              </button>
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

        <div class="table-responsive">
          <table class="uploads-table">
            <colgroup>
              <col class="col-idx">
              <col class="col-image">
              <col class="col-result">
              <col class="col-date">
              <col class="col-actions">
            </colgroup>

            <thead>
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Result</th>
                <th>Uploaded</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody id="records-table-body">
              <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="pagination" style="margin-top: 20px;"></div>
      </div>
    </div>
  </div>
</div>

<?php include '../partials/delete-toast.php'; ?>

<!-- JS -->
<script src="../js/delete-toast.js"></script>
<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>
<script src="../js/patient-uploads.js"></script>

<script>
// Click-to-toggle filter dropdown (mobile-safe)
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
