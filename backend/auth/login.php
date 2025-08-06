<?php
require_once '../../config/db.php';
require_once '../helpers/log-activity.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = $_POST['remember'] ?? '0';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // ✅ Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit;
    }

    $role = $user['role'];
    $allowedRoles = ['admin', 'healthcare', 'patient'];
    if (!in_array($role, $allowedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid user role.']);
        exit;
    }

    // ✅ Destroy any existing sessions before setting new one
    foreach ($allowedRoles as $r) {
        $cookie = "eyecheck_$r";
        if (isset($_COOKIE[$cookie])) {
            // Expire old cookie
            setcookie($cookie, '', time() - 3600, '/');
            // Kill session file
            session_name($cookie);
            session_start();
            session_destroy();
        }
    }

    // ✅ Also remove generic PHPSESSID if exists
    if (isset($_COOKIE['PHPSESSID'])) {
        setcookie('PHPSESSID', '', time() - 3600, '/');
    }

    // ✅ Set session name and cookie params BEFORE session_start()
    session_name("eyecheck_$role");
    $cookie_lifetime = $remember === '1' ? (60 * 60 * 24 * 30) : 0;
    session_set_cookie_params([
        'lifetime' => $cookie_lifetime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
    session_regenerate_id(true);

    // ✅ Save session_id in DB (optional)
    $updateSession = $pdo->prepare("UPDATE users SET session_id = ? WHERE id = ?");
    $updateSession->execute([session_id(), $user['id']]);

    // ✅ Save session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image'];
    $_SESSION['is_super_admin'] = (int)$user['is_super_admin'];

    // ✅ Set separate role cookie to help auth-check
    setcookie("eyecheck_role", $role, [
        'expires' => time() + 86400,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax'
    ]);

    // ✅ Log activity
    logActivity($user['id'], $user['role'], 'LOGIN', ucfirst($user['role']) . " '{$user['username']}' logged in");

    // ✅ Finalize session and respond
    session_write_close();
    echo json_encode(['success' => true, 'redirect' => "/eyecheck/$role/dashboard.php"]);
    exit;
}
?>
