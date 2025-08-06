<?php
require_once __DIR__ . '/../../config/db.php'; 

function logActivity($user_id, $role, $action, $description) {
    global $pdo;
    try {
        $sql = "INSERT INTO activity_logs (user_id, role, action, description, created_at)
                VALUES (:user_id, :role, :action, :description, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':role' => $role,
            ':action' => $action,
            ':description' => $description
        ]);
    } catch (PDOException $e) {
        error_log("❌ LOG ERROR: " . $e->getMessage());
    }
}
