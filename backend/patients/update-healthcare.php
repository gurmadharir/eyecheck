<?php
require_once('../../config/db.php');
require_once __DIR__ . '/../helpers/log-activity.php';


// ✅ Start healthcare session
if (session_status() === PHP_SESSION_NONE) {
    session_name('eyecheck_healthcare');
    session_start();
}

// ✅ Set response type
header("Content-Type: application/json");

// ✅ Only logged-in healthcare users
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'healthcare') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

// ✅ Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// ✅ Clean function
function clean($input, $maxLength = 100) {
    return mb_substr(trim($input ?? ''), 0, $maxLength);
}

// ✅ Inputs
$userId    = $_SESSION['user_id'];
$patientId = intval($_POST['id'] ?? 0);
$name      = clean($_POST['name'], 100);
$contact   = clean($_POST['contact'], 20);
$town      = clean($_POST['town'], 50);
$region    = clean($_POST['region'], 50);
$gender    = strtolower(clean($_POST['gender']));
$dob       = clean($_POST['dob'], 10); // YYYY-MM-DD

// ✅ Validate
$errors = [];
if (!$patientId || !$name || !$contact || !$town || !$region || !$gender || !$dob) {
    $errors[] = "All fields are required.";
}
if (!preg_match("/^[a-zA-Z ']+$/", $name)) {
    $errors[] = "Name must contain only letters, spaces or apostrophes.";
}
if (!filter_var($contact, FILTER_VALIDATE_EMAIL) && !preg_match("/^[0-9+\-\s]{7,20}$/", $contact)) {
    $errors[] = "Contact must be a valid email or phone number.";
}
if (!in_array($gender, ['male', 'female', 'other'])) {
    $errors[] = "Invalid gender.";
}
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) {
    $errors[] = "Invalid date format.";
}

// ❌ Validation failed
if ($errors) {
    echo json_encode([
        'success' => false,
        'message' => implode(" ", $errors)
    ]);
    exit;
}

// ✅ Check patient ownership via `created_by`
$stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ? AND created_by = ?");
$stmt->execute([$patientId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Patient not found or access denied.'
    ]);
    exit;
}

// ✅ Update record
try {
    // ✅ Fetch current data
    $current = $pdo->prepare("SELECT id, name, contact, town, region, gender, dob FROM patients WHERE id = ? AND created_by = ?");
    $current->execute([$patientId, $userId]);
    $existing = $current->fetch(PDO::FETCH_ASSOC);

    // ✅ Check if all fields match
    if (
        $existing &&
        $existing['name'] === $name &&
        $existing['contact'] === $contact &&
        $existing['town'] === $town &&
        $existing['region'] === $region &&
        $existing['gender'] === $gender &&
        $existing['dob'] === $dob
    ) {
        echo json_encode([
            'success' => false,
            'message' => "No changes detected."
        ]);

        // Log
        logActivity($userId, 'healthcare', 'NO_UPDATE', "Tried to update patient (ID: $patientId) but no changes detected", $patientId);
        exit;
    }

    // ✅ Proceed with update if data is different
    $update = $pdo->prepare("
        UPDATE patients
        SET name = :name, contact = :contact, town = :town, region = :region, gender = :gender, dob = :dob
        WHERE id = :id AND created_by = :user_id
    ");

    $updated = $update->execute([
        ':name'     => $name,
        ':contact'  => $contact,
        ':town'     => $town,
        ':region'   => $region,
        ':gender'   => $gender,
        ':dob'      => $dob,
        ':id'       => $patientId,
        ':user_id'  => $userId
    ]);

    logActivity($userId, 'healthcare', 'UPDATE_PATIENT', "Updated patient info (ID: $patientId)", $patientId);

    echo json_encode([
        'success' => true,
        'message' => "Patient updated successfully."
    ]);

} catch (PDOException $e) {
    error_log("Update error: " . $e->getMessage());

    // Log
    logActivity($userId, 'healthcare', 'UPDATE_ERROR', "Error updating patient (ID: $patientId): " . $e->getMessage(), $patientId);
    
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}
?>
