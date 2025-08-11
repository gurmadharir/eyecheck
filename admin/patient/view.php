<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin'); // Enforce only admin access

$page = 'patients';

require_once '../../config/db.php';

// --- Validate patient user id from query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  header("Location: manage.php");
  exit();
}

// --- Fetch patient general info
$stmt = $pdo->prepare("
  SELECT u.full_name, u.email, u.created_at,
         p.contact, p.town, p.dob, p.region,
         COALESCE(p.warnings_sent, 0) AS warnings_sent
  FROM users u
  JOIN patients p ON u.id = p.user_id
  WHERE u.id = ? AND u.role = 'patient'
  LIMIT 1
");
$stmt->execute([$id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
  header("Location: manage.php");
  exit();
}

function calculateAge($dob) {
  return $dob ? floor((time() - strtotime($dob)) / 31556926) : '-';
}

// --- Fetch patient eye image uploads
$imagesStmt = $pdo->prepare("
  SELECT image_path, diagnosis_result, created_at 
  FROM patient_uploads 
  WHERE patient_id = (SELECT id FROM patients WHERE user_id = ?) 
  ORDER BY created_at DESC
");
$imagesStmt->execute([$id]);
$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>View Patient | Admin</title>
  
  <!-- Theme bootstrap CSS -->
  <script src="../js/theme-init.js"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/global.css" />
  <style>
    .patient-info {
      background: var(--card-bg);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: var(--shadow);
    }
    .patient-info h2 {
      margin-top: 0;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-top: 10px;
    }
    .info-box {
      background: var(--card-bg-alt);
      padding: 10px 15px;
      border-radius: 8px;
    }
    .image-gallery {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      padding-bottom: 10px;
    }
    .image-card {
      flex: 0 0 auto;
      width: 240px;
      background: var(--card-bg);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }
    .image-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .image-caption {
      padding: 10px;
      font-size: 0.9rem;
      background: var(--card-bg-alt);
      text-align: center;
    }
    .back-btn {
      margin-bottom: 14px;
      display: inline-block;
      color: var(--primary);
      text-decoration: none;
      font-weight: bold;
    }
    .image-caption {
      text-align: left;  
      line-height: 1.6;
      padding: 8px 10px;  
    }
  </style>
</head>
<body>
<div class="dashboard-wrapper">
  <?php include '../../partials/sidebar.php'; ?>
  <div class="main-content">

    <div class="top-bar">
      <a href="manage.php" class="back-btn">
        <i class="fas fa-arrow-left" style="margin-right: 5px"></i> Back to the list
      </a>
    </div>

    <div class="patient-info">
      <h2><?= htmlspecialchars($patient['full_name']) ?> 
        <small style="font-weight: normal; font-size: 0.9em; color: gray;">
          (<?= htmlspecialchars($patient['email']) ?>)
        </small>
      </h2>
      <div class="info-grid">
        <div class="info-box"><strong>Contact:</strong> <?= $patient['contact'] ?? '-' ?></div>
        <div class="info-box"><strong>Age:</strong> <?= calculateAge($patient['dob']) ?> yrs</div>
        <div class="info-box"><strong>Region:</strong> <?= $patient['region'] ?? '-' ?></div>
        <div class="info-box"><strong>Town:</strong> <?= $patient['town'] ?? '-' ?></div>
        <div class="info-box"><strong>Registered:</strong> <?= date('F j, Y', strtotime($patient['created_at'])) ?></div>
        <?php
          $warns = (int) $patient['warnings_sent'];
          $warnColor = $warns >= 3 ? "color: red;" : "";
          $warnEmoji = $warns >= 3 ? " ⚠️" : "";
        ?>
        <div class="info-box" style="<?= $warnColor ?>">
          <strong>Warnings Sent:</strong> <?= $warns . $warnEmoji ?>
        </div>
      </div>
    </div>

    <h3 style="margin-bottom: 10px;">Uploaded Eye Images</h3>
    <div class="image-gallery">
      <?php if (count($images) > 0): ?>
        <?php foreach ($images as $img): ?>
          <?php if (!empty($img['image_path']) && file_exists('../../' . $img['image_path'])): ?>
            <div class="image-card">
              <img src="../../<?= $img['image_path'] ?>" alt="Eye Image" />
              <div class="image-caption">
                <strong>Result:</strong> <?= ucfirst($img['diagnosis_result']) ?><br>
                <small><?= date('M d, Y', strtotime($img['created_at'])) ?></small>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color: red;">No images found for this patient.</p>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="../../js/theme-toggle.js"></script>
<script src="../../js/sidebar-toggle.js" defer></script>

</body>
</html>
