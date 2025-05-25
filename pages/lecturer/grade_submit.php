<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: ../../auth/index.php');
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
            // Save the marks for this assessment
            $stmt = $conn->prepare('
                INSERT INTO grades (student_id, subject_id, assessment_id, class_id, marks, category, date_recorded)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE 
                    marks = VALUES(marks), 
                    category = VALUES(category), 
                    date_recorded = CURDATE()
            ');
            $stmt->bind_param('iiiids', $student_id, $assessment['subject_id'], $assessment_id, $class_id, $marks, $assessment['category']);
            $stmt->execute();
            $stmt->close();

            // Calculate category totals and overall grade
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
                    SUM(marks * (SELECT weightage FROM assessment_plans WHERE assessment_id = g.assessment_id) / 100) as total_marks,
                    COUNT(DISTINCT CASE WHEN category = "coursework" THEN assessment_id END) as coursework_count,
                    COUNT(DISTINCT CASE WHEN category = "final_exam" THEN assessment_id END) as final_exam_count
                FROM grades g
                WHERE student_id = ? AND subject_id = ? AND class_id = ?
            ');
            $stmt->bind_param('iii', $student_id, $assessment['subject_id'], $class_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $coursework_total = $result['coursework_total'] ?? 0;
            $final_exam_total = $result['final_exam_total'] ?? 0;
            $total_marks = $result['total_marks'] ?? 0;
            $coursework_count = $result['coursework_count'] ?? 0;
            $final_exam_count = $result['final_exam_count'] ?? 0;

            // Calculate the letter grade based on MMU rules with +/- modifiers
            $grade = '';
            if ($coursework_total >= 50 && $final_exam_total >= 50) {
                if ($total_marks >= 90) {
                    $grade = 'A+';
                } elseif ($total_marks >= 85) {
                    $grade = 'A';
                } elseif ($total_marks >= 80) {
                    $grade = 'A-';
                } elseif ($total_marks >= 75) {
                    $grade = 'B+';
                } elseif ($total_marks >= 70) {
                    $grade = 'B';
                } elseif ($total_marks >= 65) {
                    $grade = 'B-';
                } elseif ($total_marks >= 60) {
                    $grade = 'C+';
                } elseif ($total_marks >= 55) {
                    $grade = 'C';
                } elseif ($total_marks >= 50) {
                    $grade = 'C-';
                } elseif ($total_marks >= 45) {
                    $grade = 'D';
                } else {
                    $grade = 'F';
                }
            } else {
                $grade = 'F'; // Fail if either component is below 50%
            }

            // Update the grade and totals
            $stmt = $conn->prepare('
                UPDATE grades
                SET grade = ?,
                    coursework_total = ?,
                    final_exam_total = ?,
                    total_marks = ?,
                    coursework_count = ?,
                    final_exam_count = ?,
                    last_updated = NOW()
                WHERE student_id = ? 
                AND subject_id = ? 
                AND class_id = ?
            ');
            $stmt->bind_param('sddiiiiii', 
                $grade, 
                $coursework_total, 
                $final_exam_total, 
                $total_marks,
                $coursework_count,
                $final_exam_count,
                $student_id, 
                $assessment['subject_id'], 
                $class_id
            );
            $stmt->execute();
            $stmt->close();
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
        $_SESSION['error'] = "An error occurred while saving grades. Please try again.";
        header('Location: grade.php?class_id=' . $class_id);
        exit();
    }
} else {
    header('Location: grade.php');
    exit();
} 