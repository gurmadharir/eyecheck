<?php
// File: /eyecheck/backend/auth/logout.php
// One universal logout: clears all possible EyeCheck sessions, no role needed.

declare(strict_types=1);
date_default_timezone_set('Africa/Mogadishu');

// (Optional) activity logger, only if you want to keep logout logs
$logActivity = function($user_id, $user_role, $username, $action, $details) {
    $dbPath = __DIR__ . '/../../config/db.php';
    $helperPath = __DIR__ . '/../helpers/log-activity.php';
    if (is_file($dbPath) && is_file($helperPath)) {
        require_once $dbPath;
        require_once $helperPath;
        try {
            logActivity((int)$user_id, (string)$user_role, $action, $details);
        } catch (Throwable $t) {
            // swallow logging errors
        }
    }
};

// All possible session names we’ve ever used
$sessionNames = [
    'eyecheck_admin',
    'eyecheck_healthcare',
    'eyecheck_patient',
    'eyecheck_default',
    'eyecheck', 
];

// Loop through known sessions and nuke them if present
foreach ($sessionNames as $name) {
    if (!isset($_COOKIE[$name])) {
        continue; // nothing to clear for this session name
    }

    // Switch to this session namespace and open it
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    session_name($name);
    session_id($_COOKIE[$name]);
    session_start();

    // (Optional) log the logout if we can read the session fields
    $user_id  = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? 'unknown';
    $username = $_SESSION['username'] ?? 'unknown';
    if ($user_id) {
        $logActivity($user_id, $user_role, $username, 'LOGOUT', "User '$username' logged out.");
    }

    // Clear and destroy this session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            $name,
            '',
            time() - 42000,
            $params['path'] ?: '/',
            $params['domain'] ?? '',
            false, // secure (set true if using HTTPS)
            true   // httponly
        );
    }
    session_destroy();

    // Also clear the cookie by a direct path-wide delete (belt & suspenders)
    setcookie($name, '', time() - 3600, '/', '', false, true);
}

// Clear any role-hint cookie if you had one
setcookie('eyecheck_role', '', time() - 3600, '/', '', false, true);

// Redirect to a single landing page (change if you prefer login page)
$redirectPath = '/eyecheck/logout.php'; // or '/eyecheck/login.php'
header("Location: $redirectPath");
exit;
