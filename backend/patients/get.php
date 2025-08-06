<?php
require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_healthcare');
    session_start();
}

header("Content-Type: application/json");

// 🔐 Authorization
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'healthcare') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$healthcareId = $_SESSION['user_id'];

// 🧹 Sanitize & limit input
function sanitizeInput($input, $maxLength = 100) {
    $clean = trim($input ?? '');
    return mb_substr($clean, 0, $maxLength);
}

$search = sanitizeInput($_GET['search'] ?? '');
$gender = sanitizeInput($_GET['gender']);
$result = sanitizeInput($_GET['result']);
$region = sanitizeInput($_GET['region']);
$ageFilter = sanitizeInput($_GET['age']);
$dateSort = ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'latest';
$start = sanitizeInput($_GET['start']);
$end = sanitizeInput($_GET['end']);
$page = max(1, intval($_GET['page'] ?? 1));

// 🧩 Base Query
$where = [];
$params = [':healthcareId' => $healthcareId];
$join = "LEFT JOIN patient_uploads pu ON pu.patient_id = p.id";

// 🔍 Search
if (!empty($search)) {
    $where[] = "(p.name LIKE :search OR p.contact LIKE :search OR p.town LIKE :search)";
    $params[':search'] = "%$search%";
}

// ✅ Gender
$validGenders = ['male', 'female', 'other'];
if (in_array(strtolower($gender), $validGenders)) {
    $where[] = "p.gender = :gender";
    $params[':gender'] = $gender;
}

// ✅ Result
if (in_array($result, ['Positive', 'Negative', 'Conjunctivitis'])) {
    $mapped = $result === 'Conjunctivitis' ? 'Positive' : $result;
    $where[] = "pu.diagnosis_result = :result";
    $params[':result'] = $mapped;
}

// ✅ Region
if ($region !== 'all' && $region !== '') {
    $where[] = "p.region = :region";
    $params[':region'] = $region;
}

// ✅ Age
$currentYear = date("Y");
switch ($ageFilter) {
    case 'below20':
        $where[] = "($currentYear - YEAR(p.dob)) < 20";
        break;
    case '20to40':
        $where[] = "($currentYear - YEAR(p.dob)) BETWEEN 20 AND 40";
        break;
    case 'above40':
        $where[] = "($currentYear - YEAR(p.dob)) > 40";
        break;
}

// ✅ Date range
if (!empty($start)) {
    $where[] = "DATE(pu.created_at) >= :start";
    $params[':start'] = $start;
}
if (!empty($end)) {
    $where[] = "DATE(pu.created_at) <= :end";
    $params[':end'] = $end;
}

// ✅ Restrict to:
$where[] = "pu.image_path LIKE 'healthcare/patients/uploads/%'";
$where[] = "p.created_by = :healthcareId"; // ✅ Only their own created patients

// ✅ Final clauses
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$orderBy = $dateSort === 'oldest' ? 'pu.created_at ASC' : 'pu.created_at DESC';
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // 📊 Count total
    $countSQL = "SELECT COUNT(DISTINCT p.id) FROM patients p $join $whereSQL";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // 📄 Fetch data
    $dataSQL = "
        SELECT p.*, pu.id AS upload_id, pu.image_path, pu.diagnosis_result, pu.created_at AS upload_date
        FROM patients p
        $join
        $whereSQL
        GROUP BY p.id
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ";
    $dataStmt = $pdo->prepare($dataSQL);
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $records = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $records,
        'total' => $total,
        'perPage' => $limit,
        'currentPage' => $page
    ]);
} catch (PDOException $e) {
    error_log("Healthcare fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
