<?php
// Detect if this is an API call (to return JSON)
$isApi = str_contains($_SERVER['REQUEST_URI'], 'delete-handler.php') ||
         (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

// ✅ Load correct session name using cookie
$roleFromCookie = $_COOKIE['eyecheck_role'] ?? '';
$validRoles = ['admin', 'healthcare', 'patient'];

if (in_array($roleFromCookie, $validRoles)) {
    session_name("eyecheck_$roleFromCookie");
} else {
    session_name('eyecheck_default'); // fallback for broken or missing role
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();  // loads session based on the name above
}

// ✅ Log for debugging
file_put_contents(__DIR__ . '/auth-debug.log', json_encode([
    'session_name' => session_name(),
    'session_id' => session_id(),
    'session' => $_SESSION,
    'cookies' => $_COOKIE,
    'uri' => $_SERVER['REQUEST_URI'],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// ✅ Block unauthenticated users
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    $redirectPath = "/eyecheck/{$roleFromCookie}/login.php";
    header("Location: $redirectPath");
    exit();
}

// ✅ Require specific role
function requireRole(string $requiredRole) {
    global $isApi;
    $actual = strtolower($_SESSION['role'] ?? 'guest');
    $required = strtolower($requiredRole);

    if ($actual !== $required) {
        if ($isApi) {
            echo json_encode(['success' => false, 'message' => 'Forbidden: role mismatch']);
        } else {
            header("Location: /eyecheck/$required/login.php");
        }
        exit();
    }
}
