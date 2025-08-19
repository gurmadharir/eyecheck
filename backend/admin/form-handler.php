<?php
declare(strict_types=1);

session_name("eyecheck_admin");
session_start();
require_once '../../config/db.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$isEdit = isset($_POST['id']) && is_numeric($_POST['id']);
$id = $isEdit ? (int)$_POST['id'] : null;

function respond($success, $message, $redirect = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Invalid request method.");
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    respond(false, "Unauthorized access.");
}

/* ---------------- Helpers for username generation ---------------- */

function slugify_simple(string $text): string {
    // Best-effort transliteration
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) $text = $converted;
    }
    $text = strtolower($text);
    // keep only a-z 0-9 and dots (you allowed dots in your regex)
    $text = preg_replace('/[^a-z0-9.]+/', '', $text) ?? '';
    return $text !== '' ? $text : 'user';
}

function first_name_base(string $fullName): string {
    $first = strtok(trim($fullName), " \t\n\r\0\x0B") ?: $fullName;
    return slugify_simple($first);
}

/**
 * Generate unique username like: base, base2, base3, ...
 * Uses all usernames starting with base to find the next free numeric suffix.
 */
function generate_unique_username_from_base(PDO $pdo, string $base): string {
    $base = slugify_simple($base);

    // Fetch all that start with base
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username LIKE ?");
    $stmt->execute([$base . '%']);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    if (!$existing) {
        return $base;
    }

    // if base itself not taken, use it
    if (!in_array($base, $existing, true)) {
        return $base;
    }

    // Scan numeric suffixes
    $max = 1; // since base taken, next should be 2+
    foreach ($existing as $u) {
        if (strpos($u, $base) !== 0) continue;
        $suffix = substr($u, strlen($base)); // '' or '2' or '10'
        if ($suffix === '') { $max = max($max, 1); continue; }
        if (ctype_digit($suffix)) {
            $num = (int)$suffix;
            $max = max($max, $num);
        }
    }

    // propose next
    $candidate = $base . (string)($max + 1);

    // tiny safety loop in case of race
    $check = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $i = $max + 1;
    while (true) {
        $check->execute([$candidate]);
        if (!$check->fetchColumn()) break;
        $i++;
        $candidate = $base . (string)$i;
        if ($i > $max + 1000) {
            $candidate = $base . (string)random_int(1000, 9999);
            $check->execute([$candidate]);
            if (!$check->fetchColumn()) break;
        }
    }

    return $candidate;
}

/**
 * Ensure a desired username is unique; if taken, auto-number from that desired base.
 * If desired is empty, generate from first name.
 */
function ensure_final_username(PDO $pdo, string $desired, string $fullName): string {
    $desired = trim($desired);

    if ($desired !== '') {
        $base = slugify_simple($desired);
    } else {
        $base = first_name_base($fullName);
    }

    return generate_unique_username_from_base($pdo, $base);
}

/* ---------------- Collect inputs ---------------- */

