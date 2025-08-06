<?php
header('Content-Type: application/json');
require_once('../helpers/auth-check.php');
requireRole('admin');
require_once('../../config/db.php'); // your PDO connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Read query parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'latest';  // latest or oldest
$filter = $_GET['filter'] ?? 'pending'; // pending or warned
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$params = [];
$whereClauses = [];
$orderBy = $sort === 'oldest' ? 'ASC' : 'DESC';

try {
    if ($filter === 'pending') {
        // Fetch from pending_users table
        $table = 'pending_users';
        $selectFields = "id, full_name, email, created_at";
        
        if ($search) {
            $whereClauses[] = "(full_name LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Count total rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table $whereSQL");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        // Fetch paginated results
        $stmt = $pdo->prepare("
            SELECT $selectFields
            FROM $table
            $whereSQL
            ORDER BY created_at $orderBy
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($filter === 'flagged') {
        // Fetch from patients table where flagged = 1
        $table = 'patients p
          JOIN users u ON p.user_id = u.id';
        $selectFields = "p.id, p.user_id, p.name AS full_name, u.email, p.created_at, p.warnings_sent, p.flagged_at";


        if ($search) {
            $whereClauses[] = "(name LIKE :search OR contact LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $whereClauses[] = "flagged = 1";  // ✅ correct condition
        $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

        // Count total rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table $whereSQL");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        // Fetch paginated results
        $stmt = $pdo->prepare("
            SELECT $selectFields
            FROM $table
            $whereSQL
            ORDER BY flagged_at $orderBy
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


    } else {
        // Invalid filter
        echo json_encode(['success' => false, 'message' => 'Invalid filter']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $users,
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $page,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
