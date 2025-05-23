<?php
session_start();
require_once '../../auth/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_GET['code'])) {
    http_response_code(403);
    exit();
}

$student_id = $_SESSION['user_id'];
$subjectCode = $_GET['code'];

$stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
$stmt->bind_param("s", $subjectCode);
$stmt->execute();
$stmt->bind_result($subject_id);
$stmt->fetch();
$stmt->close();

$semester_start = new DateTime('2025-03-10');
$assessments = [];
if ($subject_id) {
    $stmt = $conn->prepare("SELECT assessment_id, weightage, due_date FROM assessment_plans WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $interval = $semester_start->diff(new DateTime($row['due_date']));
        $days = (int)$interval->format('%a');
        $week = floor($days / 7) + 1;
        $row['week'] = max(1, min(14, $week)); // Clamp between 1 and 14
        $assessments[] = $row;
    }
    $stmt->close();
}

$marks = [];
if (!empty($assessments)) {
    $assessment_ids = array_column($assessments, 'assessment_id');
    $in = str_repeat('?,', count($assessment_ids) - 1) . '?';
    $types = str_repeat('i', count($assessment_ids) + 1);
    $params = array_merge([$student_id], $assessment_ids);

    $sql = "SELECT assessment_id, marks FROM grades WHERE student_id = ? AND assessment_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $marks[$row['assessment_id']] = $row['marks'];
    }
    $stmt->close();
}

$weekly_grades = [];
for ($week = 1; $week <= 14; $week++) {
    $week_total = 0;
    $week_max = 0;
    foreach ($assessments as $a) {
        if ($a['week'] == $week) {
            $week_max += $a['weightage'];
            $week_total += isset($marks[$a['assessment_id']]) ? ($marks[$a['assessment_id']] / 100) * $a['weightage'] : 0;
        }
    }
    $weekly_grades[] = [$week, $week_max > 0 ? round(($week_total / $week_max) * 100, 2) : null];
}

header('Content-Type: application/json');
echo json_encode($weekly_grades); 