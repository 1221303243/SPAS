<?php
session_start();
require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$reminder = '';
$current_trimester = getCurrentTrimester($conn);
if ($user_id && $_SESSION['role'] === 'student' && $current_trimester) {
    $student_id = null;
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();
    if ($student_id) {
        $sql = "
            SELECT ap.assessment_type, ap.due_date, sub.subject_name
            FROM assessment_plans ap
            JOIN subjects sub ON ap.subject_id = sub.subject_id
            JOIN classes c ON c.subject_id = ap.subject_id
            JOIN student_classes sc ON sc.class_id = c.class_id
            WHERE sc.student_id = ?
              AND sub.trimester_id = ?
              AND ap.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY ap.due_date ASC
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $student_id, $current_trimester['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $reminder = "Reminder: <b>{$row['assessment_type']}</b> for <b>{$row['subject_name']}</b> is due on <b>" . date('M d, Y', strtotime($row['due_date'])) . "</b> (in less than a week)!";
        }
        $stmt->close();
    }
}
echo json_encode(['reminder' => $reminder]); 