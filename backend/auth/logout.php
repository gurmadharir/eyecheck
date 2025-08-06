<?php
// ✅ Step 1: Detect role based on `eyecheck_role` cookie or fallback from HTTP_REFERER
$role = $_COOKIE['eyecheck_role'] ?? '';

if (!$role) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (str_contains($referer, '/admin/')) {
        $role = 'admin';
    } elseif (str_contains($referer, '/healthcare/')) {
        $role = 'healthcare';
    } elseif (str_contains($referer, '/patient/')) {
        $role = 'patient';
    } else {
        $role = 'default';
    }
}

// ✅ Step 2: Set correct session name
$validRoles = ['admin', 'healthcare', 'patient'];
$sessionName = in_array($role, $validRoles) ? "eyecheck_$role" : "eyecheck_default";
session_name($sessionName);

// ✅ Step 3: Start session and log logout
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log-activity.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;

if ($user_id && $user_role) {
    logActivity($user_id, $user_role, 'LOGOUT', ucfirst($user_role) . " '$username' logged out.");
}

// ✅ Step 4: Destroy session and cookies
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], false, true);
}
setcookie("eyecheck_role", '', time() - 3600, "/");
session_destroy();

// ✅ Step 5: Redirect to correct login page
$redirectPath = "/eyecheck/" . ($role !== 'default' ? "$role/login.php" : "login.php");
header("Location: $redirectPath");
exit;
