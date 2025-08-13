<?php
require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_healthcare');
    session_start();
}

header("Content-Type: application/json");

// 🔐 Auth check
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'healthcare') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$healthcareId = (int) $_SESSION['user_id'];

// 🧹 Tiny sanitizer (one-line comment: trims text and caps length)
function sanitize($v, $max = 120) {
    $v = isset($v) ? trim($v) : '';
    return mb_substr($v, 0, $max);
}

$search    = sanitize($_GET['search'] ?? '');
$genderIn  = sanitize($_GET['gender'] ?? 'all');
$resultIn  = sanitize($_GET['result'] ?? 'all');
$region    = sanitize($_GET['region'] ?? 'all');
$ageFilter = sanitize($_GET['age'] ?? 'all');
$dateSort  = (($_GET['sort'] ?? '') === 'oldest') ? 'oldest' : 'latest';
$start     = sanitize($_GET['start'] ?? '');
$end       = sanitize($_GET['end'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));
$limit     = 10;
$offset    = ($page - 1) * $limit;

/* =============================
   Build patient-level WHERE
   (name/contact/town/gender/region/age + ownership)
   ============================= */
$pWhere = ["p.created_by = :hc"];
$pParams = [":hc" => $healthcareId];

if ($search !== '') {
    $pWhere[] = "(p.name LIKE :q OR p.contact LIKE :q OR p.town LIKE :q)";
    $pParams[":q"] = "%{$search}%";
}

$g = strtolower($genderIn);
if (in_array($g, ['male','female','other'], true)) {
    $pWhere[] = "LOWER(p.gender) = :gender";
    $pParams[":gender"] = $g;
}

if ($region !== '' && $region !== 'all') {
    $pWhere[] = "p.region = :region";
    $pParams[":region"] = $region;
}

switch ($ageFilter) {
    case 'below20':
        $pWhere[] = "TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) < 20";
        break;
    case '20to40':
        $pWhere[] = "TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN 20 AND 40";
        break;
    case 'above40':
        $pWhere[] = "TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) > 40";
        break;
}
$pWhereSql = $pWhere ? ("WHERE " . implode(" AND ", $pWhere)) : "";

/* =============================
   Build upload-level WHERE
   (diagnosis_result/date/path + ownership mirror)
   We filter uploads FIRST, then pick latest per patient.
   ============================= */
$uWhere = [
    "pu2.image_path LIKE 'healthcare/patients/uploads/%'",
    "p2.created_by = :hc" // mirror ownership (joins p2 inside subqueries)
];
$uParams = [":hc" => $healthcareId];

/* Support both canonical and legacy values:
   - Conjunctivitis     => ['Conjunctivitis','Positive']
   - NonConjunctivitis  => ['NonConjunctivitis','Negative'] */
$val = strtolower($resultIn);
if ($val !== '' && $val !== 'all') {
    if ($val === 'conjunctivitis' || $val === 'positive') {
        $uWhere[]           = "pu2.diagnosis_result IN (:r1, :r2)";
        $uParams[":r1"]     = 'Conjunctivitis';
        $uParams[":r2"]     = 'Positive';
    } elseif ($val === 'nonconjunctivitis' || $val === 'negative') {
        $uWhere[]           = "pu2.diagnosis_result IN (:r1, :r2)";
        $uParams[":r1"]     = 'NonConjunctivitis';
        $uParams[":r2"]     = 'Negative';
    }
}

if ($start !== '') {
    $uWhere[] = "DATE(pu2.created_at) >= :ustart";
    $uParams[":ustart"] = $start;
}
if ($end !== '') {
    $uWhere[] = "DATE(pu2.created_at) <= :uend";
    $uParams[":uend"] = $end;
}
$uWhereSql = "WHERE " . implode(" AND ", $uWhere);

$orderBy = ($dateSort === 'oldest') ? 'pu.created_at ASC' : 'pu.created_at DESC';

try {
    /* -----------------------------------
       COUNT patients who have ≥1 matching upload
       ----------------------------------- */
    $countSQL = "
        SELECT COUNT(*) FROM (
          SELECT p.id
          FROM patients p
          JOIN (
             SELECT pu2.patient_id, MAX(pu2.created_at) AS max_created_at
             FROM patient_uploads pu2
             JOIN patients p2 ON p2.id = pu2.patient_id
             $uWhereSql
             GROUP BY pu2.patient_id
          ) last ON last.patient_id = p.id
          $pWhereSql
        ) AS sub
    ";
    $countStmt = $pdo->prepare($countSQL);
    foreach ($uParams as $k => $v) $countStmt->bindValue($k, $v);
    foreach ($pParams as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    /* -----------------------------------
       FETCH page of patients with their latest matching upload
       ----------------------------------- */
    $dataSQL = "
      SELECT
        p.*,
        pu.id AS upload_id,
        pu.image_path,
        pu.diagnosis_result,
        pu.created_at AS upload_date
      FROM patients p
      JOIN (
         -- pick latest matching upload per patient (after filters)
         SELECT pu.*
         FROM patient_uploads pu
         JOIN (
            SELECT pu2.patient_id, MAX(pu2.created_at) AS max_created_at
            FROM patient_uploads pu2
            JOIN patients p2 ON p2.id = pu2.patient_id
            $uWhereSql
            GROUP BY pu2.patient_id
         ) last
           ON last.patient_id = pu.patient_id
          AND last.max_created_at = pu.created_at
      ) pu ON pu.patient_id = p.id
      $pWhereSql
      ORDER BY $orderBy
      LIMIT :limit OFFSET :offset
    ";
    $dataStmt = $pdo->prepare($dataSQL);
    foreach ($uParams as $k => $v) $dataStmt->bindValue($k, $v);
    foreach ($pParams as $k => $v) $dataStmt->bindValue($k, $v);
    $dataStmt->bindValue(':limit',  $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'     => true,
        'data'        => $rows,
        'total'       => $total,
        'perPage'     => $limit,
        'currentPage' => $page
    ]);
} catch (PDOException $e) {
    error_log("Healthcare patients fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
