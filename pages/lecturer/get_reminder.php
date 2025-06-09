<?php
session_start();
require_once '../../auth/db_connection.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$reminder = '';
if ($user_id && $_SESSION['role'] === 'lecturer') {
    $lecturer_id = null;
    $stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($lecturer_id);
    $stmt->fetch();
    $stmt->close();
    if ($lecturer_id) {
        $sql = "
            SELECT ap.assessment_type, ap.due_date, c.class_name
            FROM assessment_plans ap
            JOIN classes c ON ap.subject_id = c.subject_id
            WHERE c.lecturer_id = ?
              AND ap.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY ap.due_date ASC
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $lecturer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $reminder = "Reminder: <b>{$row['assessment_type']}</b> for <b>{$row['class_name']}</b> is due on <b>" . date('M d, Y', strtotime($row['due_date'])) . "</b> (in less than a week)!";
        }
        $stmt->close();
    }
}
echo json_encode(['reminder' => $reminder]); 