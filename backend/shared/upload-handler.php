<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Error fallback
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit();
});
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Uncaught Exception: ' . $e->getMessage()]);
    exit();
});

header('Content-Type: application/json');
require_once '../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';

// Secure session by role
$formRole = $_POST['role'] ?? '';
if ($formRole === 'patient') session_name('eyecheck_patient');
elseif ($formRole === 'healthcare') session_name('eyecheck_healthcare');
else session_name('eyecheck_default');

if (session_status() === PHP_SESSION_NONE) session_start();
$sessionRole = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;


if (!$sessionRole || !$formRole || $sessionRole !== $formRole || !$user_id) {
    jsonResponse(false, 'Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

// Validate image
$file = $_FILES['eye_image'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) jsonResponse(false, 'Image upload failed.');
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedTypes)) jsonResponse(false, 'Only valid image files are allowed.');
$imageHash = md5_file($file['tmp_name']);
if (!$imageHash) jsonResponse(false, 'Failed to compute image hash.');

// Detect diagnosis
$rawResult = trim($_POST['diagnosis_result'] ?? '');

// Extra fallback: strip emojis from result
$cleanedResult = preg_replace('/[\x{1F600}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $rawResult);
$cleanedResult = trim($cleanedResult);

if (!in_array($cleanedResult, ['Conjunctivitis', 'NonConjunctivitis'])) {
    jsonResponse(false, 'Invalid diagnosis result. Upload rejected.');
}

$diagnosis = $cleanedResult;


// Upload path
$uploadDir = ($formRole === 'patient') ? '../../patient/uploads/' : '../../healthcare/patients/uploads/';
$relativeDir = ($formRole === 'patient') ? 'patient/uploads/' : 'healthcare/patients/uploads/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) jsonResponse(false, 'Failed to create upload directory.');
if (!is_writable($uploadDir)) jsonResponse(false, 'Upload directory is not writable.');
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$fullPath = $uploadDir . $filename;
$relativePath = $relativeDir . $filename;

// PATIENT flow
if ($formRole === 'patient') {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        validateFields(['name', 'contact', 'home_town', 'region', 'gender', 'dob']);
        validateInputFormat([
            'name' => 'name',
            'contact' => 'phone',
            'home_town' => 'town',
            'region' => 'region',
            'gender' => 'gender',
            'dob' => 'date'
        ]);
        $stmt = $pdo->prepare("INSERT INTO patients (user_id, name, contact, town, region, gender, dob, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            clean($_POST['name']),
            clean($_POST['contact']),
            clean($_POST['home_town']),
            clean($_POST['region']),
            clean($_POST['gender']),
            clean($_POST['dob']),
            $user_id
        ]);
        $patientId = $pdo->lastInsertId();
    } else {
        $patientId = $patient['id'];
    }

    if (!$patientId) jsonResponse(false, 'Invalid patient ID.');

    $checkHash = $pdo->prepare("SELECT id FROM patient_uploads WHERE image_hash = ? AND patient_id = ?");
    $checkHash->execute([$imageHash, $patientId]);
    if ($checkHash->rowCount() > 0) jsonResponse(false, 'This image was already uploaded.');

    $stmt = $pdo->prepare("INSERT INTO patient_uploads (patient_id, image_path, image_hash, diagnosis_result, uploaded_by)
                           VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([$patientId, $relativePath, $imageHash, $diagnosis, $user_id]);

    if ($success && move_uploaded_file($file['tmp_name'], $fullPath)) {
        // Log
        logActivity(
            $user_id,
            'patient',
            'UPLOAD_IMAGE',
            "Patient uploaded image: hash=$imageHash, diagnosis=$diagnosis, patient_id=$patientId",
            $patientId
        );

        jsonResponse(true, 'Image uploaded successfully.', 'http://localhost/eyecheck/patient/past-uploads.php');
    } else {
        jsonResponse(false, 'Upload failed or file move failed.');
    }
}

