<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';

$user_id = $_SESSION['user_id'];
$lecturer_id = null;
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($lecturer_id);
$stmt->fetch();
$stmt->close();

$current_trimester = getCurrentTrimester($conn);

// Fetch classes taught by this lecturer
if (isset($_SESSION['edu_level']) && $current_trimester) {
    $edu_level = $_SESSION['edu_level'];
    $class_sql = "SELECT c.class_id, c.class_name, s.subject_name, c.edu_level
                 FROM classes c
                 JOIN subjects s ON c.subject_id = s.subject_id
                 WHERE c.lecturer_id = ? AND c.edu_level = ? AND s.trimester_id = ?";
    $stmt = $conn->prepare($class_sql);
    $stmt->bind_param('isi', $lecturer_id, $edu_level, $current_trimester['id']);
    $stmt->execute();
    $class_result = $stmt->get_result();
    $classes = $class_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch students and assessments if class is selected
$students = $assessments = [];
$selected_assessment_id = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : null;
if (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
    // Students
    $student_sql = "SELECT s.student_id, s.name FROM student_classes sc JOIN students s ON sc.student_id = s.student_id WHERE sc.class_id = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    $students = $student_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Assessments
    $assessment_sql = "SELECT ap.assessment_id, ap.assessment_type FROM assessment_plans ap WHERE ap.subject_id = (SELECT subject_id FROM classes WHERE class_id = ?)";
    $stmt = $conn->prepare($assessment_sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $assessment_result = $stmt->get_result();
    $assessments = $assessment_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);
    $assessment_id = intval($_POST['assessment_id']);
    $strengths = trim($_POST['strengths']);
    $areas_for_improvement = trim($_POST['areas_for_improvement']);
    $recommendations = trim($_POST['recommendations']);
    $grade_justification = trim($_POST['grade_justification']);
    $general_comments = trim($_POST['general_comments']);
    // Find grade_id for this student/assessment/class
    $grade_id = null;
    $stmt = $conn->prepare("SELECT grade_id FROM grades WHERE student_id = ? AND assessment_id = ? AND class_id = ?");
    $stmt->bind_param('iii', $student_id, $assessment_id, $class_id);
    $stmt->execute();
    $stmt->bind_result($grade_id);
    $stmt->fetch();
    $stmt->close();
    if ($grade_id) {
        $stmt = $conn->prepare("INSERT INTO feedback (grade_id, assessment_id, student_id, class_id, lecturer_id, strengths, areas_for_improvement, recommendations, grade_justification, general_comments, feedback_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->bind_param('iiiiisssss', $grade_id, $assessment_id, $student_id, $class_id, $lecturer_id, $strengths, $areas_for_improvement, $recommendations, $grade_justification, $general_comments);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Failed to save feedback.';
        }
        $stmt->close();
    } else {
        $error = 'No grade record found for this student and assessment.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Lecturer Feedback Input</title>
    <link rel="stylesheet" href="../../css/feedback_input.css" />
    <style>
        .feedback-input-container {
            max-width: 900px;
        }
    </style>
</head>
<body>
<?php include 'topbar.php'; ?>
<div class="feedback-input-container">
    <h1>Input Feedback for Student Assessment</h1>
    <?php if ($success): ?>
        <div class="success-message">Feedback submitted successfully!</div>
    <?php elseif ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="get" class="select-form">
        <label for="class_id">Class:</label>
        <select name="class_id" id="class_id" onchange="this.form.submit()" required>
            <option value="">Select Class</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?php echo $class['class_id']; ?>" <?php if (isset($_GET['class_id']) && $_GET['class_id'] == $class['class_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['subject_name'] . ') [' . $class['edu_level'] . ']'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($_GET['class_id'])): ?>
        <label for="assessment_id">Assessment:</label>
        <select name="assessment_id" id="assessment_id" onchange="this.form.submit()" required>
            <option value="">Select Assessment</option>
            <?php foreach ($assessments as $assessment): ?>
                <option value="<?php echo $assessment['assessment_id']; ?>" <?php if ($selected_assessment_id == $assessment['assessment_id']) echo 'selected'; ?>><?php echo htmlspecialchars($assessment['assessment_type']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </form>
    <?php
    // Show student list if class and assessment are selected
    if (isset($_GET['class_id']) && $selected_assessment_id) {
        echo '<div class="student-list">';
        foreach ($students as $student) {
            // Check if feedback exists for this student/assessment/class
            $feedback_exists = false;
            $stmt = $conn->prepare("SELECT feedback_id FROM feedback WHERE student_id = ? AND assessment_id = ? AND class_id = ? AND feedback_status = 'published' LIMIT 1");
            $stmt->bind_param('iii', $student['student_id'], $selected_assessment_id, $class_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $feedback_exists = true;
            }
            $stmt->close();
            echo '<div class="student-list-item">';
            echo '<div class="student-info">';
            echo '<span class="feedback-indicator ' . ($feedback_exists ? 'done' : 'not-done') . '">' . ($feedback_exists ? '&#10003;' : '&#10007;') . '</span>';
            echo htmlspecialchars($student['name']);
            echo '</div>';
            // Button to open feedback form for this student/assessment
            $url = '?class_id=' . $class_id . '&assessment_id=' . $selected_assessment_id . '&student_id=' . $student['student_id'];
            echo '<a href="' . $url . '" class="input-feedback-btn">' . ($feedback_exists ? 'Edit Feedback' : 'Input Feedback') . '</a>';
            echo '</div>';
        }
        echo '</div>';
    }
    // Show feedback form if student is selected
    if (isset($_GET['class_id']) && $selected_assessment_id && isset($_GET['student_id'])) {
    ?>
    <form method="post" class="feedback-form" id="feedbackForm">
        <button type="button" class="close-feedback-btn" onclick="closeFeedbackForm()">&times; Close</button>
        <input type="hidden" name="class_id" value="<?php echo intval($_GET['class_id']); ?>" />
        <input type="hidden" name="assessment_id" value="<?php echo intval($_GET['assessment_id']); ?>" />
        <input type="hidden" name="student_id" value="<?php echo intval($_GET['student_id']); ?>" />
        <label>Student:</label>
        <div style="font-weight:700; margin-bottom:8px; color:#1976d2;">
            <?php
            foreach ($students as $student) {
                if ($student['student_id'] == $_GET['student_id']) {
                    echo htmlspecialchars($student['name']);
                    break;
                }
            }
            ?>
        </div>
        <label for="strengths">Strengths:</label>
        <textarea name="strengths" id="strengths" rows="3" required></textarea>
        <label for="areas_for_improvement">Areas for Improvement:</label>
        <textarea name="areas_for_improvement" id="areas_for_improvement" rows="3" required></textarea>
        <label for="recommendations">Recommendations:</label>
        <textarea name="recommendations" id="recommendations" rows="3" required></textarea>
        <label for="grade_justification">Grade Justification:</label>
        <textarea name="grade_justification" id="grade_justification" rows="2"></textarea>
        <label for="general_comments">General Comments:</label>
        <textarea name="general_comments" id="general_comments" rows="2"></textarea>
        <button type="submit" class="submit-btn">Submit Feedback</button>
    </form>
    <script>
    function closeFeedbackForm() {
        // Remove student_id from URL and reload
        const url = new URL(window.location.href);
        url.searchParams.delete('student_id');
        window.location.href = url.toString();
    }
    // Optionally, after successful submit, auto-close the form
    if (document.querySelector('.success-message')) {
        setTimeout(closeFeedbackForm, 1200);
    }
    </script>
    <?php } ?>
</div>
</body>
</html> 