-- Create database
CREATE DATABASE IF NOT EXISTS borrowsmart_db;
USE borrowsmart_db;

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
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

-- Create instruments table
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

-- Create borrowing_records table
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

-- Insert default roles
INSERT INTO roles (name, description) VALUES 
('admin', 'System administrator with full access'),
('staff', 'Staff member with moderate access'),
('student', 'Student with basic access');

-- Insert default admin and staff accounts
-- Default password for both accounts is: Admin@123
INSERT INTO users (username, email, password, full_name, role_id) VALUES 
('ADMIN0001', 'admin@uthm.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1),
('STAFF0001', 'staff@uthm.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Member', 2);

-- Insert actual instruments with quantities
INSERT INTO instruments (name, category, description, quantity, available_quantity, status) VALUES 
-- Brass Instruments
('Trumpet', 'brass', 'Standard B♭ Trumpet suitable for band and orchestra use', 12, 12, 'available'),
('French Horn', 'brass', 'Double French Horn in F/B♭', 2, 2, 'available'),
('Trombone', 'brass', 'Tenor Trombone with F attachment', 10, 10, 'available'),
('Tuba', 'brass', 'Standard B♭ Tuba', 2, 2, 'available'),
('Euphonium', 'brass', 'Standard B♭ Euphonium', 2, 2, 'available'),

-- Woodwind Instruments
('Flute', 'woodwind', 'Standard C Flute with closed hole system', 2, 2, 'available'),
('Clarinet', 'woodwind', 'B♭ Clarinet suitable for beginners and intermediate players', 5, 5, 'available'),
('Saxophone', 'woodwind', 'Alto Saxophone in E♭', 5, 5, 'available'),

-- Percussion Instruments
('Big Drum', 'percussion', 'Large Bass Drum for band use', 1, 1, 'available'),
('Cymbals', 'percussion', 'Pair of 18" Crash Cymbals', 1, 1, 'available'),
('Snare Drum', 'percussion', 'Standard 14" Snare Drum with stand', 4, 4, 'available');

-- Create indexes for better performance
CREATE INDEX idx_user_role ON users(role_id);
CREATE INDEX idx_borrowing_user ON borrowing_records(user_id);
CREATE INDEX idx_borrowing_instrument ON borrowing_records(instrument_id);
CREATE INDEX idx_borrowing_status ON borrowing_records(status);
CREATE INDEX idx_instrument_status ON instruments(status);
CREATE INDEX idx_instrument_category ON instruments(category);
