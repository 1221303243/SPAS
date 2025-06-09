<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

// Fetch classes taught by this lecturer
$class_sql = "SELECT c.class_id, c.class_name, s.subject_name FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.lecturer_id = ?";
$stmt = $conn->prepare($class_sql);
$stmt->bind_param('i', $lecturer_id);
$stmt->execute();
$class_result = $stmt->get_result();
$classes = $class_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch students and assessments if class is selected
$students = $assessments = [];
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
                    <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['subject_name'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (isset($_GET['class_id'])): ?>
    <form method="post" class="feedback-form">
        <input type="hidden" name="class_id" value="<?php echo intval($_GET['class_id']); ?>" />
        <label for="student_id">Student:</label>
        <select name="student_id" id="student_id" required>
            <option value="">Select Student</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['student_id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="assessment_id">Assessment:</label>
        <select name="assessment_id" id="assessment_id" required>
            <option value="">Select Assessment</option>
            <?php foreach ($assessments as $assessment): ?>
                <option value="<?php echo $assessment['assessment_id']; ?>"><?php echo htmlspecialchars($assessment['assessment_type']); ?></option>
            <?php endforeach; ?>
        </select>
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
    <?php endif; ?>
</div>
</body>
</html> 