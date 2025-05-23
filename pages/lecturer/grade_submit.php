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

    // Fetch due_date and subject_id for the selected assessment
    $stmt = $conn->prepare('SELECT due_date, subject_id FROM assessment_plans WHERE assessment_id = ?');
    $stmt->bind_param('i', $assessment_id);
    $stmt->execute();
    $stmt->bind_result($assessment_due_date, $subject_id);
    $stmt->fetch();
    $stmt->close();

    // Calculate week number (not used in saving, but kept for reference)
    $semester_start = new DateTime('2025-03-10');
    $due_date = new DateTime($assessment_due_date);
    $interval = $semester_start->diff($due_date);
    $days = (int)$interval->format('%a');
    $week_number = floor($days / 7) + 1;

    foreach ($grades as $student_id => $marks) {
        // Save the marks for this assessment
        $stmt = $conn->prepare('
            INSERT INTO grades (student_id, subject_id, assessment_id, class_id, marks, date_recorded)
            VALUES (?, ?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE marks = VALUES(marks), date_recorded = CURDATE()
        ');
        $stmt->bind_param('iiiid', $student_id, $subject_id, $assessment_id, $class_id, $marks);
        $stmt->execute();
        $stmt->close();

        // Recalculate the total marks for this student in this subject/class
        $stmt = $conn->prepare('
            SELECT SUM(marks) as total_marks
            FROM grades
            WHERE student_id = ? AND subject_id = ? AND class_id = ?
        ');
        $stmt->bind_param('iii', $student_id, $subject_id, $class_id);
        $stmt->execute();
        $stmt->bind_result($total_marks);
        $stmt->fetch();
        $stmt->close();

        // Calculate the letter grade based on total_marks (out of 100)
        $grade = '';
        if ($total_marks >= 90) $grade = 'A';
        elseif ($total_marks >= 80) $grade = 'B';
        elseif ($total_marks >= 70) $grade = 'C';
        elseif ($total_marks >= 60) $grade = 'D';
        else $grade = 'F';

        // Update the grade column for this student/subject/class (all rows for this student/subject/class)
        $stmt = $conn->prepare('
            UPDATE grades
            SET grade = ?
            WHERE student_id = ? AND subject_id = ? AND class_id = ?
        ');
        $stmt->bind_param('siii', $grade, $student_id, $subject_id, $class_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect or show success
    header('Location: grade.php?class_id=' . $class_id . '&success=1');
    exit();
} else {
    header('Location: grade.php');
    exit();
} 