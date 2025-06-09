-- Sample Feedback Data
-- This script inserts sample feedback data for testing purposes
-- Make sure to adjust the IDs according to your actual data

-- Sample feedback for a coursework assessment
INSERT INTO feedback (
    grade_id,
    assessment_id,
    student_id,
    class_id,
    lecturer_id,
    strengths,
    areas_for_improvement,
    recommendations,
    grade_justification,
    general_comments,
    feedback_status
) VALUES (
    1, -- Replace with actual grade_id
    1, -- Replace with actual assessment_id
    1, -- Replace with actual student_id
    1, -- Replace with actual class_id
    1, -- Replace with actual lecturer_id
    'Excellent analysis of the market trends. Your research methodology was well-structured and your conclusions were well-supported with recent data. The presentation was clear and professional.',
    'Some calculations in the financial analysis section need more precision. The sample size could be larger for better statistical significance. Consider including more recent references from the past year.',
    'Double-check all mathematical calculations before submission. Review recent literature in the field to strengthen your references. Practice time management to ensure thorough review of your work.',
    'Grade of 85% reflects strong performance with room for improvement. The analysis was comprehensive but some technical details needed refinement.',
    'Overall, this is a solid piece of work that demonstrates good understanding of the subject matter. Keep up the good work and focus on the areas mentioned for improvement.',
    'published'
);

-- Sample feedback for a final exam
INSERT INTO feedback (
    grade_id,
    assessment_id,
    student_id,
    class_id,
    lecturer_id,
    strengths,
    areas_for_improvement,
    recommendations,
    grade_justification,
    general_comments,
    feedback_status
) VALUES (
    2, -- Replace with actual grade_id
    2, -- Replace with actual assessment_id
    1, -- Replace with actual student_id
    1, -- Replace with actual class_id
    1, -- Replace with actual lecturer_id
    'Strong problem-solving approach throughout the exam. Your answers showed deep understanding of core concepts. Good use of relevant formulas and methods.',
    'Time management could be improved - some questions were left incomplete. More detailed explanations would strengthen your answers. Consider practicing under timed conditions.',
    'Practice past exam papers under timed conditions to improve time management. Focus on providing detailed explanations for complex problems. Review key concepts regularly.',
    'Grade of 78% reflects good understanding but time management issues affected completion. Strong foundation with room for improvement in exam technique.',
    'You have a solid grasp of the material. With better time management and more detailed explanations, you can achieve even higher scores in future exams.',
    'published'
); 