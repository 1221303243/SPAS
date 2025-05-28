<?php
session_start();
// include('../../logic/db-connection.php');
// $conn = OpenCon();
require_once '../../auth/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
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

// Get student_id (assuming user_id in session is student_id)
$student_id = $_SESSION['user_id'];

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

$semester_start = new DateTime('2025-03-10'); // Adjust as needed

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

// Calculate weekly grades (percentage)
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
    $weekly_grades[$week] = $week_max > 0 ? round(($week_total / $week_max) * 100, 2) : null;
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Course Content</title>
    <link rel="stylesheet" href="../../css/course_content.css" />
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            fetch('get_chart_data.php?code=<?php echo urlencode($subjectCode); ?>')
                .then(response => response.json())
                .then(grades_by_date => {
                    console.log('grades_by_date:', grades_by_date); // DEBUG
                    var data = new google.visualization.DataTable();
                    data.addColumn('date', 'Date');
                    data.addColumn('number', 'Your Grade');
                    data.addColumn('number', 'Passing Grade');

                    grades_by_date.forEach(function(grade) {
                        if (grade.date && grade.percentage !== null) {
                            // Parse date string (YYYY-MM-DD) to JS Date object
                            var parts = grade.date.split('-');
                            var jsDate = new Date(parts[0], parts[1] - 1, parts[2]);
                            data.addRow([jsDate, grade.percentage, 50]);
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
                            slantedTextAngle: 45
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
    <script>
    console.log([
        ['Week', 'Grade'],
        <?php
        for ($week = 1; $week <= 14; $week++) {
            $grade = $weekly_grades[$week];
            echo "[$week, " . ($grade !== null ? $grade : "null") . "]";
            if ($week < 14) echo ",";
        }
        ?>
    ]);
    </script>
</head>
<body>
    <?php include 'sidebar_student.php'; ?>

    <div class="content-container">
        <div class="course-header">
            <h1><?php echo $subjectName . ' (' . htmlspecialchars($subjectCode) . ')'; ?></h1>
        </div>

        <div class="graph-container">
            <div id="curve_chart"></div>
        </div>

        <div class="feedback-container">
            <h2>Grades</h2>
            <div class="feedback-content">
                <?php
                // Check if there's feedback to display
                $hasFeedback = false; // This would be determined from the database in a real application
                
                if ($hasFeedback) {
                    // Loop through feedback items
                    // This is a placeholder for actual database-driven content
                    echo '<div class="feedback-item">';
                    echo '<div class="feedback-header">';
                    echo '<span class="feedback-date">March 15, 2023</span>';
                    echo '<span class="feedback-grade">Grade: A (92%)</span>';
                    echo '</div>';
                    echo '<div class="feedback-text">';
                    echo 'Excellent work on the recent calculus assignment. Your problem-solving approach shows deep understanding of the concepts.';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p class="no-feedback">No feedback available yet.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
