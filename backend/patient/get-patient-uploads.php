<?php
require_once '../../config/db.php';

// Isolate session for patient
if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_patient');
    session_start();
}

header("Content-Type: application/json");

// Auth check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get patient.id for this user
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode(['success' => true, 'data' => [], 'total' => 0, 'perPage' => 10, 'currentPage' => 1]);
    exit();
}

$patient_id = $patient['id'];

// Read filters
$result    = $_GET['result'] ?? 'all';     // e.g. Conjunctivitis | NonConjunctivitis | Positive | Negative | all
$sort      = $_GET['sort'] ?? 'latest';    // latest | oldest
$startDate = $_GET['start'] ?? '';
$endDate   = $_GET['end'] ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));
$limit     = 10;
$offset    = ($page - 1) * $limit;

// Build WHERE
$where  = "WHERE patient_id = :patient_id";
$params = [':patient_id' => $patient_id];

/*
  Support both canonical and legacy values:
  - Conjunctivitis     => ['Conjunctivitis','Positive']
  - NonConjunctivitis  => ['NonConjunctivitis','Negative']
*/
$val = strtolower(trim($result));
if ($val && $val !== 'all') {
    if ($val === 'conjunctivitis' || $val === 'positive') {
        $where .= " AND diagnosis_result IN (:r1, :r2)";
        $params[':r1'] = 'Conjunctivitis';
        $params[':r2'] = 'Positive';
    } elseif ($val === 'nonconjunctivitis' || $val === 'negative') {
        $where .= " AND diagnosis_result IN (:r1, :r2)";
        $params[':r1'] = 'NonConjunctivitis';
        $params[':r2'] = 'Negative';
    }
}

// Date range
if ($startDate !== '') {
    $where .= " AND DATE(created_at) >= :start";
    $params[':start'] = $startDate;
}
if ($endDate !== '') {
    $where .= " AND DATE(created_at) <= :end";
    $params[':end'] = $endDate;
}

// Sort
$orderBy = ($sort === 'oldest') ? 'created_at ASC' : 'created_at DESC';

// Count (same WHERE)
$countSql  = "SELECT COUNT(*) FROM patient_uploads $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Fetch page (same WHERE)
$dataSql = "
  SELECT id, image_path, diagnosis_result, created_at
  FROM patient_uploads
  $where
  ORDER BY $orderBy
  LIMIT :limit OFFSET :offset
";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit',  $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$uploads = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// Response
echo json_encode([
    'success'     => true,
    'data'        => $uploads,
    'total'       => $total,
    'perPage'     => $limit,
    'currentPage' => $page,
]);
