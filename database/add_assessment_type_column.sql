-- Add assessment_type column to subjects table
-- This allows for two types of subjects: coursework+final_exam and coursework_only

USE SPAS;

-- Add the assessment_type column if it doesn't exist
ALTER TABLE subjects 
ADD COLUMN assessment_type ENUM('coursework_final_exam', 'coursework_only') 
DEFAULT 'coursework_final_exam' 
AFTER description;

-- Add index for better performance
CREATE INDEX idx_subject_assessment_type ON subjects(assessment_type);

-- Update existing subjects to have the default assessment type
UPDATE subjects SET assessment_type = 'coursework_final_exam' WHERE assessment_type IS NULL; 