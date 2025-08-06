<?php
// ✅ Dynamically assign session_name based on user role (from user_id)
if (session_status() === PHP_SESSION_NONE) {
    if (isset($_POST['user_id'])) {
        require_once('../../config/db.php');
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([(int)$_POST['user_id']]);
        $roleFromDb = $stmt->fetchColumn();

        switch ($roleFromDb) {
            case 'admin': session_name('eyecheck_admin'); break;
            case 'healthcare': session_name('eyecheck_healthcare'); break;
            case 'patient': session_name('eyecheck_patient'); break;
            default: session_name('eyecheck_default');
        }
    } else {
        session_name('eyecheck_default');
    }
    session_start();
}

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once('../../config/db.php');
require_once __DIR__ . '/../helpers/log-activity.php';

header('Content-Type: application/json');

// ✅ Session authentication
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sessionUserId = (int) $_SESSION['user_id'];
$sessionRole = strtolower($_SESSION['role']);
$allowedRoles = ['admin', 'healthcare', 'patient'];

if (!in_array($sessionRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

// ✅ Get target user id
$targetUserId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : $sessionUserId;
$isSelf = $targetUserId === $sessionUserId;

// ✅ Permission: only self, or admin/healthcare editing others
if (!$isSelf && !in_array($sessionRole, ['admin', 'healthcare'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized update attempt']);
    exit();
}

// ✅ Sanitize input
$full_name = trim($_POST['full_name'] ?? '');
$usernameRaw = trim($_POST['username'] ?? '');
$username = strtolower(ltrim($usernameRaw, '@')); // remove @, force lowercase
$username = preg_replace('/[^a-z0-9_]/', '', $username); // only a-z, 0-9, _

// ✅ Validate name
if (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Full name must only contain letters and spaces']);
    exit();
}

// ✅ Validate username
if (!preg_match("/^[a-z0-9_]{3,20}$/", $username)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Username must be 3–20 chars, lowercase, numbers or _']);
    exit();
}

// ✅ Check if username already taken (exclude self)
$stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = ? AND id != ?");
$stmt->execute([$username, $targetUserId]);

if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Username is already taken.']);
    exit();
}

// ✅ Fetch current image info
$stmt = $pdo->prepare("SELECT profile_image, profile_image_hash FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$currentImagePath = $currentUser['profile_image'] ?? '';
$currentImageHash = $currentUser['profile_image_hash'] ?? null;

$profileImagePath = null;
$newImageHash = null;

// ✅ Handle image upload
if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes)) {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WEBP or GIF allowed']);
        exit();
    }

    if ($file['size'] > 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Image must be under 1MB']);
        exit();
    }

    $newImageHash = md5_file($file['tmp_name']);
    // ✅ Only block if same user is re-uploading same image
    if (!empty($currentImageHash) && $newImageHash === $currentImageHash) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You already use this image. Try a different one.']);
        exit();
    }


    // ✅ Save image
    $uploadDir = __DIR__ . "/../../$sessionRole/uploads/profile/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finalFileName = $safeName . '.' . $ext;
    $targetPath = $uploadDir . $finalFileName;

    if (file_exists($targetPath)) {
        $finalFileName = $safeName . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $finalFileName;
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
        
        // LOG
        logActivity($sessionUserId, $sessionRole, 'UPLOAD_ERROR', 'Failed to save uploaded profile image');
        exit();
    }

    $profileImagePath = $finalFileName; // ✅ just filename like "me.jpg"

    // ✅ Remove old image
    $expectedDir = realpath($uploadDir);
    $resolvedOld = realpath($uploadDir . $currentImagePath);
    if (
    $resolvedOld &&
    strpos($resolvedOld, $expectedDir) === 0 &&
        file_exists($resolvedOld) &&
        is_file($resolvedOld) // ✅ ensures it's not a directory
    ) {
        unlink($resolvedOld);
    }

}

// ✅ Final update query
try {
    if ($profileImagePath !== null) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, profile_image = ?, profile_image_hash = ? WHERE id = ?");
        $stmt->execute([$full_name, $username, $finalFileName, $newImageHash, $targetUserId]);

        if ($isSelf) {
            $_SESSION['profile_image'] = $finalFileName; // ✅ just the filename like "me.jpg"
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
        $stmt->execute([$full_name, $username, $targetUserId]);
    }

    if ($isSelf) {
        $_SESSION['full_name'] = $full_name;
    }

    // Log 
    logActivity(
        $sessionUserId,
        $sessionRole,
        'UPDATE_PROFILE',
        $targetUserId === $sessionUserId
            ? 'User updated their own profile'
            : "Updated user profile (ID $targetUserId)",
        $targetUserId
    );

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'redirect' => "/eyecheck/$sessionRole/dashboard.php"
    ]);
    exit();

} catch (PDOException $e) {
    error_log("Update failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit();
}
