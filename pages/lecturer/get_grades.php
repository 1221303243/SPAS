<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../auth/db_connection.php';

if (!isset($_GET['assessment_id']) || !isset($_GET['class_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$assessment_id = intval($_GET['assessment_id']);
$class_id = intval($_GET['class_id']);

// Get assessment details
$stmt = $conn->prepare('
    SELECT ap.*, s.subject_code, s.subject_name
    FROM assessment_plans ap
    JOIN subjects s ON ap.subject_id = s.subject_id
    WHERE ap.assessment_id = ?
');
$stmt->bind_param('i', $assessment_id);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assessment) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Assessment not found']);
    exit();
}

// Get current grades for this assessment
$stmt = $conn->prepare('
    SELECT g.student_id, g.marks, g.grade, g.date_recorded
    FROM grades g
    WHERE g.assessment_id = ? AND g.class_id = ?
    ORDER BY g.student_id
');
$stmt->bind_param('ii', $assessment_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = [
        'student_id' => $row['student_id'],
        'marks' => $row['marks'],
        'grade' => $row['grade'],
        'date_recorded' => $row['date_recorded']
    ];
}
$stmt->close();

// Prepare response
$response = [
    'success' => true,
    'assessment' => [
        'assessment_id' => $assessment['assessment_id'],
        'assessment_type' => $assessment['assessment_type'],
        'category' => $assessment['category'],
        'weightage' => $assessment['weightage'],
        'due_date' => $assessment['due_date'],
        'subject_code' => $assessment['subject_code'],
        'subject_name' => $assessment['subject_name']
    ],
    'grades' => $grades
];

header('Content-Type: application/json');
echo json_encode($response); 