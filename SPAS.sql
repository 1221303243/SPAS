CREATE DATABASE SPAS;

USE SPAS;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    date_of_birth DATE,
    grade_level INT
);

CREATE TABLE lecturers (
    lecturer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100)
)

CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE grades (
    grade_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade VARCHAR(5) NOT NULL,
    date_recorded DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
);

-- Insert sample data
INSERT INTO students (name, email, grade_level) VALUES 
('John Smith', 'john.smith@example.com', 10),
('Emma Johnson', 'emma.johnson@example.com', 11),
('Michael Brown', 'michael.brown@example.com', 9),
('Sophia Davis', 'sophia.davis@example.com', 12);

INSERT INTO subjects (subject_name, description) VALUES 
('Mathematics', 'Algebra, Geometry, Calculus'),
('Science', 'Physics, Chemistry, Biology'),
('English', 'Literature, Grammar, Writing'),
('History', 'World History, Local History');

INSERT INTO grades (student_id, subject_id, grade, date_recorded) VALUES 
(1, 1, 'A', '2023-09-15'),
(1, 2, 'B+', '2023-09-16'),
(2, 1, 'A-', '2023-09-15'),
(2, 3, 'A', '2023-09-17'),
(3, 2, 'B', '2023-09-16'),
(3, 4, 'A-', '2023-09-18'),
(4, 1, 'B+', '2023-09-15'),
(4, 3, 'A+', '2023-09-17');

-- Queries for retrieving data

-- Get all students
SELECT * FROM students;

-- Get all subjects
SELECT * FROM subjects;

-- Get all grades with student and subject information
SELECT g.grade_id, s.name, subj.subject_name, g.grade, g.date_recorded
FROM grades g
JOIN students s ON g.student_id = s.student_id
JOIN subjects subj ON g.subject_id = subj.subject_id;

-- Get grades for a specific student
SELECT s.name, subj.subject_name, g.grade, g.date_recorded
FROM grades g
JOIN students s ON g.student_id = s.student_id
JOIN subjects subj ON g.subject_id = subj.subject_id
WHERE s.student_id = 1;

-- Calculate average grade per subject (assuming A=4, A-=3.7, B+=3.3, B=3, etc.)
SELECT subj.subject_name, 
       AVG(CASE 
           WHEN g.grade = 'A+' THEN 4.3
           WHEN g.grade = 'A' THEN 4.0
           WHEN g.grade = 'A-' THEN 3.7
           WHEN g.grade = 'B+' THEN 3.3
           WHEN g.grade = 'B' THEN 3.0
           WHEN g.grade = 'B-' THEN 2.7
           WHEN g.grade = 'C+' THEN 2.3
           WHEN g.grade = 'C' THEN 2.0
           WHEN g.grade = 'C-' THEN 1.7
           WHEN g.grade = 'D+' THEN 1.3
           WHEN g.grade = 'D' THEN 1.0
           WHEN g.grade = 'F' THEN 0.0
       END) AS avg_grade
FROM grades g
JOIN subjects subj ON g.subject_id = subj.subject_id
GROUP BY subj.subject_name;

-- Find students who have grades in all subjects
SELECT s.student_id, s.name
FROM students s
WHERE (SELECT COUNT(DISTINCT subject_id) FROM grades WHERE student_id = s.student_id) = 
      (SELECT COUNT(*) FROM subjects);

-- Find top performing students (highest average grades)
SELECT s.name, 
       AVG(CASE 
           WHEN g.grade = 'A+' THEN 4.3
           WHEN g.grade = 'A' THEN 4.0
           WHEN g.grade = 'A-' THEN 3.7
           WHEN g.grade = 'B+' THEN 3.3
           WHEN g.grade = 'B' THEN 3.0
           WHEN g.grade = 'B-' THEN 2.7
           WHEN g.grade = 'C+' THEN 2.3
           WHEN g.grade = 'C' THEN 2.0
           WHEN g.grade = 'C-' THEN 1.7
           WHEN g.grade = 'D+' THEN 1.3
           WHEN g.grade = 'D' THEN 1.0
           WHEN g.grade = 'F' THEN 0.0
       END) AS avg_grade
FROM grades g
JOIN students s ON g.student_id = s.student_id
GROUP BY s.student_id, s.name
ORDER BY avg_grade DESC
LIMIT 3;

