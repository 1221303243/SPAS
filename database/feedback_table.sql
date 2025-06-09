-- Feedback Table Schema
-- This table stores detailed feedback from lecturers for each student's assessment

CREATE TABLE feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    grade_id INT NOT NULL,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    
    -- Structured feedback fields
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    grade_justification TEXT,
    
    -- Additional feedback options
    general_comments TEXT,
    rubric_score JSON, -- Store detailed rubric scores if applicable
    
    -- Metadata
    feedback_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    feedback_status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    
    -- Foreign key constraints
    FOREIGN KEY (grade_id) REFERENCES grades(grade_id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessment_plans(assessment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_student_assessment (student_id, assessment_id),
    INDEX idx_class_assessment (class_id, assessment_id),
    INDEX idx_feedback_date (feedback_date),
    INDEX idx_feedback_status (feedback_status)
);

-- Optional: Create a view for easier querying
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
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    CONCAT(l.first_name, ' ', l.last_name) AS lecturer_name
FROM feedback f
JOIN grades g ON f.grade_id = g.grade_id
JOIN assessment_plans ap ON f.assessment_id = ap.assessment_id
JOIN students s ON f.student_id = s.student_id
JOIN lecturers l ON f.lecturer_id = l.lecturer_id; 