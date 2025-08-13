<?php
require_once '../backend/helpers/auth-check.php';
requireRole('healthcare');
$role = 'healthcare';
$page = 'patients';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patients | Healthcare</title>

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

        <!-- controls -->
        <div class="table-controls">
          <h3>Patients</h3>
          <div class="controls-right">
            <input id="reportSearch" type="text" placeholder="Search..."
                   style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;font-size:.95rem;" />

            <div class="filter-wrapper">
              <button class="filter-btn" aria-haspopup="true" aria-expanded="false" aria-controls="filterDropdown" title="Filter">
                <i class="fas fa-filter"></i>
              </button>

              <div class="filter-dropdown-tooltip" id="filterDropdown">
                <label for="dateFilter"><i class="fas fa-calendar-alt"></i> Sort by Date:</label>
                <select id="dateFilter">
                  <option value="latest">Latest First</option>
                  <option value="oldest">Oldest First</option>
                </select>

                <label for="genderFilter"><i class="fas fa-venus-mars"></i> Gender:</label>
                <select id="genderFilter">
                  <option value="all">All</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>

                <label for="resultFilter"><i class="fas fa-stethoscope"></i> Result:</label>
                <select id="resultFilter">
                  <option value="all">All</option>
                  <option value="Conjunctivitis">Conjunctivitis</option>
                  <option value="Negative">Negative</option>
                </select>

                <label for="regionFilter"><i class="fas fa-map-marker-alt"></i> Region:</label>
                <select id="regionFilter"></select>

                <label for="ageFilter"><i class="fas fa-birthday-cake"></i> Age Range:</label>
                <select id="ageFilter">
                  <option value="all">All</option>
                  <option value="below20">Under 20</option>
                  <option value="20to40">Between 20 - 40</option>
                  <option value="above40">Above 40</option>
                </select>

                <label><i class="fas fa-calendar-day"></i> From:</label>
                <input type="date" id="startDate" />

                <label><i class="fas fa-calendar-day"></i> To:</label>
                <input type="date" id="endDate" />
              </div>
            </div>
          </div>
        </div>

        <!-- table -->
        <div class="table-responsive">
          <table class="patients-table">
            <colgroup>
              <col class="col-idx">
              <col class="col-name">
              <col class="col-image">
              <col class="col-contact">
              <col class="col-town">
              <col class="col-region">
              <col class="col-gender">
              <col class="col-dob">
              <col class="col-result">
              <col class="col-actions">
            </colgroup>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Image</th>
                <th>Contact</th>
                <th>Town</th>
                <th>Region</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Result</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="records-table-body">
              <tr><td colspan="10" style="text-align:center;">Loading...</td></tr>
            </tbody>
          </table>
        </div>

        <div id="pagination" class="pagination" style="margin-top:20px;text-align:center;"></div>
      </div>
    </div>
  </div>
</div>

<?php include '../partials/delete-toast.php'; ?>
<script src="../js/delete-toast.js"></script>
<script src="../js/theme-toggle.js" defer></script>
<script src="../js/sidebar-toggle.js" defer></script>
<script src="../js/patients.js"></script>

<script src="../js/toggle-dropdown.js"></script>

</body>
</html>
