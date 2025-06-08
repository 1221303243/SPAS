-- Drop existing table if it exists
DROP TABLE IF EXISTS grades;

-- Create grades table
CREATE TABLE grades (
    grade_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    assessment_id INT NOT NULL,
    class_id INT NOT NULL,
    marks DECIMAL(5,2) NOT NULL,
    weighted_marks DECIMAL(5,2) DEFAULT NULL,
    category ENUM('coursework', 'final_exam') NOT NULL,
    coursework_total DECIMAL(5,2) DEFAULT NULL,
    final_exam_total DECIMAL(5,2) DEFAULT NULL,
    grade CHAR(2) DEFAULT NULL,
    date_recorded DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessment_plans(assessment_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY unique_grade (student_id, assessment_id, class_id)
); 