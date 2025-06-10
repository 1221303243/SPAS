-- SPAS Admin Database Tables
-- Run this in phpMyAdmin to create all necessary tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS SPAS;
USE SPAS;

-- Admin Users Table
CREATE TABLE admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    department VARCHAR(100),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- Users Table (for students, lecturers, etc.)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_code VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('student', 'lecturer', 'admin') NOT NULL,
    department VARCHAR(100),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    avatar_url VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_department (department)
);

-- Subjects Table
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT NOT NULL DEFAULT 3,
    category ENUM('core', 'elective', 'laboratory') DEFAULT 'core',
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    department VARCHAR(100),
    lecturer_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_subject_code (subject_code),
    INDEX idx_category (category),
    INDEX idx_level (level),
    INDEX idx_status (status)
);

-- Classes Table
CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_code VARCHAR(20) UNIQUE NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    subject_id INT NOT NULL,
    lecturer_id INT,
    room VARCHAR(50),
    schedule VARCHAR(100),
    max_students INT DEFAULT 30,
    current_students INT DEFAULT 0,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_class_code (class_code),
    INDEX idx_subject_id (subject_id),
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_status (status)
);

-- Class Enrollments Table
CREATE TABLE class_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    grade VARCHAR(5),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (class_id, student_id),
    INDEX idx_class_id (class_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- System Activity Log Table
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- System Settings Table
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default admin user
INSERT INTO admin_users (username, email, password_hash, first_name, last_name, role, department, status) VALUES
('admin', 'admin@spas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'super_admin', 'IT Department', 'active');

-- Insert sample system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'SPAS - Student Performance Assessment System', 'string', 'Website name', TRUE),
('site_description', 'A comprehensive student performance assessment system', 'string', 'Website description', TRUE),
('max_file_size', '5242880', 'integer', 'Maximum file upload size in bytes', FALSE),
('session_timeout', '3600', 'integer', 'Session timeout in seconds', FALSE),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status', TRUE),
('email_settings', '{"smtp_host":"","smtp_port":"","smtp_username":"","smtp_password":""}', 'json', 'Email configuration', FALSE);

-- Insert sample subjects
INSERT INTO subjects (subject_code, subject_name, description, credits, category, level, department) VALUES
('MATH101', 'Introduction to Calculus', 'Algebra, Geometry, Calculus fundamentals', 3, 'core', 'beginner', 'Mathematics'),
('PHYS101', 'Physics Fundamentals', 'Physics, Chemistry, Biology basics', 4, 'core', 'beginner', 'Science'),
('ENG101', 'English Composition', 'Literature, Grammar, Writing skills', 3, 'core', 'beginner', 'English'),
('HIST101', 'World History', 'World History, Local History studies', 3, 'elective', 'beginner', 'History'),
('CHEM101', 'General Chemistry', 'General Chemistry with laboratory work', 4, 'core', 'intermediate', 'Science'),
('BIO101', 'Biology Basics', 'Biology Basics with practical sessions', 4, 'core', 'beginner', 'Science');

-- Insert sample users
INSERT INTO users (user_code, username, email, password_hash, first_name, last_name, phone, role, department, status) VALUES
('USR001', 'john.legend', 'john.legend@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Legend', '+1234567890', 'admin', 'IT Department', 'active'),
('USR002', 'sarah.johnson', 'sarah.johnson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '+1234567891', 'lecturer', 'Mathematics', 'active'),
('USR003', 'michael.brown', 'michael.brown@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', '+1234567892', 'student', 'Computer Science', 'active'),
('USR004', 'emily.davis', 'emily.davis@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily', 'Davis', '+1234567893', 'lecturer', 'Physics', 'inactive'),
('USR005', 'david.wilson', 'david.wilson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Wilson', '+1234567894', 'student', 'Engineering', 'active'),
('USR006', 'lisa.anderson', 'lisa.anderson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Anderson', '+1234567895', 'lecturer', 'English', 'active');

-- Insert sample classes
INSERT INTO classes (class_code, class_name, subject_id, lecturer_id, room, schedule, max_students, current_students, semester, academic_year, status) VALUES
('MATH101-001', 'Introduction to Calculus', 1, 2, 'Room 201', 'Mon, Wed, Fri 9:00 AM', 25, 25, 'Fall 2024', '2024-2025', 'active'),
('PHYS101-001', 'Physics Fundamentals', 2, 4, 'Lab 105', 'Tue, Thu 10:30 AM', 30, 30, 'Fall 2024', '2024-2025', 'active'),
('ENG101-001', 'English Composition', 3, 6, 'Room 301', 'Mon, Wed 2:00 PM', 20, 20, 'Fall 2024', '2024-2025', 'active'),
('HIST101-001', 'World History', 4, 2, 'Room 401', 'Tue, Thu 1:00 PM', 28, 28, 'Fall 2024', '2024-2025', 'inactive'),
('CHEM101-001', 'General Chemistry', 5, 4, 'Lab 201', 'Mon, Wed, Fri 11:00 AM', 35, 35, 'Fall 2024', '2024-2025', 'active'),
('BIO101-001', 'Biology Basics', 6, 6, 'Lab 301', 'Tue, Thu 3:30 PM', 22, 22, 'Fall 2024', '2024-2025', 'active');

-- Update subject lecturer assignments
UPDATE subjects SET lecturer_id = 2 WHERE subject_code = 'MATH101';
UPDATE subjects SET lecturer_id = 4 WHERE subject_code = 'PHYS101';
UPDATE subjects SET lecturer_id = 6 WHERE subject_code = 'ENG101';
UPDATE subjects SET lecturer_id = 2 WHERE subject_code = 'HIST101';
UPDATE subjects SET lecturer_id = 4 WHERE subject_code = 'CHEM101';
UPDATE subjects SET lecturer_id = 6 WHERE subject_code = 'BIO101';

-- Insert sample enrollments
INSERT INTO class_enrollments (class_id, student_id, status, grade) VALUES
(1, 3, 'enrolled', 'A'),
(1, 5, 'enrolled', 'B+'),
(2, 3, 'enrolled', 'A-'),
(2, 5, 'enrolled', 'B'),
(3, 3, 'enrolled', 'A'),
(3, 5, 'enrolled', 'A-');

-- Create views for easier data access
CREATE VIEW v_user_summary AS
SELECT 
    user_id,
    user_code,
    CONCAT(first_name, ' ', last_name) as full_name,
    email,
    role,
    department,
    status,
    created_at
FROM users;

CREATE VIEW v_class_summary AS
SELECT 
    c.class_id,
    c.class_code,
    c.class_name,
    s.subject_name,
    CONCAT(u.first_name, ' ', u.last_name) as lecturer_name,
    c.room,
    c.schedule,
    c.current_students,
    c.max_students,
    c.status
FROM classes c
JOIN subjects s ON c.subject_id = s.subject_id
LEFT JOIN users u ON c.lecturer_id = u.user_id;

CREATE VIEW v_subject_summary AS
SELECT 
    s.subject_id,
    s.subject_code,
    s.subject_name,
    s.description,
    s.credits,
    s.category,
    s.level,
    s.department,
    CONCAT(u.first_name, ' ', u.last_name) as lecturer_name,
    s.status
FROM subjects s
LEFT JOIN users u ON s.lecturer_id = u.user_id;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetUserStats()
BEGIN
    SELECT 
        role,
        status,
        COUNT(*) as count
    FROM users 
    GROUP BY role, status;
END //

CREATE PROCEDURE GetClassStats()
BEGIN
    SELECT 
        c.status,
        COUNT(*) as total_classes,
        SUM(c.current_students) as total_students,
        AVG(c.current_students) as avg_class_size
    FROM classes c
    GROUP BY c.status;
END //

CREATE PROCEDURE GetSubjectStats()
BEGIN
    SELECT 
        category,
        level,
        COUNT(*) as count
    FROM subjects 
    GROUP BY category, level;
END //

DELIMITER ;

-- Create triggers for activity logging
DELIMITER //

CREATE TRIGGER log_user_changes
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values)
    VALUES (
        NEW.user_id,
        'UPDATE',
        'users',
        NEW.user_id,
        JSON_OBJECT(
            'first_name', OLD.first_name,
            'last_name', OLD.last_name,
            'email', OLD.email,
            'status', OLD.status
        ),
        JSON_OBJECT(
            'first_name', NEW.first_name,
            'last_name', NEW.last_name,
            'email', NEW.email,
            'status', NEW.status
        )
    );
END //

CREATE TRIGGER log_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values)
    VALUES (
        NEW.user_id,
        'INSERT',
        'users',
        NEW.user_id,
        JSON_OBJECT(
            'first_name', NEW.first_name,
            'last_name', NEW.last_name,
            'email', NEW.email,
            'role', NEW.role
        )
    );
END //

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON SPAS.* TO 'spas_user'@'localhost';
-- FLUSH PRIVILEGES; 