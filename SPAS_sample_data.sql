-- SPAS Sample Data
-- This file contains sample data for testing the SPAS system

USE SPAS;

-- Insert sample users
INSERT INTO users (username, email, password, role) VALUES 
('admin1', 'admin@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('lecturer1', 'lecturer1@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('lecturer2', 'lecturer2@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('student1', 'student1@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('student2', 'student2@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('student3', 'student3@spas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert sample admin
INSERT INTO admin (user_id, name) VALUES (1, 'System Administrator');

-- Insert sample lecturers
INSERT INTO lecturers (user_id, name, email) VALUES 
(2, 'Dr. John Smith', 'john.smith@spas.com'),
(3, 'Prof. Sarah Johnson', 'sarah.johnson@spas.com');

-- Insert sample students
INSERT INTO students (user_id, name, email, date_of_birth, grade_level, edu_level) VALUES 
(4, 'Alice Brown', 'alice.brown@spas.com', '2000-05-15', 10, 'Undergraduate'),
(5, 'Bob Wilson', 'bob.wilson@spas.com', '1999-08-22', 11, 'Undergraduate'),
(6, 'Carol Davis', 'carol.davis@spas.com', '2001-03-10', 9, 'Undergraduate');

-- Insert sample semester/trimester
INSERT INTO current_semester (trimester_name, start_date, end_date, is_active) VALUES 
('Trimester 1 2024', '2024-01-15', '2024-04-15', TRUE),
('Trimester 2 2024', '2024-05-15', '2024-08-15', FALSE),
('Trimester 3 2024', '2024-09-15', '2024-12-15', FALSE);

-- Insert sample subjects
INSERT INTO subjects (subject_code, subject_name, description, assessment_type, edu_level, lecturer_id, trimester_id) VALUES 
('MATH101', 'Mathematics I', 'Introduction to Calculus and Algebra', 'coursework_final_exam', 'Undergraduate', 1, 1),
('PHY101', 'Physics I', 'Fundamental Physics Concepts', 'coursework_final_exam', 'Undergraduate', 2, 1),
('ENG101', 'English Composition', 'Academic Writing and Communication', 'coursework_only', 'Undergraduate', 1, 1),
('CS101', 'Computer Science I', 'Programming Fundamentals', 'coursework_final_exam', 'Undergraduate', 2, 1);

-- Insert sample classes
INSERT INTO classes (class_name, edu_level, semester, year, subject_id, lecturer_id) VALUES 
('Mathematics I - Class A', 'Undergraduate', 'Trimester 1', 2024, 1, 1),
('Physics I - Class A', 'Undergraduate', 'Trimester 1', 2024, 2, 2),
('English Composition - Class A', 'Undergraduate', 'Trimester 1', 2024, 3, 1),
('Computer Science I - Class A', 'Undergraduate', 'Trimester 1', 2024, 4, 2);

-- Insert sample student-class relationships
INSERT INTO student_classes (class_id, student_id) VALUES 
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 2),
(3, 1), (3, 3),
(4, 2), (4, 3);

-- Insert sample assessment plans
INSERT INTO assessment_plans (subject_id, assessment_type, category, weightage, due_date) VALUES 
(1, 'Assignment 1', 'coursework', 20, '2024-02-15'),
(1, 'Assignment 2', 'coursework', 20, '2024-03-15'),
(1, 'Final Exam', 'final_exam', 60, '2024-04-10'),
(2, 'Lab Report 1', 'coursework', 15, '2024-02-20'),
(2, 'Lab Report 2', 'coursework', 15, '2024-03-20'),
(2, 'Final Exam', 'final_exam', 70, '2024-04-12'),
(3, 'Essay 1', 'coursework', 30, '2024-02-25'),
(3, 'Essay 2', 'coursework', 30, '2024-03-25'),
(3, 'Final Project', 'coursework', 40, '2024-04-05'),
(4, 'Programming Assignment 1', 'coursework', 25, '2024-02-28'),
(4, 'Programming Assignment 2', 'coursework', 25, '2024-03-28'),
(4, 'Final Exam', 'final_exam', 50, '2024-04-15');

-- Insert sample grades
INSERT INTO grades (student_id, subject_id, assessment_id, class_id, marks, weighted_marks, total_marks, category, date_recorded) VALUES 
(1, 1, 1, 1, 85.00, 17.00, 17.00, 'coursework', '2024-02-15'),
(1, 1, 2, 1, 88.00, 17.60, 34.60, 'coursework', '2024-03-15'),
(1, 1, 3, 1, 82.00, 49.20, 83.80, 'final_exam', '2024-04-10'),
(1, 2, 4, 2, 90.00, 13.50, 13.50, 'coursework', '2024-02-20'),
(1, 2, 5, 2, 87.00, 13.05, 26.55, 'coursework', '2024-03-20'),
(1, 2, 6, 2, 85.00, 59.50, 86.05, 'final_exam', '2024-04-12'),
(2, 1, 1, 1, 78.00, 15.60, 15.60, 'coursework', '2024-02-15'),
(2, 1, 2, 1, 82.00, 16.40, 32.00, 'coursework', '2024-03-15'),
(2, 1, 3, 1, 79.00, 47.40, 79.40, 'final_exam', '2024-04-10'),
(2, 4, 10, 4, 92.00, 23.00, 23.00, 'coursework', '2024-02-28'),
(2, 4, 11, 4, 89.00, 22.25, 45.25, 'coursework', '2024-03-28'),
(2, 4, 12, 4, 88.00, 44.00, 89.25, 'final_exam', '2024-04-15'),
(3, 1, 1, 1, 75.00, 15.00, 15.00, 'coursework', '2024-02-15'),
(3, 1, 2, 1, 80.00, 16.00, 31.00, 'coursework', '2024-03-15'),
(3, 1, 3, 1, 76.00, 45.60, 76.60, 'final_exam', '2024-04-10'),
(3, 3, 7, 3, 88.00, 26.40, 26.40, 'coursework', '2024-02-25'),
(3, 3, 8, 3, 85.00, 25.50, 51.90, 'coursework', '2024-03-25'),
(3, 3, 9, 3, 90.00, 36.00, 87.90, 'coursework', '2024-04-05'),
(3, 4, 10, 4, 85.00, 21.25, 21.25, 'coursework', '2024-02-28'),
(3, 4, 11, 4, 87.00, 21.75, 43.00, 'coursework', '2024-03-28'),
(3, 4, 12, 4, 83.00, 41.50, 84.50, 'final_exam', '2024-04-15');

-- Insert sample calendar events
INSERT INTO calendar_events (event_date, event_text, subject_id) VALUES 
('2024-02-15', 'Mathematics Assignment 1 Due', 1),
('2024-02-20', 'Physics Lab Report 1 Due', 2),
('2024-02-25', 'English Essay 1 Due', 3),
('2024-02-28', 'CS Programming Assignment 1 Due', 4),
('2024-03-15', 'Mathematics Assignment 2 Due', 1),
('2024-03-20', 'Physics Lab Report 2 Due', 2),
('2024-03-25', 'English Essay 2 Due', 3),
('2024-03-28', 'CS Programming Assignment 2 Due', 4),
('2024-04-05', 'English Final Project Due', 3),
('2024-04-10', 'Mathematics Final Exam', 1),
('2024-04-12', 'Physics Final Exam', 2),
('2024-04-15', 'CS Final Exam', 4);

-- Insert sample feedback
INSERT INTO feedback (
    grade_id, assessment_id, student_id, class_id, lecturer_id,
    strengths, areas_for_improvement, recommendations, grade_justification, general_comments, feedback_status
) VALUES 
(1, 1, 1, 1, 1, 
 'Excellent problem-solving approach. Clear mathematical reasoning throughout.', 
 'Some minor calculation errors in the final steps.', 
 'Double-check all calculations before submission. Review calculus concepts.', 
 'Grade reflects strong understanding with minor errors.', 
 'Overall excellent work. Keep up the good effort!', 'published'),
(4, 4, 1, 2, 2, 
 'Well-structured lab report with clear methodology.', 
 'Could include more detailed analysis of experimental errors.', 
 'Include error analysis in future lab reports.', 
 'Strong lab work with room for improvement in analysis.', 
 'Good experimental work. Focus on error analysis.', 'published');

-- Update grades with calculated totals
UPDATE grades SET 
    coursework_total = (
        SELECT SUM(weighted_marks) 
        FROM grades g2 
        WHERE g2.student_id = grades.student_id 
        AND g2.subject_id = grades.subject_id 
        AND g2.category = 'coursework'
    ),
    final_exam_total = (
        SELECT SUM(weighted_marks) 
        FROM grades g2 
        WHERE g2.student_id = grades.student_id 
        AND g2.subject_id = grades.subject_id 
        AND g2.category = 'final_exam'
    )
WHERE category IN ('coursework', 'final_exam');

-- Update grades with letter grades based on total marks
UPDATE grades SET grade = 
    CASE 
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 90 THEN 'A+'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 85 THEN 'A'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 80 THEN 'A-'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 75 THEN 'B+'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 70 THEN 'B'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 65 THEN 'B-'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 60 THEN 'C+'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 55 THEN 'C'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 50 THEN 'C-'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 45 THEN 'D'
        WHEN (COALESCE(coursework_total, 0) + COALESCE(final_exam_total, 0)) >= 40 THEN 'E'
        ELSE 'F'
    END
WHERE subject_id IN (1, 2, 4); -- Only for subjects with final exams

-- For coursework-only subjects, calculate based on coursework total only
UPDATE grades SET grade = 
    CASE 
        WHEN coursework_total >= 90 THEN 'A+'
        WHEN coursework_total >= 85 THEN 'A'
        WHEN coursework_total >= 80 THEN 'A-'
        WHEN coursework_total >= 75 THEN 'B+'
        WHEN coursework_total >= 70 THEN 'B'
        WHEN coursework_total >= 65 THEN 'B-'
        WHEN coursework_total >= 60 THEN 'C+'
        WHEN coursework_total >= 55 THEN 'C'
        WHEN coursework_total >= 50 THEN 'C-'
        WHEN coursework_total >= 45 THEN 'D'
        WHEN coursework_total >= 40 THEN 'E'
        ELSE 'F'
    END
WHERE subject_id = 3; -- English Composition (coursework only)

-- Display database creation confirmation
SELECT 'SPAS Sample Data inserted successfully!' AS message;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_students FROM students;
SELECT COUNT(*) AS total_lecturers FROM lecturers;
SELECT COUNT(*) AS total_subjects FROM subjects;
SELECT COUNT(*) AS total_classes FROM classes;
SELECT COUNT(*) AS total_grades FROM grades; 