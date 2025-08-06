<?php
require_once __DIR__ . '/../backend/shared/view-upload.php';

require_once __DIR__ . '/../backend/shared/diagnosis-utils.php';
$diagData = formatDiagnosis($data['diagnosis_result']);

$page = "past-uploads";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Upload | EyeCheck</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/theme.css" />
  <link rel="stylesheet" href="../css/view.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
    <?php include '../partials/sidebar.php'; ?>
    <div class="main-content">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h2 style="margin: 0;">Preview 👁️</h2>
        <?php include '../partials/topbar.php'; ?>
      </div>
      <hr style="width: 100%; height: 1px; border: none; margin: 16px 0; background-color: rgba(255, 255, 255, 0.15);" />

      <div class="image-view-container">
        <div class="image-wrapper">
        <img src="../<?= htmlspecialchars($data['image_path']) ?>"
             alt="Uploaded Eye Image"
             style="display: block; max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); border: none;" />
        </div>

        <div class="details">
          <p><strong>🔬 Diagnosis:</strong>
            <span style="<?= htmlspecialchars($diagData['style']) ?>"><?= htmlspecialchars($diagData['label']) ?></span>
          </p>

          <p><strong>📅 Uploaded:</strong> <?= date('F j, Y', strtotime($data['created_at'])) ?></p>
        </div>

        <button class="preview-btn" onclick="openReportModal()">📄 Preview Full Report</button>
      </div>
    </div>
  </div>

  <!-- ✅ Report Modal -->
  <?php if (isset($patient)) include '../partials/report-preview.php'; ?>

  <script src="../js/theme.js"></script>
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
