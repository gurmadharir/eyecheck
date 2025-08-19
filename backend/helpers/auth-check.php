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

/**
 * ✅ NEW: Block deactivated accounts even if they have a session.
 * This ensures a user who was deactivated after logging in gets kicked out on the next request.
 */
try {
    require_once __DIR__ . '/../../config/db.php'; // PDO $pdo
    $check = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $check->execute([$_SESSION['user_id']]);
    $active = (int)$check->fetchColumn();

    if ($active !== 1) {
        // End session
        $currentSessionName = session_name();
        session_unset();
        session_destroy();

        // Optionally expire role cookie and this session cookie for cleanliness
        if (isset($_COOKIE['eyecheck_role'])) {
            setcookie('eyecheck_role', '', time() - 3600, '/', '', false, true);
        }
        if (isset($_COOKIE[$currentSessionName])) {
            setcookie($currentSessionName, '', time() - 3600, '/', '', false, true);
        }

        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Your account is deactivated. Please contact support.']);
        } else {
            $redirRole = strtolower($_SESSION['role'] ?? $roleFromCookie ?: 'admin');
            header("Location: /eyecheck/{$redirRole}/login.php?inactive=1");
        }
        exit();
    }
} catch (Throwable $e) {
    // Fail-safe: if DB check fails, proceed without blocking to avoid breaking pages,
    // but you can log the error for visibility.
    file_put_contents(__DIR__ . '/auth-debug.log', "is_active check error: " . $e->getMessage() . "\n", FILE_APPEND);
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
