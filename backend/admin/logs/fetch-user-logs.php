<?php
require_once('../../helpers/auth-check.php');
requireRole('admin');
require_once('../../../config/db.php');
header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'User ID is required']);
  exit;
}

try {
  $userId = $_GET['user_id'];

  $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
  $stmt->execute([$userId]);
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($logs as &$log) {
    if ($log['ip_address'] === '::1') {
      $log['ip_address'] = '127.0.0.1';
    }
  }

  echo json_encode(['success' => true, 'logs' => $logs]);
} catch (PDOException $e) {
  error_log("❌ Fetch User Logs Error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Failed to fetch user logs']);
}
