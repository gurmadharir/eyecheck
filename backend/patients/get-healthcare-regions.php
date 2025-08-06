<?php
require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_healthcare');
    session_start();
}

header('Content-Type: application/json');

// 🔐 Get logged in healthcare user ID
$healthcareId = $_SESSION['user_id'] ?? null;

if (!$healthcareId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // 🎯 Only regions for patients created by this healthcare user
    $stmt = $pdo->prepare("
        SELECT DISTINCT region
        FROM patients
        WHERE region IS NOT NULL AND region != ''
          AND created_by = :healthcareId
        ORDER BY region ASC
    ");
    $stmt->execute([':healthcareId' => $healthcareId]);
    $regions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'regions' => $regions]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
