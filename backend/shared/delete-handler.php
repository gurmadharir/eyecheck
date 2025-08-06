<?php 

if (isset($_POST['role']) && in_array($_POST['role'], ['patient', 'healthcare', 'admin'])) {
    $_SERVER['REQUEST_URI'] = "/{$_POST['role']}/shared/delete-handler.php";
}

ob_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth-check.php';
require_once __DIR__ . '/../helpers/log-activity.php';


header('Content-Type: application/json');

// ✅ Local image deletion helper
function deleteImagesByPaths($paths) {
    foreach ($paths as $relativePath) {
        $fullPath = __DIR__ . '/../../' . ltrim($relativePath, '/');
        if ($relativePath && file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

// ✅ Logging helper
function logDebug($data) {
    $logFile = 'C:/xampp/htdocs/eyecheck/logs/debug-delete.log';
    $logEntry = "----- " . date('Y-m-d H:i:s') . " -----\n" . print_r($data, true) . "\n\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// ✅ Response shell
$response = ['success' => false, 'message' => 'Invalid request'];

$role = $_SESSION['role'] ?? 'guest';
$loggedInUserId = $_SESSION['user_id'] ?? null;
$isSuperAdmin = $_SESSION['is_super_admin'] ?? 0;

$targetUserId = $_POST['target_user_id'] ?? null;
$targetPatientId = $_POST['target_patient_id'] ?? null;
$targetUploadId = $_POST['target_upload_id'] ?? null;

// ✅ Log incoming request
logDebug([
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $loggedInUserId,
    'role' => $role,
    'POST' => $_POST,
]);

// ✅ SUPER ADMIN: Delete any user
if ($role === 'admin' && $isSuperAdmin && $targetUserId) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $userToDelete = $stmt->fetch();

    if ($userToDelete) {
        $userRole = $userToDelete['role'];

        if ($userRole === 'healthcare') {
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE created_by = ?");
            $stmt->execute([$targetUserId]);
            $patientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($patientIds as $pid) {
                $stmt = $pdo->prepare("SELECT image_path FROM patient_uploads WHERE patient_id = ?");
                $stmt->execute([$pid]);
                deleteImagesByPaths($stmt->fetchAll(PDO::FETCH_COLUMN));

                $pdo->prepare("DELETE FROM patient_uploads WHERE patient_id = ?")->execute([$pid]);
            }

            $pdo->prepare("DELETE FROM patients WHERE created_by = ?")->execute([$targetUserId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);
            $response = ['success' => true, 'message' => 'Healthcare user and related data deleted'];
            // LOG
            logActivity($loggedInUserId, $role, 'DELETE_USER', "Deleted healthcare user ID $targetUserId and all related patients/uploads", $targetUserId);

        }

        elseif ($userRole === 'patient') {
            // 🔍 Step 1: Get related patient record (if exists)
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
            $stmt->execute([$targetUserId]);
            $patientId = $stmt->fetchColumn();

            if ($patientId) {
                // 🔍 Step 2: Get all image paths for that patient
                $stmt = $pdo->prepare("SELECT image_path FROM patient_uploads WHERE patient_id = ?");
                $stmt->execute([$patientId]);
                $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // 🧹 Step 3: Delete local images
                deleteImagesByPaths($imagePaths);

                // ❌ Step 4: Delete uploads + patient record
                $pdo->prepare("DELETE FROM patient_uploads WHERE patient_id = ?")->execute([$patientId]);
                $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$patientId]);
            }

            // ❌ Step 5: Delete the user regardless of patient linkage
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);

            $response = ['success' => true, 'message' => 'Patient user and all related data deleted'];

            // LOG
            logActivity($loggedInUserId, $role, 'DELETE_USER', "Deleted patient user ID $targetUserId and related uploads", $targetUserId);

        }

        else {
            // Fallback for non-patient/admin
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);
            $response = ['success' => true, 'message' => 'Admin user deleted successfull.'];

            // LOG
            logActivity($loggedInUserId, $role, 'DELETE_USER', "Deleted user ID $targetUserId", $targetUserId);

        }
    } else {
        $response['message'] = 'User not found';
    }
}


// ✅ HEALTHCARE: Delete own patient
elseif ($role === 'healthcare' && $targetPatientId) {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ? AND created_by = ?");
    $stmt->execute([$targetPatientId, $loggedInUserId]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT image_path FROM patient_uploads WHERE patient_id = ?");
        $stmt->execute([$targetPatientId]);
        deleteImagesByPaths($stmt->fetchAll(PDO::FETCH_COLUMN));

        $pdo->prepare("DELETE FROM patient_uploads WHERE patient_id = ?")->execute([$targetPatientId]);
        $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$targetPatientId]);

        $response = ['success' => true, 'message' => 'Patient and uploads deleted'];

        // LOG
        logActivity($loggedInUserId, $role, 'DELETE_PATIENT', "Deleted patient ID $targetPatientId and their uploads", $targetPatientId);
    } else {
        $response['message'] = 'Patient not found or unauthorized';
    }
}

// ✅ PATIENT: Delete own upload
elseif ($role === 'patient' && $targetUploadId) {
    $stmt = $pdo->prepare("SELECT image_path FROM patient_uploads WHERE id = ? AND uploaded_by = ?");
    $stmt->execute([$targetUploadId, $loggedInUserId]);
    $upload = $stmt->fetch();

    if ($upload) {
        deleteImagesByPaths([$upload['image_path']]);
        $pdo->prepare("DELETE FROM patient_uploads WHERE id = ?")->execute([$targetUploadId]);
        $response = ['success' => true, 'message' => 'Upload deleted'];
        
        // LOG
        logActivity($loggedInUserId, $role, 'DELETE_UPLOAD', "Deleted their own upload ID $targetUploadId", $targetUploadId);
    } else {
        $response['message'] = 'Upload not found or unauthorized';
    }
}

// ✅ Final response log
logDebug(['response' => $response]);
ob_end_clean();
echo json_encode($response);
exit;