// HEALTHCARE flow
if ($formRole === 'healthcare') {
    validateFields(['name', 'contact', 'home_town', 'region', 'gender', 'dob']);
    validateInputFormat([
        'name' => 'name',
        'contact' => 'phone_or_email',
        'home_town' => 'town',
        'region' => 'region',
        'gender' => 'gender',
        'dob' => 'date'
    ]);

    $name = clean($_POST['name']);
    $dob = clean($_POST['dob']);
    $contact = clean($_POST['contact']);
    $town = clean($_POST['home_town']);
    $region = clean($_POST['region']);
    $gender = clean($_POST['gender']);

    $stmt = $pdo->prepare("SELECT id FROM patients WHERE name = ? AND dob = ? AND contact = ? AND created_by = ?");
    $stmt->execute([$name, $dob, $contact, $user_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patientId = $patient['id'];
    } else {
        $insert = $pdo->prepare("INSERT INTO patients (user_id, name, contact, town, region, gender, dob, created_by)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([null, $name, $contact, $town, $region, $gender, $dob, $user_id]);
        $patientId = $pdo->lastInsertId();
    }

    if (!$patientId) jsonResponse(false, 'Invalid patient ID.');

    $check = $pdo->prepare("
    SELECT 1 FROM patient_uploads
    WHERE uploaded_by = :uid AND image_hash = :hash
    LIMIT 1
    ");
    $check->execute([':uid' => $user_id, ':hash' => $imageHash]);
    if ($check->fetch()) {
        jsonResponse(false, 'You already uploaded this image before.');
    }

    $stmt = $pdo->prepare("INSERT INTO patient_uploads (patient_id, image_path, image_hash, diagnosis_result, uploaded_by)
                           VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([$patientId, $relativePath, $imageHash, $diagnosis, $user_id]);

    if ($success && move_uploaded_file($file['tmp_name'], $fullPath)) {
        logActivity(
            $user_id,
            'healthcare',
            'UPLOAD_IMAGE',
            "Healthcare uploaded image for patient: name=$name, hash=$imageHash, diagnosis=$diagnosis, patient_id=$patientId",
            $patientId
        );

        jsonResponse(true, 'Patient added and image uploaded.', 'http://localhost/eyecheck/healthcare/patients.php');
    } else {
        jsonResponse(false, 'Upload failed or file move failed.');
    }
}

jsonResponse(false, 'Unrecognized role or unexpected error.');

// ====== UTILITY FUNCTIONS ======

function jsonResponse(bool $success, string $message, string $redirect = ''): void {
    echo json_encode(['success' => $success, 'message' => $message, 'redirect' => $redirect]);
    exit();
}

function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateFields(array $required): void {
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse(false, 'All fields are required.');
        }
    }
}

function validateInputFormat(array $fields): void {
    foreach ($fields as $field => $rule) {
        $value = $_POST[$field] ?? '';
        switch ($rule) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(false, "Invalid email format for $field.");
                }
                break;
            case 'phone':
                if (!preg_match('/^\\+?[0-9\\-\\s]{7,20}$/', $value)) {
                    jsonResponse(false, "Invalid phone number for $field.");
                }
                break;
            case 'phone_or_email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) &&
                    !preg_match('/^\\+?[0-9\\-\\s]{7,20}$/', $value)) {
                    jsonResponse(false, "Contact must be a valid phone number or email.");
                }
                break;
            case 'name':
                if (!preg_match('/^[a-zA-Z\\s\'.-]{2,100}$/', $value)) {
                    jsonResponse(false, "Invalid name format.");
                }
                break;
            case 'region':
            case 'town':
                if (!preg_match('/^[a-zA-Z\\s\'-]{2,100}$/', $value)) {
                    jsonResponse(false, "Invalid characters in $field.");
                }
                break;
            case 'gender':
                if (!in_array(strtolower($value), ['male', 'female'])) {
                    jsonResponse(false, 'Gender must be Male or Female.');
                }
                break;

            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    jsonResponse(false, "Invalid date format for $field.");
                }
                if ($field === 'dob') {
                    validateDOB($value, $field); 
                }
                break;
        }
    }
}

function validateDOB(string $dob, string $field = 'dob'): void {
    $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$dobDate) {
        jsonResponse(false, "Invalid date for $field.");
    }

    $today   = new DateTime('today');
    $minAge  = (clone $today)->modify('-30 days'); // exact 30 days

    if ($dobDate > $today) {
        jsonResponse(false, ucfirst($field) . " cannot be in the future.");
    }
    if ($dobDate > $minAge) {
        jsonResponse(false, ucfirst($field) . " must be at least 1 month (30 days) old.");
    }

    $lowerBound = new DateTime('1900-01-01');
    if ($dobDate < $lowerBound) {
        jsonResponse(false, ucfirst($field) . " is too far in the past.");
    }
}

