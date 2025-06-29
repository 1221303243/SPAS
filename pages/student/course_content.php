<?php
session_start();
// include('../../logic/db-connection.php');
// $conn = OpenCon();
require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'student') {
    echo "Access denied!";
    exit();
}

if (!isset($_GET['code'])) {
    echo "No course selected.";
    exit();
}

$subjectCode = $_GET['code'];

// Get the actual student_id for the logged-in user
$user_id = $_SESSION['user_id'];
$student_id = null;
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

// Get subject_id from subject code
$subject_id = null;
$stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
$stmt->bind_param("s", $subjectCode);
$stmt->execute();
$stmt->bind_result($subject_id);
$stmt->fetch();
$stmt->close();

// Get subject name from subject code
$subjectName = "Unknown Subject";
$stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_code = ?");
$stmt->bind_param("s", $subjectCode);
$stmt->execute();
$stmt->bind_result($subjectName);
$stmt->fetch();
$stmt->close();

$current_trimester = getCurrentTrimester($conn);
if ($current_trimester) {
    $semester_start = new DateTime($current_trimester['start_date']);
    $semester_end = new DateTime($current_trimester['end_date']);
} else {
    $semester_start = new DateTime(SEMESTER_START_DATE);
    $semester_end = clone $semester_start;
    $semester_end->modify('+' . (SEMESTER_WEEKS - 1) . ' weeks');
    // Optionally, for debugging, you can display a warning:
    // echo '<div style="color:orange;">No active trimester is set. Using default semester dates.</div>';
}

// Get all assessments for this subject
$assessments = [];
if ($subject_id) {
    $stmt = $conn->prepare("SELECT assessment_id, assessment_type, weightage, due_date FROM assessment_plans WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['week'] = floor((new DateTime($row['due_date']))->diff($semester_start)->days / 7) + 1;
        $assessments[] = $row;
    }
    $stmt->close();
}

// Get student's marks for these assessments
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

// Get class_id for this student and subject
$class_id = null;
if ($subject_id) {
    $stmt = $conn->prepare("
        SELECT sc.class_id
        FROM student_classes sc
        JOIN classes c ON sc.class_id = c.class_id
        WHERE sc.student_id = ? AND c.subject_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $student_id, $subject_id);
    $stmt->execute();
    $stmt->bind_result($class_id);
    $stmt->fetch();
    $stmt->close();
}

