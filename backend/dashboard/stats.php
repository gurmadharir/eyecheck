<?php
// --- Error reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Detect role from session cookie ---
if (isset($_COOKIE['eyecheck_admin'])) {
  session_name("eyecheck_admin");
  $detectedRole = 'admin';
} elseif (isset($_COOKIE['eyecheck_healthcare'])) {
  session_name("eyecheck_healthcare");
  $detectedRole = 'healthcare';
} elseif (isset($_COOKIE['eyecheck_patient'])) {
  session_name("eyecheck_patient");
  $detectedRole = 'patient';
} else {
  http_response_code(403);
  echo json_encode(['error' => 'No valid session cookie']);
  exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

require_once('../../config/db.php');
header('Content-Type: application/json');

if ($_SESSION['role'] !== $detectedRole) {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized or mismatched role']);
  exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

/**
 * Small helper to return an inline style for traffic-light coloring.
 * Accepts a numeric percentage (0-100 or null).
 */
function style_for_confidence($val) {
  if ($val === null || $val === '') return '';
  $v = (float)$val;
  if ($v >= 80) return 'color:#27ae60;font-weight:bold;'; // green
  if ($v >= 50) return 'color:#f1c40f;font-weight:bold;'; // yellow
  return 'color:#e74c3c;font-weight:bold;';                 // red
}

try {
  if ($role === 'admin' || $role === 'healthcare') {
    $params = [];
    if ($role === 'admin') {
      $userFilter = '';
    } else {
      $region = $_SESSION['region'] ?? '';
      $userFilter = 'WHERE p.created_by = :created_by OR p.region = :region';
      $params = ['created_by' => $user_id, 'region' => $region];
    }

    // === Role-scoped Average Confidence ===
    if ($role === 'admin') {
      $avgConfidence = $pdo->query("
        SELECT ROUND(AVG(confidence), 1)
        FROM patient_uploads
        WHERE confidence IS NOT NULL
      ")->fetchColumn();
    } else {
      // healthcare: respect $userFilter scope
      $stmtConf = $pdo->prepare("
        SELECT ROUND(AVG(confidence), 1)
        FROM patient_uploads pu
        JOIN patients p ON pu.patient_id = p.id
        $userFilter
        AND pu.confidence IS NOT NULL
      ");
      $stmtConf->execute($params);
      $avgConfidence = $stmtConf->fetchColumn();
    }
    $avgConfidence = ($avgConfidence !== null) ? (float)$avgConfidence : null;
    $avgConfidenceStyle = style_for_confidence($avgConfidence);

    // === Detection results chart ===
    $stmt = $pdo->prepare("
      SELECT pu.diagnosis_result, COUNT(*)
      FROM patient_uploads pu
      JOIN patients p ON pu.patient_id = p.id
      $userFilter
      GROUP BY pu.diagnosis_result
    ");
    $stmt->execute($params);
    $map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $detectionResults = [(int)($map['Conjunctivitis'] ?? 0), (int)($map['NonConjunctivitis'] ?? 0)];

    // === Gender distribution chart ===
    $stmt = $pdo->prepare("
      SELECT p.gender, COUNT(*)
      FROM patients p
      $userFilter
      GROUP BY p.gender
    ");
    $stmt->execute($params);
    $map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $genderDistribution = [(int)($map['Female'] ?? 0), (int)($map['Male'] ?? 0)];

    // === Age groups chart ===
    $stmt = $pdo->prepare("
      SELECT CASE 
        WHEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 0 AND 9  THEN '0-9'
        WHEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 10 AND 19 THEN '10-19'
        WHEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 20 AND 29 THEN '20-29'
        WHEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 30 AND 39 THEN '30-39'
        WHEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 40 AND 49 THEN '40-49'
        ELSE '50+'
      END as age_group, COUNT(*) as count
      FROM patients p
      $userFilter
      GROUP BY age_group
    ");
    $stmt->execute($params);
    $ageGroups = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $ageGroups[$row['age_group']] = (int)$row['count'];
    }

    // === Trend chart ===
    $stmt = $pdo->prepare("
      SELECT DATE(pu.created_at) as date, 
             SUM(pu.diagnosis_result = 'Conjunctivitis')    as conjunctivitis, 
             SUM(pu.diagnosis_result = 'NonConjunctivitis') as non_conjunctivitis 
      FROM patient_uploads pu 
      JOIN patients p ON pu.patient_id = p.id 
      $userFilter
      GROUP BY DATE(pu.created_at)
      ORDER BY date ASC
    ");
    $stmt->execute($params);
    $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === Region trend chart ===
    $regionTrend = [];
    $regionSharedTrend = [];
    $monthlyUploadTrend = [];

    if ($role === 'admin') {
      // Detailed region+healthcare trend (admin only)
      $stmt = $pdo->query("
        SELECT DATE(pu.created_at) AS date,
               u.full_name AS healthcare,
               p.region,
               COUNT(*) as count 
        FROM patient_uploads pu 
        JOIN patients p ON pu.patient_id = p.id 
        JOIN users u ON pu.uploaded_by = u.id 
        WHERE u.role = 'healthcare' 
        GROUP BY DATE(pu.created_at), u.full_name, p.region 
        ORDER BY date ASC
      ");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $map = [];
      $labels = [];
      foreach ($rows as $r) {
        $label = "{$r['healthcare']} ({$r['region']})";
        $labels[$label] = true;
        $map[$r['date']][$label] = $r['count'];
      }
      foreach ($map as $date => $counts) {
        $entry = ['date' => $date];
        foreach (array_keys($labels) as $label) {
          $entry[$label] = $counts[$label] ?? 0;
        }
        $regionTrend[] = $entry;
      }

      // Shared Region Trend (patients only)
      $stmt = $pdo->query("
        SELECT DATE(pu.created_at) AS date, p.region, COUNT(*) as count 
        FROM patient_uploads pu 
        JOIN patients p ON pu.patient_id = p.id 
        GROUP BY DATE(pu.created_at), p.region 
        ORDER BY date ASC
      ");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      // Healthcare region trend
      $stmt = $pdo->prepare("
        SELECT DATE(pu.created_at) AS date, p.region, COUNT(*) as count 
        FROM patient_uploads pu 
        JOIN patients p ON pu.patient_id = p.id 
        $userFilter 
        GROUP BY DATE(pu.created_at), p.region 
        ORDER BY date ASC
      ");
      $stmt->execute($params);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Build shared region trend (both roles use this)
    $map = [];
    $regions = [];
    foreach ($rows as $r) {
      $regions[$r['region']] = true;
      $map[$r['date']][$r['region']] = $r['count'];
    }
    foreach ($map as $date => $counts) {
      $entry = ['date' => $date];
      foreach (array_keys($regions) as $regionName) {
        $entry[$regionName] = $counts[$regionName] ?? 0;
      }
      $regionSharedTrend[] = $entry;
    }

    // === Monthly Upload Trend / Weekly Uploads ===
    if ($role === 'admin') {
      $stmt = $pdo->query("
        SELECT 
          DATE_FORMAT(pu.created_at, '%Y-%m') AS month,
          SUM(CASE WHEN u.role = 'healthcare' THEN 1 ELSE 0 END) AS healthcare_uploads,
          SUM(CASE WHEN u.role = 'patient' THEN 1 ELSE 0 END)     AS patient_uploads
        FROM patient_uploads pu
        JOIN users u ON pu.uploaded_by = u.id
        GROUP BY month
        ORDER BY month ASC
      ");
      $monthlyUploadTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      // Weekly for Healthcare
      $weeklyUploadTrend = [];
      $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(pu.created_at, '%Y-%u') AS week,
               COUNT(*) as uploads
        FROM patient_uploads pu
        JOIN patients p ON pu.patient_id = p.id
        $userFilter
        GROUP BY week
        ORDER BY week ASC
      ");
      $stmt->execute($params);
      $weeklyUploadTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // === Admin-only Cards ===
    $adminCards = [];
    $mostActiveHealthcare = '';
    $regionUserBreakdown = [];
    $workerSummary = [];

    if ($role === 'admin') {
      $adminCards['total_patients']   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
      $adminCards['total_healthcare'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'healthcare'")->fetchColumn();
      $adminCards['regions_count']    = (int) $pdo->query("SELECT COUNT(DISTINCT region) FROM patients")->fetchColumn();
      // Your original model_accuracy:
      $adminCards['model_accuracy']   = round((float) $pdo->query("SELECT AVG(confidence) FROM patient_uploads WHERE confidence IS NOT NULL")->fetchColumn(), 1);
      // Also expose avg_confidence with style (non-breaking extra keys)
      $adminCards['avg_confidence']        = $avgConfidence;
      $adminCards['avg_confidence_style']  = $avgConfidenceStyle;

      $adminCards['last_upload'] = $pdo->query("SELECT MAX(created_at) FROM patient_uploads")->fetchColumn() ?? '-';

      // Step 1: Get patient_ids with 3+ Conjunctivitis (linked to real patient users)
      $stmt = $pdo->query("
        SELECT pu.patient_id
        FROM patient_uploads pu
        JOIN patients p ON pu.patient_id = p.id
        WHERE pu.diagnosis_result = 'Conjunctivitis' AND p.user_id IS NOT NULL
        GROUP BY pu.patient_id
        HAVING COUNT(*) >= 3
      ");
      $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

      // Step 2: Store in dashboard
      $adminCards['repeated_positive_count'] = count($ids);
      $adminCards['repeated_positive_ids']   = $ids;

      // Step 3: Flag them
      $update = $pdo->prepare("UPDATE patients SET flagged = 1, flagged_at = NOW() WHERE id = ?");
      foreach ($ids as $pid) {
        $update->execute([$pid]);
      }

      // Step 4: Unflag others (if needed)
      if (count($ids) > 0) {
        $pdo->query("
          UPDATE patients
          SET flagged = 0, flagged_at = NULL
          WHERE user_id IS NOT NULL
            AND id NOT IN (" . implode(',', array_map('intval', $ids)) . ")
        ");
      }

      $stmt = $pdo->query("
        SELECT region, 
               COUNT(DISTINCT CASE WHEN role = 'patient' THEN id END)     AS patients,
               COUNT(DISTINCT CASE WHEN role = 'healthcare' THEN id END)  AS healthcare
        FROM (
          SELECT u.id, u.role, p.region
          FROM users u
          LEFT JOIN patients p ON u.id = p.user_id

          UNION ALL

          SELECT u.id, u.role, u.healthcare_region AS region
          FROM users u
          WHERE u.role = 'healthcare'
        ) AS combined
        WHERE region IS NOT NULL
        GROUP BY region
      ");
      $regionUserBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt = $pdo->query("
        SELECT 
          u.full_name AS name,
          SUM(pu.diagnosis_result = 'Conjunctivitis')    AS conjunctivitis,
          SUM(pu.diagnosis_result = 'NonConjunctivitis') AS non_conjunctivitis
        FROM patient_uploads pu
        JOIN users u ON pu.uploaded_by = u.id
        WHERE u.role = 'healthcare'
        GROUP BY pu.uploaded_by
        ORDER BY conjunctivitis DESC
      ");
      $workerSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt = $pdo->query("
        SELECT u.full_name
        FROM patient_uploads pu
        JOIN users u ON pu.uploaded_by = u.id
        WHERE u.role = 'healthcare'
        GROUP BY uploaded_by
        ORDER BY COUNT(*) DESC
        LIMIT 1
      ");
      $mostActiveHealthcare = $stmt->fetchColumn() ?? '-';
    }

    // === Healthcare summary (extend with avg_confidence) ===
    $summary = [];
    if ($role === 'healthcare') {
      $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               COUNT(DISTINCT p.region) as regions,
               MAX(pu.created_at) as latest,
               FLOOR(AVG(TIMESTAMPDIFF(YEAR, p.dob, CURDATE()))) as average_age
        FROM patient_uploads pu
        JOIN patients p ON pu.patient_id = p.id
        $userFilter
      ");
      $stmt->execute($params);
      $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

      // Add the new fields here (non-breaking addition)
      $summary['avg_confidence']       = $avgConfidence;        // numeric (e.g., 82.3)
      $summary['avg_confidence_style'] = $avgConfidenceStyle;   // inline style string
    }

    echo json_encode([
      'summary'               => $summary,
      'adminCards'            => $adminCards,
      'detectionResults'      => $detectionResults,
      'genderDistribution'    => $genderDistribution,
      'trend'                 => $trend,
      'ageGroups'             => $ageGroups,
      'regionTrend'           => $regionTrend,
      'uploadsPerMonth'       => $monthlyUploadTrend,
      'mostActiveHealthcare'  => $mostActiveHealthcare,
      'regionSharedTrend'     => $regionSharedTrend,
      'regionUsers'           => $regionUserBreakdown,
      'workerSummary'         => $workerSummary,
      'weeklyUploads'         => $role === 'healthcare' ? $weeklyUploadTrend : null,
    ]);
    exit;
  }

  // === Patient-only logic ===
  if ($role === 'patient') {
    // Get patient id for this user
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient_id = $stmt->fetchColumn();
    if (!$patient_id) {
      echo json_encode(['error' => 'Patient not found']);
      exit;
    }

    // Upload trend (per-day counts)
    $stmt = $pdo->prepare("
      SELECT DATE(created_at) as date, COUNT(*) as count
      FROM patient_uploads
      WHERE patient_id = ?
      GROUP BY DATE(created_at)
    ");
    $stmt->execute([$patient_id]);
    $uploadTrend = array_map(fn($r) => ['date' => $r['date'], 'total' => (int)$r['count']], $stmt->fetchAll());

    // Totals and latest
    $stmt = $pdo->prepare("
      SELECT COUNT(*) as total, MAX(created_at) as latest
      FROM patient_uploads
      WHERE patient_id = ?
    ");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total  = (int)($row['total']  ?? 0);
    $latest =        $row['latest'] ?? '-';

    // Role-scoped avg confidence (patient)
    $stmt = $pdo->prepare("
      SELECT ROUND(AVG(confidence), 1)
      FROM patient_uploads
      WHERE patient_id = ?
        AND confidence IS NOT NULL
    ");
    $stmt->execute([$patient_id]);
    $avgConfidence = $stmt->fetchColumn();
    $avgConfidence = ($avgConfidence !== null) ? (float)$avgConfidence : null;
    $avgConfidenceStyle = style_for_confidence($avgConfidence);

    // Detection result counts
    $stmt = $pdo->prepare("
      SELECT diagnosis_result, COUNT(*)
      FROM patient_uploads
      WHERE patient_id = ?
      GROUP BY diagnosis_result
    ");
    $stmt->execute([$patient_id]);
    $map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $positive = (int)($map['Conjunctivitis'] ?? 0);
    $negative = (int)($map['NonConjunctivitis'] ?? 0);
    $detectionResults = [$positive, $negative];
    $ratioData = ['Conjunctivitis' => $positive, 'NonConjunctivitis' => $negative];

    echo json_encode([
      'summary' => [
        'total_uploads'         => $total,
        'latest_diagnosis'      => $latest,
        'avg_confidence'        => $avgConfidence,       // numeric (e.g., 82.3)
        'avg_confidence_style'  => $avgConfidenceStyle,  // inline style string
        'upload_freq'           => $total . " uploads / " . max(1, count($uploadTrend)) . " days"
      ],
      'detectionResults' => $detectionResults,
      'uploadTrend'      => $uploadTrend,
      'ratioData'        => $ratioData,
    ]);
    exit;
  }

  echo json_encode(['error' => 'Unsupported role']);
} catch (PDOException $e) {
  file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " | PDO Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
  http_response_code(500);
  echo json_encode(['error' => 'Database error']);
}
