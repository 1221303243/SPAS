-- SPAS (Student Performance Assessment System) Complete Database Schema
-- This file contains all tables, relationships, and sample data for the SPAS system

-- Create database
CREATE DATABASE IF NOT EXISTS SPAS;
USE SPAS;

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS assessment_plans;
DROP TABLE IF EXISTS calendar_events;
DROP TABLE IF EXISTS student_classes;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS lecturers;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS current_semester;

-- Create users table (main authentication table)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Create students table
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    date_of_birth DATE,
    grade_level INT,
    edu_level ENUM('Foundation', 'Diploma', 'Undergraduate', 'Postgraduate') DEFAULT 'Undergraduate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_edu_level (edu_level)
);

-- Create lecturers table
CREATE TABLE lecturers (
    lecturer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Create admin table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Create current_semester table (for trimester/semester management)
CREATE TABLE current_semester (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trimester_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
);

-- Create subjects table
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    assessment_type ENUM('coursework_final_exam', 'coursework_only') DEFAULT 'coursework_final_exam',
    edu_level ENUM('Foundation', 'Diploma', 'Undergraduate', 'Postgraduate') DEFAULT 'Undergraduate',
    lecturer_id INT,
    trimester_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id) ON DELETE SET NULL,
    FOREIGN KEY (trimester_id) REFERENCES current_semester(id) ON DELETE SET NULL,
    INDEX idx_subject_code (subject_code),
    INDEX idx_assessment_type (assessment_type),
    INDEX idx_edu_level (edu_level),
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_trimester_id (trimester_id)
);

-- Create classes table
CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    edu_level ENUM('Foundation', 'Diploma', 'Undergraduate', 'Postgraduate') DEFAULT 'Undergraduate',
    semester VARCHAR(20),
    year INT,
    subject_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id) ON DELETE CASCADE,
    INDEX idx_subject_id (subject_id),
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_edu_level (edu_level)
);

-- Create student_classes table (junction table for many-to-many relationship)
CREATE TABLE student_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_class (class_id, student_id),
    INDEX idx_class_id (class_id),
    INDEX idx_student_id (student_id)
);

-- Create assessment_plans table
CREATE TABLE assessment_plans (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    assessment_type VARCHAR(100) NOT NULL,
    category ENUM('coursework', 'final_exam') NOT NULL,
    weightage INT NOT NULL,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT weightage_range CHECK (weightage >= 0 AND weightage <= 100),
    UNIQUE KEY unique_assessment (subject_id, assessment_type),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    INDEX idx_subject_id (subject_id),
    INDEX idx_category (category),
    INDEX idx_due_date (due_date)
);

-- Create grades table
CREATE TABLE grades (
    grade_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    assessment_id INT NOT NULL,
    class_id INT NOT NULL,
    marks DECIMAL(5,2) NOT NULL,
    weighted_marks DECIMAL(5,2) DEFAULT NULL,
    total_marks DECIMAL(5,2) DEFAULT NULL,
    category ENUM('coursework', 'final_exam') NOT NULL,
    coursework_total DECIMAL(5,2) DEFAULT NULL,
    final_exam_total DECIMAL(5,2) DEFAULT NULL,
    grade CHAR(2) DEFAULT NULL,
    date_recorded DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessment_plans(assessment_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade (student_id, assessment_id, class_id),
    INDEX idx_student_id (student_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_assessment_id (assessment_id),
    INDEX idx_class_id (class_id),
    INDEX idx_total_marks (total_marks),
    INDEX idx_date_recorded (date_recorded)
);

-- Create calendar_events table
CREATE TABLE calendar_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    event_text TEXT NOT NULL,
    subject_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_date_subject (event_date, subject_id),
    INDEX idx_event_date (event_date),
    INDEX idx_subject_id (subject_id)
);

-- Create feedback table
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    grade_id INT NOT NULL,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    grade_justification TEXT,
    general_comments TEXT,
    rubric_score JSON,
    feedback_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    feedback_status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    FOREIGN KEY (grade_id) REFERENCES grades(grade_id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessment_plans(assessment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id) ON DELETE CASCADE,
    INDEX idx_student_assessment (student_id, assessment_id),
    INDEX idx_class_assessment (class_id, assessment_id),
    INDEX idx_feedback_date (feedback_date),
    INDEX idx_feedback_status (feedback_status)
);

-- Create triggers for assessment_plans weightage validation
DELIMITER //
CREATE TRIGGER check_category_weightage
BEFORE INSERT ON assessment_plans
FOR EACH ROW
BEGIN
    DECLARE total_weight INT;
    SELECT COALESCE(SUM(weightage), 0) INTO total_weight
    FROM assessment_plans
    WHERE subject_id = NEW.subject_id AND category = NEW.category;
    
    IF total_weight + NEW.weightage > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Total weightage for this category cannot exceed 100%';
    END IF;
END//

CREATE TRIGGER check_category_weightage_update
BEFORE UPDATE ON assessment_plans
FOR EACH ROW
BEGIN
    DECLARE total_weight INT;
    SELECT COALESCE(SUM(weightage), 0) INTO total_weight
    FROM assessment_plans
    WHERE subject_id = NEW.subject_id 
    AND category = NEW.category
    AND assessment_id != NEW.assessment_id;
    
    IF total_weight + NEW.weightage > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Total weightage for this category cannot exceed 100%';
    END IF;
END//
DELIMITER ;

-- Create view for feedback summary
CREATE VIEW feedback_summary AS
SELECT 
    f.feedback_id,
    f.grade_id,
    f.assessment_id,
    f.student_id,
    f.class_id,
    f.lecturer_id,
    ap.assessment_type,
    ap.category,
    ap.subject_id,
    g.marks,
    g.weighted_marks,
    f.strengths,
    f.areas_for_improvement,
    f.recommendations,
    f.grade_justification,
    f.general_comments,
    f.feedback_date,
    f.feedback_status,
    s.name AS student_name,
    l.name AS lecturer_name
FROM feedback f
JOIN grades g ON f.grade_id = g.grade_id
JOIN assessment_plans ap ON f.assessment_id = ap.assessment_id
JOIN students s ON f.student_id = s.student_id
JOIN lecturers l ON f.lecturer_id = l.lecturer_id; 