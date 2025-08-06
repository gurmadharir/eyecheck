<?php
require_once('../../helpers/auth-check.php');
requireRole('admin');
require_once('../../../config/db.php');
header('Content-Type: application/json');

try {
  // 🟢 Handle fetch users by role (only allow admin and healthcare)
  if (isset($_GET['fetch_users_by_role'])) {
    $role = $_GET['fetch_users_by_role'];

    if (in_array($role, ['admin', 'healthcare'])) {
      $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = ?");
      $stmt->execute([$role]);
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success' => true, 'users' => $users]);
    } else {
      // Return empty if role is not allowed (like patient)
      echo json_encode(['success' => false, 'message' => 'Fetching users for this role is not allowed.']);
    }

    exit;
  }

  // 🟡 Continue with logs fetch
  $role = $_GET['role'] ?? '';
  $action = $_GET['action'] ?? '';
  $sort = $_GET['sort'] ?? 'latest';
  $search = $_GET['search'] ?? '';

  $query = "
    SELECT l.*, u.username
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE 1=1
  ";
  $params = [];

  if ($role !== '') {
    $query .= " AND l.role = ?";
    $params[] = $role;
  }

  if ($action !== '') {
    $query .= " AND l.action = ?";
    $params[] = $action;
  }

  if ($search !== '') {
    $query .= " AND (u.username LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
  }

  $query .= $sort === 'oldest'
    ? " ORDER BY l.created_at ASC"
    : " ORDER BY l.created_at DESC";

  $query .= " LIMIT 100";

  $stmt = $pdo->prepare($query);
  $stmt->execute($params);
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 🔁 Fix IPv6 localhost
  foreach ($logs as &$log) {
    if ($log['ip_address'] === '::1') {
      $log['ip_address'] = '127.0.0.1';
    }
  }

  echo json_encode(['success' => true, 'logs' => $logs]);
} catch (PDOException $e) {
  error_log("❌ Fetch Logs Error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Failed to fetch logs']);
}
