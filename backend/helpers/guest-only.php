<?php
// Auto-detect session using cookies
if (isset($_COOKIE['eyecheck_admin'])) {
  session_name('eyecheck_admin');
} elseif (isset($_COOKIE['eyecheck_healthcare'])) {
  session_name('eyecheck_healthcare');
} elseif (isset($_COOKIE['eyecheck_patient'])) {
  session_name('eyecheck_patient');
} else {
  session_name('eyecheck_default');
}
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
  $role = $_SESSION['role'];
  header("Location: /eyecheck/$role/dashboard.php");
  exit;
}