// Get subject assessment type (move this up so it's available for the header)
$stmt = $conn->prepare("SELECT assessment_type FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject_result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$subject_assessment_type = $subject_result['assessment_type'] ?? 'coursework_final_exam';

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Course Content</title>
    <link rel="stylesheet" href="../../css/course_content.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        font-weight: 600;
        border-radius: 16px;
        padding: 0.25em 0.75em;
        font-size: 1em;
        margin-right: 0.5em;
        transition: background 0.2s;
    }
    .status-pass {
        background: #28a745;
        color: #fff;
    }
    .status-fail {
        background: #dc3545;
        color: #fff;
    }
    .status-desc {
        color: #6c757d;
        font-size: 0.98em;
    }
    .pass-status {
        margin: 0.5em 0 0 0;
        display: flex;
        align-items: center;
        gap: 0.5em;
    }
    /* Grade badge color classes */
    .grade-badge {
        border-radius: 16px;
        padding: 0.25em 0.9em;
        font-weight: 600;
        font-size: 1.1em;
        letter-spacing: 0.03em;
        margin-left: 0.5em;
        display: inline-block;
    }
    .grade-success {
        background: #28a745;
        color: #fff;
    }
    .grade-warning {
        background: #ffc107;
        color: #212529;
    }
    .grade-danger {
        background: #dc3545;
        color: #fff;
    }
    .grade-secondary {
        background: #6c757d;
        color: #fff;
    }
    /* Overall Result Card Styling */
    .overall-result-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(0, 193, 254, 0.08), 0 1.5px 6px rgba(31, 18, 53, 0.06);
        padding: 2.2em 2em 1.5em 2em;
        margin: 2em auto 2em auto;
        max-width: 480px;
        text-align: center;
        border: 1px solid #e3e6ea;
    }
    .overall-title {
        font-size: 1.35em;
        font-weight: 700;
        margin-bottom: 0.7em;
        display: flex;
        align-items: center;
        gap: 0.5em;
        justify-content: center;
    }
    .result-summary.centered {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.1em;
    }
    .overall-percentage {
        font-size: 2.3em;
        font-weight: 800;
        color: #1F1235;
        margin-bottom: 0.2em;
        letter-spacing: 0.01em;
    }
    .grade-row {
        display: flex;
        align-items: center;
        gap: 1.1em;
        margin-top: 0.5em;
        justify-content: center;
    }
    </style>
    <script type="text/javascript">
        var semesterStartDate = new Date('<?php echo $current_trimester ? $current_trimester['start_date'] : SEMESTER_START_DATE; ?>');
        var semesterEndDate = new Date('<?php echo $current_trimester ? $current_trimester['end_date'] : $semester_end->format('Y-m-d'); ?>');
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            fetch('get_chart_data.php?code=<?php echo urlencode($subjectCode); ?>&class_id=<?php echo $class_id; ?>')
                .then(response => response.json())
                .then(grades_by_date => {
                    console.log('grades_by_date:', grades_by_date); // DEBUG
                    var data = new google.visualization.DataTable();
                    data.addColumn('date', 'Date');
                    data.addColumn('number', 'Your Mark');
                    data.addColumn('number', 'Passing Grade');

                    data.addRow([semesterStartDate, 0, 50]); // Start from 0
                    grades_by_date.forEach(function(grade) {
                        if (grade.date && grade.percentage != null) {
                            // Parse date string (YYYY-MM-DD) to JS Date object
                            var parts = grade.date.split('-');
                            var jsDate = new Date(parts[0], parts[1] - 1, parts[2]);
                            data.addRow([jsDate, Number(grade.percentage), 50]);
                        }
                    });

                    var options = {
                        title: 'Academic Progress Over Time',
                        curveType: 'function',
                        legend: { 
                            position: 'bottom',
                            textStyle: { fontSize: 12 }
                        },
                        hAxis: { 
                            title: 'Date',
                            format: 'MMM d, yyyy',
                            gridlines: { count: -1 },
                            minorGridlines: { count: 0 },
                            slantedText: true,
                            slantedTextAngle: 45,
                            viewWindow: {
                                min: semesterStartDate,
                                max: semesterEndDate
                            }
                        },
                        vAxis: { 
                            title: 'Grade (%)',
                            viewWindow: { min: 0, max: 100 },
                            ticks: [0, 20, 40, 50, 60, 80, 100],
                            gridlines: { count: 6 },
                            minorGridlines: { count: 1 }
                        },
                        colors: ['#006DB0', '#FF0000'],
                        lineWidth: 3,
                        pointSize: 7,
                        tooltip: { 
                            isHtml: true,
                            trigger: 'focus'
                        },
                        annotations: {
                            textStyle: {
                                fontSize: 12,
                                bold: true
                            }
                        },
                        chartArea: {
                            left: '10%',
                            right: '10%',
                            top: '15%',
                            bottom: '20%'
                        },
                        backgroundColor: '#f8f9fa',
                        series: {
                            1: { // Passing grade line
                                lineDashStyle: [4, 4],
                                type: 'line',
                                color: '#FF0000',
                                lineWidth: 2
                            }
                        }
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
                    chart.draw(data, options);
                });
        }
        // Auto-refresh every 10 seconds
        setInterval(drawChart, 10000);
    </script>
