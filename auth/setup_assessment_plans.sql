-- Drop existing table if it exists
DROP TABLE IF EXISTS assessment_plans;

-- Create assessment_plans table
CREATE TABLE assessment_plans (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    assessment_type VARCHAR(100) NOT NULL,
    weightage INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT weightage_range CHECK (weightage >= 0 AND weightage <= 100),
    UNIQUE KEY unique_assessment (subject_id, assessment_type),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
); 