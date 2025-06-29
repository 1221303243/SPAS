<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../auth/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id']);
    $assessment_id = intval($_POST['assessment_id']);
    $grades = isset($_POST['grades']) ? $_POST['grades'] : [];

    // Validate assessment exists and get details
    $stmt = $conn->prepare('
        SELECT ap.due_date, ap.subject_id, ap.category, ap.assessment_type, ap.weightage,
               s.subject_code, s.subject_name
        FROM assessment_plans ap
        JOIN subjects s ON ap.subject_id = s.subject_id
        WHERE ap.assessment_id = ?
    ');
    $stmt->bind_param('i', $assessment_id);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$assessment) {
        $_SESSION['error'] = "Invalid assessment selected.";
        header('Location: grade.php?class_id=' . $class_id);
        exit();
    }

    // Validate marks before processing
    $errors = [];
    foreach ($grades as $student_id => $marks) {
        if (!is_numeric($marks) || $marks < 0 || $marks > 100) {
            $errors[] = "Invalid marks for student ID: $student_id";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: grade.php?class_id=' . $class_id . '&assessment_id=' . $assessment_id);
        exit();
    }

    // Calculate week number
    $semester_start = new DateTime('2025-03-10');
    $due_date = new DateTime($assessment['due_date']);
    $interval = $semester_start->diff($due_date);
    $days = (int)$interval->format('%a');
    $week_number = floor($days / 7) + 1;

    // Begin transaction
    $conn->begin_transaction();

    try {
        foreach ($grades as $student_id => $marks) {
            $weighted_marks = $marks * $assessment['weightage'] / 100;
            $stmt = $conn->prepare('
                INSERT INTO grades (student_id, subject_id, assessment_id, class_id, marks, weighted_marks, category, date_recorded)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE 
                    marks = VALUES(marks), 
                    weighted_marks = VALUES(weighted_marks),
                    category = VALUES(category), 
                    date_recorded = CURDATE()
            ');
            $stmt->bind_param('iiiidds', $student_id, $assessment['subject_id'], $assessment_id, $class_id, $marks, $weighted_marks, $assessment['category']);
            
            if (!$stmt->execute()) {
                error_log('Grade insert error: ' . $stmt->error);
                throw new Exception('Failed to insert grade for student ID: ' . $student_id);
            }
            $stmt->close();

            $stmt = $conn->prepare('
                SELECT 
                    SUM(CASE 
                        WHEN category = "coursework" 
                        THEN marks * (SELECT weightage FROM assessment_plans WHERE assessment_id = g.assessment_id) / 100 
                        ELSE 0 
                    END) as coursework_total,
                    SUM(CASE 
                        WHEN category = "final_exam" 
                        THEN marks * (SELECT weightage FROM assessment_plans WHERE assessment_id = g.assessment_id) / 100 
                        ELSE 0 
                    END) as final_exam_total,
                    SUM(marks * (SELECT weightage FROM assessment_plans WHERE assessment_id = g.assessment_id) / 100) as total_marks
                FROM grades g
                WHERE student_id = ? AND subject_id = ? AND class_id = ?
            ');
            $stmt->bind_param('iii', $student_id, $assessment['subject_id'], $class_id);
            
            if (!$stmt->execute()) {
                error_log('Grade calculation error: ' . $stmt->error);
                throw new Exception('Failed to calculate grades for student ID: ' . $student_id);
            }
            
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $coursework_total = $result['coursework_total'] ?? 0;
            $final_exam_total = $result['final_exam_total'] ?? 0;
            $total_marks = $result['total_marks'] ?? 0;

            // Get subject assessment type
            $stmt = $conn->prepare('SELECT assessment_type FROM subjects WHERE subject_id = ?');
            $stmt->bind_param('i', $assessment['subject_id']);
            
            if (!$stmt->execute()) {
                error_log('Subject assessment type error: ' . $stmt->error);
                throw new Exception('Failed to get subject assessment type for student ID: ' . $student_id);
            }
            
            $subject_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $subject_assessment_type = $subject_result['assessment_type'] ?? 'coursework_final_exam';

            // Calculate grade based on subject type
            $grade = '';
            if ($subject_assessment_type === 'coursework_only') {
                // For coursework-only subjects, use coursework total for letter grade
                $final_percentage = $coursework_total;
            } else {
                // For coursework + final exam subjects, check both categories pass first
                $coursework_weight = 0;
                $final_exam_weight = 0;
                
                // Get total weightages for each category
                $weight_stmt = $conn->prepare('
                    SELECT 
                        SUM(CASE WHEN category = "coursework" THEN weightage ELSE 0 END) as coursework_weight,
                        SUM(CASE WHEN category = "final_exam" THEN weightage ELSE 0 END) as final_exam_weight
                    FROM assessment_plans 
                    WHERE subject_id = ?
                ');
                $weight_stmt->bind_param('i', $assessment['subject_id']);
                
                if (!$weight_stmt->execute()) {
                    error_log('Weight calculation error: ' . $weight_stmt->error);
                    throw new Exception('Failed to calculate weightages for student ID: ' . $student_id);
                }
                
                $weight_result = $weight_stmt->get_result()->fetch_assoc();
                $weight_stmt->close();
                
                $coursework_weight = $weight_result['coursework_weight'] ?? 0;
                $final_exam_weight = $weight_result['final_exam_weight'] ?? 0;
                
                // Check if both categories pass minimum requirements
                $coursework_pass = ($coursework_weight > 0) ? ($coursework_total >= ($coursework_weight * 0.4)) : true; // 40% minimum
                $final_exam_pass = ($final_exam_weight > 0) ? ($final_exam_total >= ($final_exam_weight * 0.4)) : true; // 40% minimum
                
                if (!$coursework_pass || !$final_exam_pass) {
                    $grade = 'F'; // Fail if either category is below minimum
                } else {
                    $final_percentage = $coursework_total + $final_exam_total; // Total combined percentage
                }
            }
            
            // Calculate letter grade based on final percentage using MMU grading system (if not already failed)
            if ($grade !== 'F') {
                if ($final_percentage >= 90) {
                    $grade = 'A+';        // 90-100% (Exceptional)
                } elseif ($final_percentage >= 80) {
                    $grade = 'A';         // 80-89.99% (Excellent)
                } elseif ($final_percentage >= 76) {
                    $grade = 'B+';        // 76-79.99%
                } elseif ($final_percentage >= 72) {
                    $grade = 'B';         // 72-75.99% (Good)
                } elseif ($final_percentage >= 68) {
                    $grade = 'B-';        // 68-71.99%
                } elseif ($final_percentage >= 65) {
                    $grade = 'C+';        // 65-67.99%
                } elseif ($final_percentage >= 60) {
                    $grade = 'C';         // 60-64.99% (Average)
                } elseif ($final_percentage >= 56) {
                    $grade = 'C-';        // 56-59.99%
                } elseif ($final_percentage >= 50) {
                    $grade = 'D+';        // 50-55.99%
                } elseif ($final_percentage >= 40) {
                    $grade = 'D';         // 40-49% (Marginal Pass)
                } else {
                    $grade = 'F';         // 0-39.99% (Fail)
                }
            }

            // Update the grade and totals
            $stmt = $conn->prepare('
                UPDATE grades
                SET grade = ?,
                    coursework_total = ?,
                    final_exam_total = ?,
                    total_marks = ?
                WHERE student_id = ? 
                AND subject_id = ? 
                AND class_id = ?
            ');
            $stmt->bind_param('sdddiii', 
                $grade, 
                $coursework_total, 
                $final_exam_total, 
                $total_marks,
                $student_id, 
                $assessment['subject_id'], 
                $class_id
            );
            
            if (!$stmt->execute()) {
                error_log('Grade update error: ' . $stmt->error);
                throw new Exception('Failed to update final grade for student ID: ' . $student_id);
            }
            
            $stmt->close();

            // After saving the current assessment's marks and weighted_marks...
            // 1. Get all assessments for this subject, ordered by due date
            $assessments_sql = "
                SELECT a.assessment_id, a.due_date
                FROM assessment_plans a
                WHERE a.subject_id = ?
                ORDER BY a.due_date ASC, a.assessment_id ASC
            ";
            $assessments_stmt = $conn->prepare($assessments_sql);
            $assessments_stmt->bind_param('i', $assessment['subject_id']);
            
            if (!$assessments_stmt->execute()) {
                error_log('Assessments list error: ' . $assessments_stmt->error);
                throw new Exception('Failed to get assessments list for student ID: ' . $student_id);
            }
            
            $assessments_result = $assessments_stmt->get_result();
            $assessment_ids = [];
            while ($row = $assessments_result->fetch_assoc()) {
                $assessment_ids[] = $row['assessment_id'];
            }
            $assessments_stmt->close();

            // 2. Find the index of the current assessment
            $current_index = array_search($assessment_id, $assessment_ids);
            if ($current_index !== false) {
                // 3. Get all previous and current assessment ids
                $included_ids = array_slice($assessment_ids, 0, $current_index + 1);

                // 4. Sum weighted_marks for these assessments
                $in = implode(',', array_fill(0, count($included_ids), '?'));
                $types = str_repeat('i', count($included_ids) + 3);
                $params = array_merge([$student_id, $assessment['subject_id'], $class_id], $included_ids);

                $sum_sql = "
                    SELECT SUM(weighted_marks) as cumulative_total
                    FROM grades
                    WHERE student_id = ? AND subject_id = ? AND class_id = ? AND assessment_id IN ($in)
                ";
                $sum_stmt = $conn->prepare($sum_sql);
                $sum_stmt->bind_param($types, ...$params);
                
                if (!$sum_stmt->execute()) {
                    error_log('Cumulative total error: ' . $sum_stmt->error);
                    throw new Exception('Failed to calculate cumulative total for student ID: ' . $student_id);
                }
                
                $sum_result = $sum_stmt->get_result()->fetch_assoc();
                $sum_stmt->close();

                $cumulative_total = $sum_result['cumulative_total'] ?? 0;

                // 5. Update total_marks for the current assessment row
                $update_sql = "
                    UPDATE grades
                    SET total_marks = ?
                    WHERE student_id = ? AND subject_id = ? AND class_id = ? AND assessment_id = ?
                ";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('diiii', $cumulative_total, $student_id, $assessment['subject_id'], $class_id, $assessment_id);
                
                if (!$update_stmt->execute()) {
                    error_log('Total marks update error: ' . $update_stmt->error);
                    throw new Exception('Failed to update total marks for student ID: ' . $student_id);
                }
                
                $update_stmt->close();
            }
        }

        // Commit transaction
        $conn->commit();
        
        // Set success message with details
        $_SESSION['success'] = sprintf(
            "Grades for %s (%s) have been successfully updated.",
            $assessment['assessment_type'],
            $assessment['subject_code']
        );
        
        header('Location: grade.php?class_id=' . $class_id . '&success=1');
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log('Grade save error: ' . $e->getMessage()); // Log the actual error
        $_SESSION['error'] = "An error occurred while saving grades. Please try again.";
        header('Location: grade.php?class_id=' . $class_id);
        exit();
    }
} else {
    header('Location: grade.php');
    exit();
} 