</head>
<body>
    <?php include 'sidebar_student.php'; ?>

    <div class="content-container">
        <div class="course-header">
            <?php
            // Subject type label for header
            $type_label = ($subject_assessment_type === 'coursework_only') ? 'Coursework Only' : 'Coursework + Final Exam';
            ?>
            <h1>
                <?php echo $subjectName . ' (' . htmlspecialchars($subjectCode) . ' - ' . $type_label . ')'; ?>
            </h1>
        </div>

        <div class="graph-container">
            <div id="curve_chart"></div>
        </div>

        <!-- Assessment Analysis Section -->
        <div class="analysis-container">
            <h2>Assessment Analysis</h2>
            <div class="analysis-content">
                <?php
                // Fetch assessment details with student marks
                $analysis_sql = "
                    SELECT 
                        ap.assessment_type,
                        ap.category,
                        ap.weightage,
                        ap.due_date,
                        g.marks,
                        g.weighted_marks,
                        g.coursework_total,
                        g.final_exam_total,
                        g.date_recorded
                    FROM assessment_plans ap
                    LEFT JOIN grades g ON g.assessment_id = ap.assessment_id 
                        AND g.student_id = ? 
                        AND g.class_id = ?
                    WHERE ap.subject_id = ?
                    ORDER BY ap.category, ap.due_date ASC
                ";
                $stmt = $conn->prepare($analysis_sql);
                $stmt->bind_param("iii", $student_id, $class_id, $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $coursework_assessments = [];
                $final_exam_assessments = [];
                $overall_coursework_total = 0;
                $overall_final_exam_total = 0;

                while ($row = $result->fetch_assoc()) {
                    if ($row['category'] === 'coursework') {
                        $coursework_assessments[] = $row;
                        if ($row['coursework_total'] !== null) {
                            $overall_coursework_total = $row['coursework_total'];
                        }
                    } else {
                        $final_exam_assessments[] = $row;
                        if ($row['final_exam_total'] !== null) {
                            $overall_final_exam_total = $row['final_exam_total'];
                        }
                    }
                }
                $stmt->close();

                // Calculate total weightages for dynamic pass mark
                $coursework_weight = 0;
                foreach ($coursework_assessments as $a) $coursework_weight += $a['weightage'];
                $final_exam_weight = 0;
                foreach ($final_exam_assessments as $a) $final_exam_weight += $a['weightage'];
                $coursework_pass_mark = $coursework_weight / 2;
                $final_exam_pass_mark = $final_exam_weight / 2;

                // Display Coursework Assessments
                if (!empty($coursework_assessments)) {
                    echo '<div class="assessment-category">';
                    echo '<h3><i class="bi bi-book"></i> Coursework</h3>';
                    echo '<div class="assessment-grid">';
                    
                    foreach ($coursework_assessments as $assessment) {
                        echo '<div class="assessment-card">';
                        echo '<div class="assessment-header">';
                        echo '<h4>' . htmlspecialchars($assessment['assessment_type']) . '</h4>';
                        echo '<span class="weightage">' . $assessment['weightage'] . '%</span>';
                        echo '</div>';
                        
                        echo '<div class="assessment-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="label">Due Date:</span>';
                        echo '<span class="value">' . date('M d, Y', strtotime($assessment['due_date'])) . '</span>';
                        echo '</div>';
                        
                        echo '<div class="detail-row">';
                        echo '<span class="label">Raw Mark:</span>';
                        if ($assessment['marks'] !== null) {
                            // Try to get total marks for this assessment
                            $total_mark = isset($assessment['total_mark']) ? $assessment['total_mark'] : 100;
                            echo '<span class="value mark">' . number_format($assessment['marks'], 1) . '/' . number_format($total_mark, 0) . '</span>';
                        } else {
                            echo '<span class="value not-taken">Not Taken Yet</span>';
                        }
                        echo '</div>';
                        
                        echo '<div class="detail-row">';
                        echo '<span class="label">Weighted Mark:</span>';
                        if ($assessment['weighted_marks'] !== null) {
                            echo '<span class="value weighted">' . number_format($assessment['weighted_marks'], 1) . '%</span>';
                        } else {
                            echo '<span class="value not-taken">Not Available</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '<div class="category-summary">';
                    echo '<h4>Coursework Total: ' . number_format($overall_coursework_total, 1) . '%</h4>';
                    if ($subject_assessment_type === 'coursework_only') {
                        $pass = $overall_coursework_total >= $coursework_pass_mark;
                        echo '<p class="pass-status ' . ($pass ? 'pass' : 'fail') . '">';
                        echo '<span class="status-badge ' . ($pass ? 'status-pass' : 'status-fail') . '">';
                        echo $pass ? '<i class="bi bi-check-circle-fill"></i> PASS' : '<i class="bi bi-x-circle-fill"></i> FAIL';
                        echo '</span> ';
                        echo '<span class="status-desc">(Requires ≥<strong>' . number_format($coursework_pass_mark, 1) . '%</strong> of total coursework marks)</span>';
                        echo '</p>';
                    } else {
                        $pass = $overall_coursework_total >= $coursework_pass_mark;
                        echo '<p class="pass-status ' . ($pass ? 'pass' : 'fail') . '">';
                        echo '<span class="status-badge ' . ($pass ? 'status-pass' : 'status-fail') . '">';
                        echo $pass ? '<i class="bi bi-check-circle-fill"></i> PASS' : '<i class="bi bi-x-circle-fill"></i> FAIL';
                        echo '</span> ';
                        echo '<span class="status-desc">(Requires ≥<strong>' . number_format($coursework_pass_mark, 1) . '%</strong> of coursework weightage)</span>';
                        echo '</p>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Display Final Exam Assessments (only for coursework + final exam subjects)
                if (!empty($final_exam_assessments) && $subject_assessment_type !== 'coursework_only') {
                    echo '<div class="assessment-category">';
                    echo '<h3><i class="bi bi-file-earmark-text"></i> Final Exam</h3>';
                    echo '<div class="assessment-grid">';
                    
                    foreach ($final_exam_assessments as $assessment) {
                        echo '<div class="assessment-card">';
                        echo '<div class="assessment-header">';
                        echo '<h4>' . htmlspecialchars($assessment['assessment_type']) . '</h4>';
                        echo '<span class="weightage">' . $assessment['weightage'] . '%</span>';
                        echo '</div>';
                        
                        echo '<div class="assessment-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="label">Due Date:</span>';
                        echo '<span class="value">' . date('M d, Y', strtotime($assessment['due_date'])) . '</span>';
                        echo '</div>';
                        
                        echo '<div class="detail-row">';
                        echo '<span class="label">Raw Mark:</span>';
                        if ($assessment['marks'] !== null) {
                            // Try to get total marks for this assessment
                            $total_mark = isset($assessment['total_mark']) ? $assessment['total_mark'] : 100;
                            echo '<span class="value mark">' . number_format($assessment['marks'], 1) . '/' . number_format($total_mark, 0) . '</span>';
                        } else {
                            echo '<span class="value not-taken">Not Taken Yet</span>';
                        }
                        echo '</div>';
                        
                        echo '<div class="detail-row">';
                        echo '<span class="label">Weighted Mark:</span>';
                        if ($assessment['weighted_marks'] !== null) {
                            echo '<span class="value weighted">' . number_format($assessment['weighted_marks'], 1) . '%</span>';
                        } else {
                            echo '<span class="value not-taken">Not Available</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '<div class="category-summary">';
                    echo '<h4>Final Exam Total: ' . number_format($overall_final_exam_total, 1) . '%</h4>';
                    $pass = $overall_final_exam_total >= $final_exam_pass_mark;
                    echo '<p class="pass-status ' . ($pass ? 'pass' : 'fail') . '">';
                    echo '<span class="status-badge ' . ($pass ? 'status-pass' : 'status-fail') . '">';
                    echo $pass ? '<i class="bi bi-check-circle-fill"></i> PASS' : '<i class="bi bi-x-circle-fill"></i> FAIL';
                    echo '</span> ';
                    echo '<span class="status-desc">(Requires ≥<strong>' . number_format($final_exam_pass_mark, 1) . '%</strong> of final exam weightage)</span>';
                    echo '</p>';
                    echo '</div>';
                    echo '</div>';
                }

                // Overall Result
                $overall_total = $overall_coursework_total + $overall_final_exam_total;
                // Calculate final grade (same as student_detail.php)
                function calculateOverallGrade($overallPercentage, $overallStatus) {
                    if ($overallStatus === 'FAIL') {
                        return 'F';
                    }
                    // MMU grading system
                    if ($overallPercentage >= 90) {
                        return 'A+';        // 90-100% (Exceptional)
                    } elseif ($overallPercentage >= 80) {
                        return 'A';         // 80-89.99% (Excellent)
                    } elseif ($overallPercentage >= 76) {
                        return 'B+';        // 76-79.99%
                    } elseif ($overallPercentage >= 72) {
                        return 'B';         // 72-75.99% (Good)
                    } elseif ($overallPercentage >= 68) {
                        return 'B-';        // 68-71.99%
                    } elseif ($overallPercentage >= 65) {
                        return 'C+';        // 65-67.99%
                    } elseif ($overallPercentage >= 60) {
                        return 'C';         // 60-64.99% (Average)
                    } elseif ($overallPercentage >= 56) {
                        return 'C-';        // 56-59.99%
                    } elseif ($overallPercentage >= 50) {
                        return 'D+';        // 50-55.99%
                    } elseif ($overallPercentage >= 40) {
                        return 'D';         // 40-49% (Marginal Pass)
                    } else {
                        return 'F';         // 0-39.99% (Fail)
                    }
                }
                // Determine pass/fail for grade calculation
                if ($subject_assessment_type === 'coursework_only') {
                    $overall_pass = $overall_coursework_total >= $coursework_pass_mark;
                } else {
                    $coursework_pass = $overall_coursework_total >= $coursework_pass_mark;
                    $final_exam_pass = $overall_final_exam_total >= $final_exam_pass_mark;
                    $overall_pass = $coursework_pass && $final_exam_pass;
                }
                $final_grade = calculateOverallGrade($overall_total, $overall_pass ? 'PASS' : 'FAIL');
                // Grade color class (same as student_detail.php)
                function getGradeColorClass($grade) {
                    if (in_array($grade, ['A+', 'A'])) {
                        return 'grade-success'; // Green
                    } elseif (in_array($grade, ['B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D'])) {
                        return 'grade-warning'; // Yellow/Orange
                    } elseif (in_array($grade, ['F'])) {
                        return 'grade-danger'; // Red
                    }
                    return 'grade-secondary'; // Default/gray
                }
                $grade_color_class = getGradeColorClass($final_grade);
                echo '<div class="overall-result-card">';
                echo '<h3 class="overall-title"><i class="bi bi-trophy"></i> Overall Result</h3>';
                echo '<div class="result-summary centered">';
                echo '<div class="overall-percentage">' . number_format($overall_total, 1) . '%</div>';
                echo '<div class="grade-row">';
                echo '<span class="status-badge ' . ($overall_pass ? 'status-pass' : 'status-fail') . '">';
                echo $overall_pass ? '<i class="bi bi-check-circle-fill"></i> PASS' : '<i class="bi bi-x-circle-fill"></i> FAIL';
                echo '</span>';
                echo '<span class="grade-badge ' . $grade_color_class . '">Grade: ' . $final_grade . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                ?>
            </div>
        </div>

        <div class="feedback-container">
            <h2><i class="bi bi-chat-dots"></i> Lecturer Feedback</h2>
            <div class="feedback-content">
                <?php
                // Fetch feedback for each assessment
                $feedback_sql = "
                    SELECT 
                        ap.assessment_type,
                        ap.category,
                        ap.due_date,
                        g.marks,
                        f.strengths,
                        f.areas_for_improvement,
                        f.recommendations,
                        f.grade_justification,
                        f.general_comments,
                        f.feedback_date,
                        f.feedback_status,
                        l.name AS lecturer_name
                    FROM assessment_plans ap
                    LEFT JOIN grades g ON g.assessment_id = ap.assessment_id 
                        AND g.student_id = ? 
                        AND g.class_id = ?
                    LEFT JOIN feedback f ON f.grade_id = g.grade_id 
                        AND f.feedback_status = 'published'
                    LEFT JOIN lecturers l ON f.lecturer_id = l.lecturer_id
                    WHERE ap.subject_id = ?
                    ORDER BY ap.due_date DESC
                ";
                $stmt = $conn->prepare($feedback_sql);
                $stmt->bind_param("iii", $student_id, $class_id, $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $hasFeedback = false;
                while ($row = $result->fetch_assoc()) {
                    if ($row['strengths'] || $row['areas_for_improvement'] || $row['recommendations']) {
                        $hasFeedback = true;
                        $cardId = 'feedback-card-' . $row['assessment_type'] . '-' . strtotime($row['feedback_date']);
                        echo '<div class="feedback-item interactive-card" id="' . $cardId . '">';
                        echo '<div class="feedback-header card-header-toggle" data-target="' . $cardId . '">';
                        echo '<div class="feedback-title">';
                        echo '<h4>' . htmlspecialchars($row['assessment_type']) . '</h4>';
                        echo '<span class="feedback-category">' . ucfirst($row['category']) . '</span>';
                        echo '</div>';
                        echo '<div class="feedback-meta">';
                        if ($row['marks'] !== null) {
                            echo '<span class="feedback-grade">Grade: ' . number_format($row['marks'], 1) . '%</span>';
                        }
                        if ($row['feedback_date']) {
                            echo '<span class="feedback-date">' . date('M d, Y', strtotime($row['feedback_date'])) . '</span>';
                        }
                        if ($row['lecturer_name']) {
                            echo '<span class="feedback-lecturer">By: ' . htmlspecialchars($row['lecturer_name']) . '</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                        // Feedback body hidden by default
                        echo '<div class="feedback-body card-body-toggle" style="display:none;">';
                        if ($row['strengths']) {
                            echo '<div class="feedback-section strengths">';
                            echo '<h5><i class="bi bi-check-circle-fill"></i> Strengths</h5>';
                            echo '<p>' . nl2br(htmlspecialchars($row['strengths'])) . '</p>';
                            echo '</div>';
                        }
                        if ($row['areas_for_improvement']) {
                            echo '<div class="feedback-section improvements">';
                            echo '<h5><i class="bi bi-exclamation-triangle-fill"></i> Areas for Improvement</h5>';
                            echo '<p>' . nl2br(htmlspecialchars($row['areas_for_improvement'])) . '</p>';
                            echo '</div>';
                        }
                        if ($row['recommendations']) {
                            echo '<div class="feedback-section recommendations">';
                            echo '<h5><i class="bi bi-lightbulb-fill"></i> Recommendations</h5>';
                            echo '<p>' . nl2br(htmlspecialchars($row['recommendations'])) . '</p>';
                            echo '</div>';
                        }
                        if ($row['grade_justification']) {
                            echo '<div class="feedback-section justification">';
                            echo '<h5><i class="bi bi-info-circle-fill"></i> Grade Justification</h5>';
                            echo '<p>' . nl2br(htmlspecialchars($row['grade_justification'])) . '</p>';
                            echo '</div>';
                        }
                        if ($row['general_comments']) {
                            echo '<div class="feedback-section comments">';
                            echo '<h5><i class="bi bi-chat-text-fill"></i> General Comments</h5>';
                            echo '<p>' . nl2br(htmlspecialchars($row['general_comments'])) . '</p>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
                $stmt->close();

                if (!$hasFeedback) {
                    echo '<div class="no-feedback">';
                    echo '<i class="bi bi-chat-dots"></i>';
                    echo '<p>No feedback available yet. Your lecturer will provide detailed feedback after grading your assessments.</p>';
                    echo '<div class="feedback-info">';
                    echo '<h4>What to expect:</h4>';
                    echo '<ul>';
                    echo '<li><strong>Strengths:</strong> What you did well</li>';
                    echo '<li><strong>Areas for Improvement:</strong> Specific areas to focus on</li>';
                    echo '<li><strong>Recommendations:</strong> Actionable steps to improve</li>';
                    echo '<li><strong>Grade Justification:</strong> Explanation of your mark</li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.card-header-toggle').forEach(function(header) {
            header.addEventListener('click', function() {
                var card = header.parentElement;
                var body = card.querySelector('.card-body-toggle');
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    card.classList.add('expanded');
                } else {
                    body.style.display = 'none';
                    card.classList.remove('expanded');
                }
            });
        });
    });
    </script>
</body>
</html>
