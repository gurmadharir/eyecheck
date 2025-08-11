<?php
require_once("../../backend/helpers/auth-check.php");
require_once("../../config/db.php");

if (!isset($_GET['id'])) {
  echo "Patient ID is missing.";
  exit();
}

$patientId = $_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;

$current = $pdo->prepare("SELECT id, name, contact, town, region, gender, dob FROM patients WHERE id = ? AND created_by = ?");
$current->execute([$patientId, $userId]);


$patient = $current->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
  echo "Patient not found.";
  exit();
}

$page = 'patients';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Patient | EyeCheck</title>
  
  <!-- Theme bootstrap CSS -->
  <script src="../../js/theme-init.js"></script>

  <link rel="stylesheet" href="../../css/edit-record.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/global.css" />
</head>
<body>
  <div class="dashboard-wrapper">
    <?php include_once("../../partials/sidebar.php"); ?>
    <div class="main-content">
      <?php include_once("../../partials/topbar.php"); ?>
      <?php include '../../partials/custom-toaster.php'; ?>

      <div class="edit-report-wrapper">
        <form class="edit-report-card" id="editPatientForm">
          <h2>Update Patient</h2>
          <input type="hidden" name="id" value="<?= htmlspecialchars($patient['id']) ?>" />

          <div class="form-group">
            <label>Patient Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required />
          </div>

          <div class="form-group">
            <label>Contact</label>
            <input type="text" name="contact" value="<?= htmlspecialchars($patient['contact']) ?>" required />
          </div>

          <div class="form-group">
            <label>Home Town</label>
            <input type="text" name="town" value="<?= htmlspecialchars($patient['town']) ?>" required />
          </div>

          <div class="form-group">
            <label>Region</label>
            <select name="region" required>
              <?php
              $regions = [
                "Banadir", "Bay", "Bakool", "Gedo", "Hiiraan", "Middle Shabelle", "Lower Shabelle",
                "Middle Juba", "Lower Juba", "Galgaduud", "Mudug", "Nugaal", "Bari"
              ];
              foreach ($regions as $region) {
                $selected = ($region === $patient['region']) ? 'selected' : '';
                echo "<option value=\"$region\" $selected>$region</option>";
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
              <option value="Female" <?= $patient['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Male" <?= $patient['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Other" <?= $patient['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($patient['dob']) ?>" required />
          </div>

          <div class="buttons">
            <button type="button" onclick="history.back()">Cancel</button>
            <button type="submit" class="save-btn">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../../js/theme-toggle.js"></script>
  <script src="../../js/sidebar-toggle.js" defer></script>


  <script>
    // ✅ Inline toast function (matches custom-toaster.php)
    function showToast(message, type = 'info', redirect = false) {
      const toast = document.getElementById('toast');
      if (!toast) return;

      toast.textContent = message;
      toast.style.backgroundColor = type === 'success' ? '#2ecc71' : '#e74c3c';
      toast.style.opacity = '1';
      toast.style.display = 'block';

      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
          toast.style.display = 'none';
          if (redirect) {
            window.location.href = '../patients.php'; // ✅ change this if path differs
          }
        }, 400);
      }, 3000);
    }



    // ✅ Handle form submit via fetch
    const form = document.getElementById('editPatientForm');
     form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(form);

      fetch('../../backend/patients/update-healthcare.php', {
        method: 'POST',
        body: new URLSearchParams(formData),
        credentials: 'include'
      })
      .then(res => res.json())
      .then(data => {
        showToast(data.message, data.success ? 'success' : 'error', data.success);
      })
      .catch(() => {
        showToast("Server error. Please try again.", 'error');
      });
    });
  </script>
</body>
</html>