$role       = strtolower(trim($_POST['role'] ?? ''));
$full_name  = trim($_POST['full_name'] ?? '');
$username   = trim($_POST['username'] ?? ''); // may be blank; we can auto
$email      = trim($_POST['email'] ?? '');
$region     = trim($_POST['region'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

$errors = [];

/* ---------------- Core validation ---------------- */
// Don’t *require* username; we can generate if missing
if (!$role || !$full_name || !$email) {
    $errors[] = "Role, full name, and email are required.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}
// If username provided, validate it. If blank, we’ll generate it later.
if ($username !== '' && !preg_match('/^[a-zA-Z0-9.]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and dots.";
}
if (!preg_match("/^[a-zA-Z' ]+$/", $full_name)) {
    $errors[] = "Full name must contain only letters, spaces, or apostrophes.";
}
if ($role === 'healthcare' && empty($region)) {
    $errors[] = "Region is required for healthcare staff.";
}

/* ---------------- Super admin rule ---------------- */
$specialAdmin = ['username' => 'superadmin', 'email' => 'eyecheckhealthcare@gmail.com'];
$currentUserStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$currentUserStmt->execute([$_SESSION['user_id']]);
$currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);

$isSuperAdmin = $currentUser && (
    $currentUser['username'] === $specialAdmin['username'] ||
    $currentUser['email'] === $specialAdmin['email']
);
if ($role === 'admin' && !$isSuperAdmin) {
    $errors[] = "Only the super administrator can manage admin accounts.";
}

/* ---------------- Password validation (create only) ---------------- */
if (!$isEdit) {
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    $weak = ['123456', 'password', '123456789', 'qwerty', 'abc123', '000000'];
    if (in_array(strtolower($password), $weak, true)) {
        $errors[] = "Weak password. Choose a stronger one.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
}

/* ---------------- Email duplicates (still block) ---------------- */
try {
    if ($isEdit) {
        $checkEmail = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $checkEmail->execute([$email, $id]);
        if ($checkEmail->fetchColumn()) {
            $errors[] = "Email already exists.";
        }
    } else {
        $checkEmail = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetchColumn()) {
            $errors[] = "Email already exists.";
        }
    }
} catch (PDOException $e) {
    respond(false, "Database error (email check): " . $e->getMessage());
}

/* ---------------- Bail early if any errors ---------------- */
if (!empty($errors)) {
    respond(false, implode("<br>", $errors));
}

/* ---------------- Proceed (auto username if needed) ---------------- */
try {
    if ($isEdit) {
        // Load existing (to compare)
        $stmt = $pdo->prepare("SELECT role, full_name, username, email, healthcare_region FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            respond(false, "User not found.");
        }

        // Decide final username: ensure uniqueness (auto-number if taken by another)
        $finalUsername = ensure_final_username($pdo, $username, $full_name);

        // If nothing changed (including username after normalization), short-circuit
        if (
            $existing['role'] === $role &&
            $existing['full_name'] === $full_name &&
            $existing['username'] === $finalUsername &&
            $existing['email'] === $email &&
            ($role !== 'healthcare' || $existing['healthcare_region'] === $region)
        ) {
            respond(false, "No changes detected.");
        }

        // Update (no password changes here)
        $upd = $pdo->prepare("
            UPDATE users SET 
                role = :role,
                full_name = :full_name,
                username = :username,
                email = :email,
                healthcare_region = :region
            WHERE id = :id
        ");
        $upd->execute([
            ':role' => $role,
            ':full_name' => $full_name,
            ':username' => $finalUsername,
            ':email' => $email,
            ':region' => $role === 'healthcare' ? $region : null,
            ':id' => $id
        ]);

        respond(true, "Staff updated successfully.", "./manage.php");

    } else {
        // Create
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // If username blank or taken, ensure unique using base (desired or first name)
        $finalUsername = ensure_final_username($pdo, $username, $full_name);

        $ins = $pdo->prepare("
            INSERT INTO users (role, full_name, username, email, healthcare_region, password)
            VALUES (:role, :full_name, :username, :email, :region, :password)
        ");

        // Attempt insert; if UNIQUE(username) race happens, bump again and retry once
        try {
            $ins->execute([
                ':role' => $role,
                ':full_name' => $full_name,
                ':username' => $finalUsername,
                ':email' => $email,
                ':region' => $role === 'healthcare' ? $region : null,
                ':password' => $hash
            ]);
        } catch (PDOException $e) {
            $code = $e->errorInfo[1] ?? null;
            if ($code == 1062 /* MySQL duplicate */ || $code == 23505 /* Postgres duplicate */) {
                // bump once more
                $base = $finalUsername; // already slugified
                // remove trailing digits to get clean base if admin typed e.g. qudus7
                if (preg_match('/^([a-z0-9.]*?)(\d+)$/', $base, $m)) {
                    $base = $m[1];
                }
                $finalUsername = generate_unique_username_from_base($pdo, $base);
                $ins->execute([
                    ':role' => $role,
                    ':full_name' => $full_name,
                    ':username' => $finalUsername,
                    ':email' => $email,
                    ':region' => $role === 'healthcare' ? $region : null,
                    ':password' => $hash
                ]);
            } else {
                throw $e;
            }
        }

        respond(true, ucfirst($role) . " account created successfully.", "./manage.php");
    }

} catch (PDOException $e) {
    respond(false, "Database error: " . $e->getMessage());
} catch (Throwable $e) {
    respond(false, "Server error: " . $e->getMessage());
}
