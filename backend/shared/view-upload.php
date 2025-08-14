<?php 
require_once __DIR__ . '/../../config/db.php';

// 🔐 Start session based on URI path
if (session_status() === PHP_SESSION_NONE) {
    $uri = $_SERVER['REQUEST_URI'];
    
    if (str_contains($uri, '/healthcare/patients/')) {
        session_name('eyecheck_healthcare');
    } elseif (str_contains($uri, '/patient/')) {
        session_name('eyecheck_patient');
    } else {
        session_name('eyecheck_default');
    }

    session_start();
}

// 🔒 Check authentication
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    exit('❌ Unauthorized access');
}

$uploadId = intval($_GET['id'] ?? 0);
if (!$uploadId) {
    exit('❌ Invalid or missing upload ID');
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

try {
    if ($role === 'patient') {
        $sql = "
            SELECT p.*, pu.image_path, pu.diagnosis_result, confidence, pu.created_at
            FROM patients p
            JOIN patient_uploads pu ON pu.patient_id = p.id
            WHERE pu.id = :uploadId AND pu.uploaded_by = :userId
        ";
    } elseif ($role === 'healthcare') {
        $sql = "
            SELECT p.*, pu.image_path, pu.diagnosis_result, confidence, pu.created_at
            FROM patients p
            JOIN patient_uploads pu ON pu.patient_id = p.id
            WHERE pu.id = :uploadId
            AND (
                pu.uploaded_by = :userId OR
                p.created_by = :userId
            )
        ";
    } else {
        exit('❌ Unsupported role');
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uploadId' => $uploadId,
        ':userId' => $userId
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        exit('Record not found or access denied.');
    }

    $patient = $data;

} catch (PDOException $e) {
    exit('❌ Database error');
}
