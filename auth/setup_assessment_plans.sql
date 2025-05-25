-- Drop existing table if it exists
DROP TABLE IF EXISTS assessment_plans;

-- Create assessment_plans table
CREATE TABLE assessment_plans (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    assessment_type VARCHAR(100) NOT NULL,
    category ENUM('coursework', 'final_exam') NOT NULL,
    weightage INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT weightage_range CHECK (weightage >= 0 AND weightage <= 100),
    UNIQUE KEY unique_assessment (subject_id, assessment_type),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Add trigger to ensure total weightage per category per subject is <= 100
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