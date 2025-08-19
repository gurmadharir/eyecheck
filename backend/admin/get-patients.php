<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin'); // ✅ Access control

require_once '../../config/db.php';
header('Content-Type: application/json');

// ---------- Inputs (sanitized) ----------
$search = trim($_GET['search'] ?? '');
$sort   = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'latest';

$status = strtolower(trim($_GET['status'] ?? 'active'));   // active | inactive | all
$filter = strtolower(trim($_GET['filter'] ?? ''));         // '' | pending | flagged

$start  = trim($_GET['start'] ?? '');
$end    = trim($_GET['end'] ?? '');

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// 🔒 Limit search length
if (strlen($search) > 100) {
    echo json_encode(['success' => false, 'message' => 'Search input too long.']);
    exit();
}

// 🔒 Validate YYYY-MM-DD for dates (optional)
$validDate = static function ($d) {
    if ($d === '') return true;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
};

if (!$validDate($start) || !$validDate($end)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit();
}

// 🔧 Harmonize incompatible combos
// If filter=pending (means "inactive created within 2 days"), force status=inactive
if ($filter === 'pending') {
    $status = 'inactive';
}

// ---------- WHERE builder ----------
$where   = ["u.role = 'patient'"];
$params  = [];

// ✅ Safe search
if ($search !== '') {
    $where[] = "(u.full_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// ✅ Optional date filtering (only if both provided)
if ($start !== '' && $end !== '') {
    $where[] = "DATE(u.created_at) BETWEEN :start AND :end";
    $params[':start'] = $start;
    $params[':end']   = $end;
}

// ✅ Status filter
if ($status === 'active') {
    $where[] = "u.is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "u.is_active = 0";
} // 'all' => no condition

// ✅ “filter” presets:
// - pending: users created within last 2 days and inactive
// - flagged: patients with warnings or a flagged timestamp
if ($filter === 'pending') {
    $where[] = "u.is_active = 0";
    $where[] = "u.created_at >= (CURRENT_TIMESTAMP - INTERVAL 2 DAY)";
} elseif ($filter === 'flagged') {
    $where[] = "(COALESCE(p.warnings_sent, 0) > 0 OR p.flagged_at IS NOT NULL)";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$orderBy  = ($sort === 'oldest') ? 'u.created_at ASC' : 'u.created_at DESC';

try {
    // ---------- Count ----------
    $countSql = "
        SELECT COUNT(*)
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
        $whereSQL
    ";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // ---------- Data ----------
    $dataSql = "
        SELECT
            u.id AS id,
            u.id AS user_id,
            u.full_name,
            u.email,
            u.created_at,
            u.role,
            u.is_active,
            COALESCE(p.warnings_sent, 0) AS warnings_sent,
            p.flagged_at
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'     => true,
        'data'        => $patients,
        'total'       => $total,
        'perPage'     => $limit,
        'currentPage' => $page
    ]);
} catch (PDOException $e) {
    error_log('Admin fetch patients error: ' . $e->getMessage()); // ✅ internal logging
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
