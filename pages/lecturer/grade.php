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
    $current_trimester = getCurrentTrimester($conn);
    if (isset($_SESSION['edu_level']) && $current_trimester) {
        $edu_level = $_SESSION['edu_level'];
        // Fetch classes and subjects taught by this lecturer and education level
        $sql = "SELECT DISTINCT c.class_id, c.class_name, s.subject_code, s.subject_name, s.subject_id, c.edu_level
                FROM classes c
                JOIN subjects s ON c.subject_id = s.subject_id
                WHERE c.lecturer_id = ? AND c.edu_level = ? AND s.trimester_id = ?
                ORDER BY s.subject_name, c.class_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $lecturer_id, $edu_level, $current_trimester['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        $stmt->close();
    }
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
    $sql = "SELECT assessment_id, assessment_type, category, weightage 
            FROM assessment_plans 
            WHERE subject_id = ? 
            ORDER BY category, assessment_type";
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

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="post" action="grade_submit.php" id="gradeForm" class="needs-validation" novalidate>
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
                                    <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class_id === $class['class_id'] ? 'selected' : ''; ?>
                                        >
                                        <?php echo htmlspecialchars($class['subject_name'] . ' (' . $class['subject_code'] . ') - ' . $class['class_name'] . ' [' . $class['edu_level'] . ']'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($selected_class_id > 0): ?>
                        <!-- Assessment Selection -->
                        <div class="mb-4">
                            <label for="assessmentSelect" class="form-label">Select Assessment:</label>
                            <div class="input-group">
                                <select class="form-select" id="assessmentSelect" name="assessment_id" required>
                                    <option value="" selected disabled>Choose assessment type...</option>
                                    <?php 
                                    $selected_assessment_id = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : (isset($_POST['assessment_id']) ? intval($_POST['assessment_id']) : '');
                                    if (empty($assessments)):
                                    ?>
                                        <option value="" disabled>No assessments configured for this subject</option>
                                    <?php else: ?>
                                        <optgroup label="Coursework">
                                            <?php foreach ($assessments as $assessment): ?>
                                                <?php if ($assessment['category'] === 'coursework'): ?>
                                                    <option value="<?php echo $assessment['assessment_id']; ?>" 
                                                            data-weightage="<?php echo $assessment['weightage']; ?>"
                                                            data-category="<?php echo $assessment['category']; ?>"
                                                            <?php echo ($selected_assessment_id == $assessment['assessment_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($assessment['assessment_type'] . ' (' . $assessment['weightage'] . '%)'); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Final Exam">
                                            <?php foreach ($assessments as $assessment): ?>
                                                <?php if ($assessment['category'] === 'final_exam'): ?>
                                                    <option value="<?php echo $assessment['assessment_id']; ?>"
                                                            data-weightage="<?php echo $assessment['weightage']; ?>"
                                                            data-category="<?php echo $assessment['category']; ?>"
                                                            <?php echo ($selected_assessment_id == $assessment['assessment_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($assessment['assessment_type'] . ' (' . $assessment['weightage'] . '%)'); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="loadGradesBtn" disabled>
                                    <i class="bi bi-check-circle"></i> Load Current Grades
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

                        <!-- Assessment Summary -->
                        <div id="assessmentSummary" class="alert alert-info d-none mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Category:</strong> <span id="assessmentCategory"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Weightage:</strong> <span id="assessmentWeightage"></span>%
                                </div>
                                <div class="col-md-4">
                                    <strong>Due Date:</strong> <span id="assessmentDueDate"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Grade Input Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Current Grade</th>
                                        <th>Marks</th>
                                        <th>Weighted Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($students)): ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td class="current-grade" data-student-id="<?php echo $student['student_id']; ?>">-</td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control grade-input" 
                                                           name="grades[<?php echo $student['student_id']; ?>]"
                                                           min="0" 
                                                           max="100" 
                                                           step="0.01"
                                                           required
                                                           data-student-id="<?php echo $student['student_id']; ?>">
                                                    <div class="invalid-feedback">
                                                        Please enter a valid mark between 0 and 100
                                                    </div>
                                                </td>
                                                <td class="weighted-score" data-student-id="<?php echo $student['student_id']; ?>">-</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No students found for this class.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Class Average</h6>
                                        <h3 id="classAverage">-</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Highest Score</h6>
                                        <h3 id="highestScore">-</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Lowest Score</h6>
                                        <h3 id="lowestScore">-</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="bi bi-save"></i> Save & Submit
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('gradeForm');
        const assessmentSelect = document.getElementById('assessmentSelect');
        const classSelect = document.getElementById('classSelect');
        const loadGradesBtn = document.getElementById('loadGradesBtn');
        const submitBtn = document.getElementById('submitBtn');
        const resetBtn = document.getElementById('resetBtn');
        const assessmentSummary = document.getElementById('assessmentSummary');
        const gradeInputs = document.querySelectorAll('.grade-input');
        
        // When assessment is selected, reload page with class_id and assessment_id in URL
        assessmentSelect.addEventListener('change', function() {
            const classId = classSelect.value;
            const assessmentId = this.value;
            if (assessmentId) {
                window.location.href = `grade.php?class_id=${classId}&assessment_id=${assessmentId}`;
            }
        });

        // Helper to get selected weightage
        function getSelectedWeightage() {
            const selectedOption = assessmentSelect.selectedOptions[0];
            return selectedOption ? parseFloat(selectedOption.getAttribute('data-weightage')) || 0 : 0;
        }

        // Update weighted score for a student
        function updateWeightedScore(studentId, marks) {
            const weightage = getSelectedWeightage();
            const weightedScore = (marks * weightage / 100).toFixed(2);
            document.querySelector(`.weighted-score[data-student-id="${studentId}"]`).textContent = isNaN(weightedScore) ? '-' : weightedScore;
            updateStatistics();
        }

        // Update statistics
        function updateStatistics() {
            const marks = Array.from(gradeInputs)
                .map(input => parseFloat(input.value))
                .filter(mark => !isNaN(mark));

            if (marks.length > 0) {
                const average = marks.reduce((a, b) => a + b, 0) / marks.length;
                document.getElementById('classAverage').textContent = average.toFixed(2);
                document.getElementById('highestScore').textContent = Math.max(...marks).toFixed(2);
                document.getElementById('lowestScore').textContent = Math.min(...marks).toFixed(2);
            } else {
                document.getElementById('classAverage').textContent = '-';
                document.getElementById('highestScore').textContent = '-';
                document.getElementById('lowestScore').textContent = '-';
            }
        }

        // Reset form
        function resetForm() {
            form.reset();
            assessmentSummary.classList.add('d-none');
            document.querySelectorAll('.weighted-score').forEach(el => el.textContent = '-');
            document.getElementById('classAverage').textContent = '-';
            document.getElementById('highestScore').textContent = '-';
            document.getElementById('lowestScore').textContent = '-';
            submitBtn.disabled = true;
        }

        // Reset button handler
        resetBtn.addEventListener('click', resetForm);

        // Grade input validation and calculation
        gradeInputs.forEach(input => {
            input.addEventListener('input', function() {
                const marks = parseFloat(this.value);
                if (marks >= 0 && marks <= 100) {
                    this.classList.remove('is-invalid');
                    updateWeightedScore(this.dataset.studentId, marks);
                    submitBtn.disabled = false;
                } else {
                    this.classList.add('is-invalid');
                    submitBtn.disabled = true;
                }
            });
        });

        // Update weighted scores if assessment changes (after reload)
        if (assessmentSelect.value) {
            gradeInputs.forEach(input => {
                if (input.value) {
                    updateWeightedScore(input.dataset.studentId, parseFloat(input.value));
                }
            });
        }

        // Form submission validation
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                if (!confirm('Are you sure you want to submit these grades? This action cannot be undone.')) {
                    event.preventDefault();
                }
            }
            form.classList.add('was-validated');
        });
    });
    </script>
</body>
</html>
