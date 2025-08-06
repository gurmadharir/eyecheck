<?php
session_name("eyecheck_admin");
session_start();

require_once '../../config/db.php';
header("Content-Type: application/json");

// 🔐 Allow only admins
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// 🔒 Sanitize and validate inputs
$search = trim($_GET['search'] ?? '');
if (strlen($search) > 100) {
    echo json_encode(['success' => false, 'message' => 'Search input too long.']);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$allowedRoles = ['admin', 'healthcare'];
$role = in_array($_GET['role'] ?? '', $allowedRoles) ? $_GET['role'] : 'admin';

$region = trim($_GET['region'] ?? 'all');
$sort = ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'latest';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// 🔍 Build WHERE clause
$where = ["role = :role", "id != :currentUserId"];
$params = [
    ':role' => $role,
    ':currentUserId' => $currentUserId
];

// Optional search filter
if (!empty($search)) {
    $where[] = "(full_name LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

// Optional region filter (only for healthcare)
if ($role === 'healthcare' && $region !== 'all') {
    $where[] = "healthcare_region = :region";
    $params[':region'] = $region;
}

$whereSQL = "WHERE " . implode(" AND ", $where);
$orderBy = $sort === 'oldest' ? 'created_at ASC' : 'created_at DESC';

try {
    // 🔢 Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // 📥 Fetch paginated data
    $stmt = $pdo->prepare("
        SELECT id, full_name, username, email, healthcare_region, created_at
        FROM users
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
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🗺 Fetch distinct regions (if role is healthcare)
    $regions = [];
    if ($role === 'healthcare') {
        $regionStmt = $pdo->query("SELECT DISTINCT healthcare_region FROM users WHERE role = 'healthcare'");
        $regions = $regionStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ✅ Return response
    echo json_encode([
        'success' => true,
        'data' => $users,
        'total' => $total,
        'perPage' => $limit,
        'currentPage' => $page,
        'regions' => $regions
    ]);
} catch (PDOException $e) {
    error_log("Admin fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
