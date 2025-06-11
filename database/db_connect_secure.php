<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';

// Configure secure session
configureSecureSession();
session_start();

// Check session timeout
if (isset($_SESSION['user_id'])) {
    checkSessionTimeout();
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(20) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(10) UNIQUE NOT NULL COMMENT 'Matric number (e.g., AI220000) or Staff ID',
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role_id INT NOT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            last_login TIMESTAMP NULL,
            login_attempts INT DEFAULT 0,
            lockout_time TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        );

        CREATE TABLE IF NOT EXISTS instruments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            category ENUM('brass', 'woodwind', 'percussion') NOT NULL,
            description TEXT,
            quantity INT NOT NULL DEFAULT 1,
            available_quantity INT NOT NULL DEFAULT 1,
            status ENUM('available', 'borrowed', 'maintenance') DEFAULT 'available',
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS borrowing_records (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            instrument_id INT NOT NULL,
            borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expected_return_date DATE NOT NULL,
            actual_return_date DATE,
            status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (instrument_id) REFERENCES instruments(id)
        );

        CREATE TABLE IF NOT EXISTS login_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            ip_address VARCHAR(45) NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE,
            user_agent VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        INSERT IGNORE INTO roles (name, description) VALUES 
        ('admin', 'System administrator with full access'),
        ('staff', 'Staff member with moderate access'),
        ('student', 'Student with basic access');
    ");

} catch(PDOException $e) {
    // Log error securely without exposing sensitive information
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}
