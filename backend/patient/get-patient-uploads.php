<?php
require_once '../../config/db.php';

// ✅ Isolate session for patient
if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_patient');
    session_start();
}

header("Content-Type: application/json");

// ✅ Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Get corresponding patient.id
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode(['success' => true, 'data' => [], 'total' => 0, 'perPage' => 10, 'currentPage' => 1]);
    exit();
}

$patient_id = $patient['id'];

// ✅ Read filters
$result = $_GET['result'] ?? 'all';
$sort = $_GET['sort'] ?? 'latest';
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// ✅ Build dynamic WHERE clause
$where = "WHERE patient_id = :patient_id";
$params = [':patient_id' => $patient_id];

if ($result !== 'all') {
    $mappedResult = $result === 'Conjunctivitis' ? 'Positive' : $result;
    $where .= " AND diagnosis_result = :result";
    $params[':result'] = $mappedResult;
}

if (!empty($startDate)) {
    $where .= " AND DATE(created_at) >= :start";
    $params[':start'] = $startDate;
}

if (!empty($endDate)) {
    $where .= " AND DATE(created_at) <= :end";
    $params[':end'] = $endDate;
}

$orderBy = ($sort === 'oldest') ? 'created_at ASC' : 'created_at DESC';

// ✅ Count total
$countSql = "SELECT COUNT(*) FROM patient_uploads $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// ✅ Fetch paginated results
$dataSql = "SELECT id, image_path, diagnosis_result, created_at
            FROM patient_uploads
            $where
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$uploads = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Return JSON
echo json_encode([
    'success' => true,
    'data' => $uploads,
    'total' => intval($total),
    'perPage' => $limit,
    'currentPage' => $page
]);
