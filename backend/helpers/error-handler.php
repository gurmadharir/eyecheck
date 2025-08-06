<?php
// Error handler for system-level PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$user_id, &$role) {
    $msg = "PHP ERROR [$errno] in $errfile:$errline - $errstr";
    error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, __DIR__ . '/../../logs/system_errors.log');

    if (!empty($user_id) && in_array($role, ['patient', 'healthcare', 'admin'])) {
        require_once __DIR__ . '/log-activity.php';
        logActivity($user_id, $role, 'SYSTEM_ERROR', $msg);
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "A server error occurred."]);
    exit();
});

set_exception_handler(function($e) use (&$user_id, &$role) {
    $msg = "UNCAUGHT EXCEPTION: " . $e->getMessage();
    error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, __DIR__ . '/../../logs/system_errors.log');

    if (!empty($user_id) && in_array($role, ['patient', 'healthcare', 'admin'])) {
        require_once __DIR__ . '/log-activity.php';
        logActivity($user_id, $role, 'SYSTEM_ERROR', $msg);
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "An unexpected error occurred."]);
    exit();
});