<?php
$host = 'localhost';
$dbname = 'eyecheck';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop and create fresh database
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✅ Database '$dbname' created.<br>";

    $pdo->exec("USE `$dbname`");

    // USERS table (✅ updated with status column)
    $pdo->exec("
       CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            username VARCHAR(100) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            profile_image_hash VARCHAR(32) DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'healthcare', 'patient') NOT NULL DEFAULT 'patient',
            is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
            healthcare_region VARCHAR(100) DEFAULT NULL,
            reset_token VARCHAR(255) DEFAULT NULL,
            reset_expires DATETIME DEFAULT NULL,
            session_id VARCHAR(255) DEFAULT NULL,
            password_updated_at DATETIME DEFAULT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
       );
    ");
    echo "✅ Table 'users' created.<br>";

    // PATIENTS table
    $pdo->exec("
        CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(100) NOT NULL,
            town VARCHAR(100) NOT NULL,
            region VARCHAR(100) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            dob DATE NOT NULL,
            flagged TINYINT(1) DEFAULT 0,
            warnings_sent INT DEFAULT 0,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        );
    ");
    echo "✅ Table 'patients' created.<br>";

    // PATIENT_UPLOADS table
    $pdo->exec("
        CREATE TABLE patient_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            image_hash VARCHAR(32) NOT NULL UNIQUE,
            diagnosis_result VARCHAR(100) DEFAULT 'Pending',
            model_version VARCHAR(50) DEFAULT NULL,
            confidence DECIMAL(5,2) DEFAULT NULL,
            feedback TEXT DEFAULT NULL,
            uploaded_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        );
    ");
    echo "✅ Table 'patient_uploads' created.<br>";

    // ACTIVITY_LOGS table
    $pdo->exec("
        CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );
    ");
    echo "✅ Table 'activity_logs' created.<br>";

    // PENDING_USERS table
    $pdo->exec("
       CREATE TABLE pending_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL
        );
    ");
    echo "✅ Table 'pending_users' created.<br>";

    // MODEL_PREDICTIONS table
    $pdo->exec("
        CREATE TABLE model_predictions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            result VARCHAR(100) NOT NULL,
            model_version VARCHAR(50),
            confidence DECIMAL(5,2),
            feedback TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        );
    ");
    echo "✅ Table 'model_predictions' created.<br>";

    echo "<br><b>🎉 Setup complete. System is ready!</b>";

} catch (PDOException $e) {
    die('❌ Setup failed: ' . $e->getMessage());
}
?>