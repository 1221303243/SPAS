<?php
session_start();
require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';

// Validate session and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_GET['code'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Input validation
$user_id = $_SESSION['user_id'];
$student_id = null;
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    http_response_code(404);
    echo json_encode(['error' => 'Student profile not found']);
    exit();
}
$subjectCode = trim($_GET['code']);
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (empty($subjectCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject code is required']);
    exit();
}

/**
 * Get subject ID from subject code
 * @param string $subjectCode
 * @return int|null
 */
function getSubjectId($conn, $subjectCode) {
    $subject_id = null;  // Declare variable before use
    $stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
    $stmt->bind_param("s", $subjectCode);
    $stmt->execute();
    $stmt->bind_result($subject_id);
    $result = $stmt->fetch();
    $stmt->close();
    return $result ? $subject_id : null;
}

/**
 * Get assessments for a subject
 * @param int $subject_id
 * @return array
 */
function getAssessments($conn, $subject_id) {
    $assessments = [];
    $semester_start = new DateTime(SEMESTER_START_DATE);
    
    $stmt = $conn->prepare("
        SELECT assessment_id, weightage, due_date, assessment_type 
        FROM assessment_plans 
        WHERE subject_id = ?
    ");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $interval = $semester_start->diff(new DateTime($row['due_date']));
        $days = (int)$interval->format('%a');
        $week = floor($days / 7) + 1;
        $row['week'] = max(1, min(SEMESTER_WEEKS, $week)); // Clamp between 1 and SEMESTER_WEEKS
        $assessments[] = $row;
    }
    $stmt->close();
    return $assessments;
}

/**
 * Get student marks for assessments
 * @param int $student_id
 * @param array $assessment_ids
 * @return array
 */
function getStudentMarks($conn, $student_id, $assessment_ids) {
    $marks = [];
    if (empty($assessment_ids)) {
        return $marks;
    }

    $in = str_repeat('?,', count($assessment_ids) - 1) . '?';
    $types = str_repeat('i', count($assessment_ids) + 1);
    $params = array_merge([$student_id], $assessment_ids);

    $sql = "SELECT assessment_id, marks FROM grades WHERE student_id = ? AND assessment_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Validate that marks are within expected range
        $marks[$row['assessment_id']] = max(MIN_GRADE, min(MAX_GRADE, $row['marks']));
    }
    $stmt->close();
    return $marks;
}

/**
 * Calculate weekly percentages
 * @param array $assessments
 * @param array $marks
 * @return array
 */
function calculateWeeklyPercentages($assessments, $marks) {
    $weekly_percentages = [];
    
    for ($week = 1; $week <= SEMESTER_WEEKS; $week++) {
        $week_total = 0;
        $week_max = 0;
        
        foreach ($assessments as $assessment) {
            if ($assessment['week'] == $week) {
                $week_max += $assessment['weightage'];
                if (isset($marks[$assessment['assessment_id']])) {
                    // Normalize marks to percentage if needed (assuming marks are stored out of 100)
                    $normalized_marks = $marks[$assessment['assessment_id']];
                    $week_total += ($normalized_marks / MAX_GRADE) * $assessment['weightage'];
                }
            }
        }
        
        $weekly_percentages[] = [
            'week' => $week,
            'percentage' => $week_max > 0 ? round(($week_total / $week_max) * MAX_GRADE, 2) : null
        ];
    }
    
    return $weekly_percentages;
}

// Main execution
try {
    // Get subject ID
    $subject_id = getSubjectId($conn, $subjectCode);
    if (!$subject_id) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid subject code']);
        exit();
    }

    error_log('student_id: ' . $student_id);
    error_log('subject_id: ' . $subject_id);
    error_log('class_id: ' . $class_id);

    // Join grades and assessment_plans to get only grades for this subject and student
    $sql = "
        SELECT g.date_recorded, g.total_marks
        FROM grades g
        INNER JOIN assessment_plans a ON g.assessment_id = a.assessment_id
        WHERE g.student_id = ? AND a.subject_id = ? AND g.class_id = ?
        ORDER BY g.date_recorded ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $student_id, $subject_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades_by_date = [];
    while ($row = $result->fetch_assoc()) {
        $percentage = null;
        if (is_numeric($row['total_marks'])) {
            $percentage = max(MIN_GRADE, min(MAX_GRADE, $row['total_marks']));
        }
        $grades_by_date[] = [
            'date' => $row['date_recorded'],
            'percentage' => $percentage
        ];
    }
    $stmt->close();

    // Ensure the first data point is the semester start date
    $semester_start = SEMESTER_START_DATE;
    $has_start = false;
    foreach ($grades_by_date as $entry) {
        if ($entry['date'] === $semester_start) {
            $has_start = true;
            break;
        }
    }
    if (!$has_start) {
        array_unshift($grades_by_date, [
            'date' => $semester_start,
            'percentage' => null // or 0 if you want to show a starting point
        ]);
    }

    error_log('grades_by_date: ' . json_encode($grades_by_date)); // DEBUG

    header('Content-Type: application/json');
    echo json_encode($grades_by_date);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Error in get_chart_data.php: " . $e->getMessage());
} 