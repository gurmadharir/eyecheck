<?php
require_once __DIR__ . '/../../backend/shared/view-upload.php';

$page = "patients";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Upload | EyeCheck</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

   <!-- Theme bootstrap CSS -->
  <script src="../../js/theme-init.js"></script>

  <link rel="stylesheet" href="../../css/view.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/global.css" />
  <style>
    @media print {
      .top-bar, .sidebar, .welcome-msg, .print-btn, .preview-btn {
        display: none !important;
      }

      body {
        margin: 0;
        padding: 0;
        background: white !important;
      }

      .dashboard-wrapper {
        padding: 0 !important;
        box-shadow: none;
      }

      @page {
        margin: 0;
        size: auto;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-wrapper">
    <?php include '../../partials/sidebar.php'; ?>
    <div class="main-content">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h2 style="margin: 0;">Preview 👁️</h2>
        <?php include '../../partials/topbar.php'; ?>
      </div>
      <hr style="width: 100%; height: 1px; border: none; margin: 16px 0; background-color: rgba(255, 255, 255, 0.15);" />

      <div class="image-view-container">
        <div class="image-wrapper">
          <img src="/eyecheck/<?= htmlspecialchars($data['image_path']) ?>" alt="Uploaded Eye Image" />
        </div>

       <div class="details">
          <?php
            $diagnosis = strtolower($data['diagnosis_result']);
            $diagClass = $diagnosis === 'conjunctivitis' ? 'positive' : ($diagnosis === 'negative' ? 'negative' : '');
            $dob = new DateTime($data['dob']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
          ?>
          <p><strong>🧑 Patient:</strong> <?= htmlspecialchars($data['name']) ?></p>
          <p><strong>🎂 Age:</strong>  <?= $age ." years." ?></p>
          <p><strong>🔬 Diagnosis:</strong>
            <span class="<?= $diagClass ?>"><?= ucfirst($data['diagnosis_result']) ?></span>
          </p>
          <p><strong>📅 Uploaded:</strong> <?= date('F j, Y', strtotime($data['created_at'])) ?></p>
       </div>



        <button class="preview-btn" onclick="openReportModal()">📄 Preview Full Report</button>
      </div>
    </div>
  </div>

  <!-- ✅ Report Modal -->
  <?php if (isset($patient)) include '../../partials/report-preview.php'; ?>

  <script src="../../js/theme-toggle.js"></script>
  <script src="../../js/sidebar-toggle.js" defer></script>
  
  <script>
    function openReportModal() {
      const modal = document.getElementById('reportModal');
      if (modal) modal.style.display = 'flex';
    }

    function closeReportModal() {
      const modal = document.getElementById('reportModal');
      if (modal) modal.style.display = 'none';
    }
  </script>
</body>
</html>
