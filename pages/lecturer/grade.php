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

include 'topbar.php';

// Get lecturer's classes and subjects
$user_id = $_SESSION['user_id'];
$lecturer_id = null;
$classes = [];

// Get lecturer_id
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$stmt->close();

if ($lecturer) {
    $lecturer_id = $lecturer['lecturer_id'];
    // Fetch classes and subjects taught by this lecturer
    $sql = "SELECT DISTINCT c.class_id, c.class_name, s.subject_code, s.subject_name, s.subject_id
            FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.lecturer_id = ?
            ORDER BY s.subject_name, c.class_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt->close();
}

// Get selected class_id from URL if any
$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Get subject_id for the selected class
$subject_id = null;
if ($selected_class_id > 0) {
    foreach ($classes as $class) {
        if ($class['class_id'] == $selected_class_id) {
            $subject_id = $class['subject_id'];
            break;
        }
    }
}

// Fetch assessments for the selected subject
$assessments = [];
if ($subject_id) {
    $sql = "SELECT assessment_id, assessment_type, weightage 
            FROM assessment_plans 
            WHERE subject_id = ? 
            ORDER BY assessment_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assessments[] = $row;
    }
    $stmt->close();
}

// Fetch students for the selected class
$students = [];
if ($selected_class_id > 0) {
    $sql = "SELECT s.student_id, s.name FROM student_classes sc JOIN students s ON sc.student_id = s.student_id WHERE sc.class_id = ? ORDER BY s.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Grade Input</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/grade.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <!-- Subject Code Banner -->
    <div class="subject-banner">
        <div class="container">
            <h2 class="mb-0">
                <?php 
                if ($selected_class_id > 0) {
                    foreach ($classes as $class) {
                        if ($class['class_id'] == $selected_class_id) {
                            echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']);
                            break;
                        }
                    }
                } else {
                    echo "Select a Class";
                }
                ?>
            </h2>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success mt-3">Grades submitted successfully!</div>
    <?php endif; ?>

    <form method="post" action="grade_submit.php">
        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($selected_class_id); ?>">
        <input type="hidden" name="assessment_id" id="hiddenAssessmentId" value="">
        <!-- Main Content -->
        <div class="container mt-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-4">Grade Input</h3>
                    <!-- Class Selection Dropdown -->
                    <div class="mb-4">
                        <label for="classSelect" class="form-label">Select Class:</label>
                        <div class="input-group">
                            <select class="form-select" id="classSelect" onchange="window.location.href='grade.php?class_id=' + this.value">
                                <option value="" <?php echo $selected_class_id === 0 ? 'selected' : ''; ?> disabled>Choose a class...</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class_id === $class['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['subject_name'] . ' (' . $class['subject_code'] . ') - ' . $class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($selected_class_id > 0): ?>
                    <!-- Assessment Selection (only show if class is selected) -->
                    <div class="mb-4">
                        <label for="assessmentSelect" class="form-label">Select Assessment:</label>
                        <div class="input-group">
                            <select class="form-select" id="assessmentSelect" name="assessment_id" onchange="document.getElementById('hiddenAssessmentId').value = this.value;">
                                <option value="" selected disabled>Choose assessment type...</option>
                                <?php if (empty($assessments)): ?>
                                    <option value="" disabled>No assessments configured for this subject</option>
                                <?php else: ?>
                                    <?php foreach ($assessments as $assessment): ?>
                                        <option value="<?php echo $assessment['assessment_id']; ?>">
                                            <?php echo htmlspecialchars($assessment['assessment_type'] . ' (' . $assessment['weightage'] . '%)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="loadGradesBtn">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </div>
                        <?php if (empty($assessments)): ?>
                            <div class="mt-2">
                                <a href="plan.php" class="btn btn-outline-primary">
                                    <i class="bi bi-plus-circle"></i> Configure Assessments
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><input type="number" class="form-control" min="0" max="100" name="grades[<?php echo $student['student_id']; ?>]"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No students found for this class.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-primary" type="submit">Save & Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script>
    // Keep assessment_id in sync for submission
    const assessmentSelect = document.getElementById('assessmentSelect');
    const hiddenAssessmentId = document.getElementById('hiddenAssessmentId');
    if (assessmentSelect && hiddenAssessmentId) {
        assessmentSelect.addEventListener('change', function() {
            hiddenAssessmentId.value = this.value;
        });
        // Set initial value if already selected
        if (assessmentSelect.value) {
            hiddenAssessmentId.value = assessmentSelect.value;
        }
    }
    </script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
