<?php
$host = 'localhost';
$dbname = 'borrowsmart_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);

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

        INSERT IGNORE INTO roles (name, description) VALUES 
        ('admin', 'System administrator with full access'),
        ('staff', 'Staff member with moderate access'),
        ('student', 'Student with basic access');
    ");

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
