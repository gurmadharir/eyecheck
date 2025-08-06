<?php
global $conn;

$host = 'localhost';
$dbname = 'eyecheck';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+03:00'");

} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
