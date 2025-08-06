<?php
require_once '../../backend/helpers/auth-check.php';
requireRole('admin'); // ✅ Access control

require_once '../../config/db.php';
header("Content-Type: application/json");

// ✅ Sanitize and validate inputs
$search = trim($_GET['search'] ?? '');

// 🔒 Limit search length
if (strlen($search) > 100) {
    echo json_encode(['success' => false, 'message' => 'Search input too long.']);
    exit();
}

$sort = ($_GET['sort'] === 'oldest') ? 'oldest' : 'latest';

$start = trim($_GET['start'] ?? '');
$end = trim($_GET['end'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["u.role = 'patient'"];
$params = [];

// ✅ Safe search
if (!empty($search)) {
    $where[] = "(u.full_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

// ✅ Optional date filtering
if (!empty($start) && !empty($end)) {
    $where[] = "DATE(u.created_at) BETWEEN :start AND :end";
    $params[':start'] = $start;
    $params[':end'] = $end;
}

$whereSQL = "WHERE " . implode(" AND ", $where);
$orderBy = ($sort === 'oldest') ? 'u.created_at ASC' : 'u.created_at DESC';

try {
    // ✅ Count total results
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
        $whereSQL
    ");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // ✅ Fetch paginated results
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.created_at, u.role
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $patients,
        'total' => $total,
        'perPage' => $limit,
        'currentPage' => $page
    ]);

} catch (PDOException $e) {
    error_log("Admin fetch patients error: " . $e->getMessage()); // ✅ internal logging
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
