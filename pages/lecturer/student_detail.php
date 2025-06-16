<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../../auth/index.php");
    exit();
}

require_once '../../auth/db_connection.php';

// Get student_id and class_id from URL
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($student_id === 0 || $class_id === 0) {
    $_SESSION['error'] = "Invalid student or class ID.";
    header("Location: student_list.php");
    exit();
}

// Get student details and class information
$sql = "
    SELECT 
        s.student_id, 
        s.name,
        c.class_name,
        sub.subject_code,
        sub.subject_name
    FROM students s
    JOIN student_classes sc ON s.student_id = sc.student_id
    JOIN classes c ON sc.class_id = c.class_id
    JOIN subjects sub ON c.subject_id = sub.subject_id
    WHERE s.student_id = ? AND sc.class_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $class_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['error'] = "Student not found in this class.";
    header("Location: student_list.php");
    exit();
}

// Get assessment details and marks
$sql = "
    SELECT 
        ap.assessment_id,
        ap.assessment_type,
        ap.category,
        ap.weightage,
        g.marks,
        g.grade,
        g.date_recorded
    FROM assessment_plans ap
    LEFT JOIN grades g ON g.assessment_id = ap.assessment_id 
        AND g.student_id = ? 
        AND g.class_id = ?
    WHERE ap.subject_id = (
        SELECT subject_id 
        FROM classes 
        WHERE class_id = ?
    )
    ORDER BY ap.category, ap.assessment_type
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $student_id, $class_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$assessments = [];
$coursework_total = 0;
$coursework_weight = 0;
$final_exam_total = 0;
$final_exam_weight = 0;

while ($row = $result->fetch_assoc()) {
    $assessments[] = $row;
    
    // Calculate totals
    if ($row['category'] === 'coursework') {
        if (isset($row['weighted_marks']) && $row['weighted_marks'] !== null) {
            $coursework_total += $row['weighted_marks'];
        } elseif ($row['marks'] !== null) {
            $coursework_total += $row['marks'] * $row['weightage'] / 100;
        }
        $coursework_weight += $row['weightage'];
    } else {
        if (isset($row['weighted_marks']) && $row['weighted_marks'] !== null) {
            $final_exam_total += $row['weighted_marks'];
        } elseif ($row['marks'] !== null) {
            $final_exam_total += $row['marks'] * $row['weightage'] / 100;
        }
        $final_exam_weight += $row['weightage'];
    }
}

$stmt->close();

// Calculate percentages
$coursework_percentage = $coursework_total; // sum of weighted marks for coursework
$final_exam_percentage = $final_exam_total; // sum of weighted marks for final exam
$overall_percentage = $coursework_percentage + $final_exam_percentage; // sum, not average

// Calculate dynamic pass marks
$coursework_pass_mark = $coursework_weight / 2;
$final_exam_pass_mark = $final_exam_weight / 2;

// Determine pass/fail status
$coursework_status = $coursework_percentage >= $coursework_pass_mark ? 'PASS' : 'FAIL';
$final_exam_status = $final_exam_percentage >= $final_exam_pass_mark ? 'PASS' : 'FAIL';
$overall_status = ($coursework_status === 'PASS' && $final_exam_status === 'PASS') ? 'PASS' : 'FAIL';

// Function to calculate final letter grade based on overall percentage and status
function calculateOverallGrade($overallPercentage, $overallStatus) {
    if ($overallStatus === 'FAIL') {
        return 'F'; // If overall status is FAIL, the grade is F
    }

    if ($overallPercentage >= 95) {
        return 'A+';
    } elseif ($overallPercentage >= 90) {
        return 'A';
    } elseif ($overallPercentage >= 85) {
        return 'A-';
    } elseif ($overallPercentage >= 80) {
        return 'B+';
    } elseif ($overallPercentage >= 75) {
        return 'B';
    } elseif ($overallPercentage >= 70) {
        return 'B-';
    } elseif ($overallPercentage >= 65) {
        return 'C+';
    } elseif ($overallPercentage >= 60) {
        return 'C';
    } elseif ($overallPercentage >= 55) {
        return 'C-';
    } elseif ($overallPercentage >= 50) {
        return 'D';
    } else {
        return 'F'; // Should ideally be caught by overallStatus 'FAIL' but as a fallback
    }
}

// Calculate the final grade based on the overall percentage and status
$final_grade = calculateOverallGrade($overall_percentage, $overall_status);

// Map grade to Bootstrap color class
function getGradeColorClass($grade) {
    if (in_array($grade, ['A+', 'A', 'A-'])) {
        return 'bg-success'; // Green
    } elseif (in_array($grade, ['B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'])) {
        return 'bg-warning text-dark'; // Yellow/Orange
    } elseif (in_array($grade, ['E', 'F'])) {
        return 'bg-danger'; // Red
    }
    return 'bg-secondary'; // Default/gray
}
$grade_color_class = getGradeColorClass($final_grade);

// Add this after the existing PHP code, before the HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_grade') {
    $assessment_id = intval($_POST['assessment_id']);
    $marks = floatval($_POST['marks']);
    
    if ($marks >= 0 && $marks <= 100) {
        // Get assessment details
        $stmt = $conn->prepare("
            SELECT ap.subject_id, ap.category, ap.assessment_type
            FROM assessment_plans ap
            WHERE ap.assessment_id = ?
        ");
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $assessment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($assessment) {
            // Update the grade
            $stmt = $conn->prepare("
                INSERT INTO grades (student_id, subject_id, assessment_id, class_id, marks, category, date_recorded)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    marks = VALUES(marks)
            ");
            $stmt->bind_param("iiiids", 
                $student_id, 
                $assessment['subject_id'], 
                $assessment_id, 
                $class_id, 
                $marks, 
                $assessment['category']
            );
            
            if ($stmt->execute()) {
                // Fetch all assessments and their weightages for this student, subject, and class
                $assessments_sql = "
                    SELECT ap.assessment_id, ap.weightage, ap.category, g.marks
                    FROM assessment_plans ap
                    LEFT JOIN grades g ON g.assessment_id = ap.assessment_id AND g.student_id = ? AND g.class_id = ?
                    WHERE ap.subject_id = ?
                    ORDER BY ap.category, ap.assessment_id
                ";
                $assessments_stmt = $conn->prepare($assessments_sql);
                $assessments_stmt->bind_param("iii", $student_id, $class_id, $assessment['subject_id']);
                $assessments_stmt->execute();
                $result = $assessments_stmt->get_result();
                $coursework_total = 0;
                $final_exam_total = 0;
                $total_marks = 0;
                $coursework_weight = 0;
                $final_exam_weight = 0;
                $assessment_data = [];
                while ($row = $result->fetch_assoc()) {
                    $marks = is_null($row['marks']) ? null : floatval($row['marks']);
                    $weighted_marks = !is_null($marks) ? $marks * $row['weightage'] / 100 : 0;
                    $assessment_data[$row['assessment_id']] = [
                        'weightage' => $row['weightage'],
                        'category' => $row['category'],
                        'marks' => $marks,
                        'weighted_marks' => $weighted_marks
                    ];
                    if ($row['category'] === 'coursework') {
                        $coursework_total += $weighted_marks;
                        $coursework_weight += $row['weightage'];
                    } else {
                        $final_exam_total += $weighted_marks;
                        $final_exam_weight += $row['weightage'];
                    }
                }
                $assessments_stmt->close();
                $total_marks = $coursework_total + $final_exam_total;
                // Calculate grade (same as grade_submit.php logic)
                $grade = '';
                $coursework_pass = ($coursework_weight > 0) ? ($coursework_total >= ($coursework_weight / 2)) : true;
                $final_exam_pass = ($final_exam_weight > 0) ? ($final_exam_total >= ($final_exam_weight / 2)) : true;
                if ($coursework_pass && $final_exam_pass) {
                    if ($total_marks >= 90) {
                        $grade = 'A+';
                    } elseif ($total_marks >= 85) {
                        $grade = 'A';
                    } elseif ($total_marks >= 80) {
                        $grade = 'A-';
                    } elseif ($total_marks >= 75) {
                        $grade = 'B+';
                    } elseif ($total_marks >= 70) {
                        $grade = 'B';
                    } elseif ($total_marks >= 65) {
                        $grade = 'B-';
                    } elseif ($total_marks >= 60) {
                        $grade = 'C+';
                    } elseif ($total_marks >= 55) {
                        $grade = 'C';
                    } elseif ($total_marks >= 50) {
                        $grade = 'C-';
                    } elseif ($total_marks >= 45) {
                        $grade = 'D';
                    } else {
                        $grade = 'F';
                    }
                } else {
                    $grade = 'F';
                }
                // Update all grades for this student, subject, and class with cumulative total_marks
                $cumulative_total = 0;
                foreach ($assessment_data as $aid => $adata) {
                    $cumulative_total += $adata['weighted_marks'];
                    $update = $conn->prepare("
                        UPDATE grades
                        SET weighted_marks = ?, coursework_total = ?, final_exam_total = ?, total_marks = ?, grade = ?
                        WHERE student_id = ? AND class_id = ? AND subject_id = ? AND assessment_id = ?
                    ");
                    $update->bind_param("ddddsiiii", $adata['weighted_marks'], $coursework_total, $final_exam_total, $cumulative_total, $grade, $student_id, $class_id, $assessment['subject_id'], $aid);
                    $update->execute();
                    $update->close();
                }
                $_SESSION['success'] = "Grade updated successfully.";
            } else {
                $_SESSION['error'] = "Error updating grade.";
            }
        } else {
            $_SESSION['error'] = "Invalid assessment.";
        }
    } else {
        $_SESSION['error'] = "Marks must be between 0 and 100.";
    }
    
    // Redirect to refresh the page
    header("Location: student_detail.php?student_id=" . $student_id . "&class_id=" . $class_id);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Student Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/student_list.css">
    <link rel="stylesheet" href="../../css/student_detail.css">
</head>
<body>
    <?php include 'topbar.php'; ?>

    <!-- Subject Code Banner -->
    <div class="subject-banner">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><?= htmlspecialchars($student['subject_code'] . ' - ' . $student['subject_name']) ?></h2>
                <a href="student_list.php?class_id=<?= $class_id ?>" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Student List
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Student Information -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="card-title"><?= htmlspecialchars($student['name']) ?></h3>
                        <p class="text-muted mb-0">Student ID: <?= htmlspecialchars($student['student_id']) ?></p>
                        <p class="text-muted">Class: <?= htmlspecialchars($student['class_name']) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4 class="mb-3">Final Grade</h4>
                        <span class="badge grade-badge <?= $grade_color_class ?>">
                            <?= $final_grade ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Coursework</h5>
                        <h2 class="mb-3"><?= number_format($coursework_percentage, 1) ?>%</h2>
                        <span class="badge status-badge <?= $coursework_status === 'PASS' ? 'status-pass' : 'status-fail' ?>">
                            <?= $coursework_status ?>
                        </span>
                        <p class="text-muted mt-2 mb-0">Weight: <?= $coursework_weight ?>% (Pass mark: <?= number_format($coursework_pass_mark, 1) ?>%)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Final Exam</h5>
                        <h2 class="mb-3"><?= number_format($final_exam_percentage, 1) ?>%</h2>
                        <span class="badge status-badge <?= $final_exam_status === 'PASS' ? 'status-pass' : 'status-fail' ?>">
                            <?= $final_exam_status ?>
                        </span>
                        <p class="text-muted mt-2 mb-0">Weight: <?= $final_exam_weight ?>% (Pass mark: <?= number_format($final_exam_pass_mark, 1) ?>%)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Overall Result</h5>
                        <h2 class="mb-3"><?= number_format($overall_percentage, 1) ?>%</h2>
                        <span class="badge status-badge <?= $overall_status === 'PASS' ? 'status-pass' : 'status-fail' ?>">
                            <?= $overall_status ?>
                        </span>
                        <p class="text-muted mt-2 mb-0">Final Grade: <?= $final_grade ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Breakdown -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Assessment Breakdown</h4>
                
                <!-- Coursework Assessments -->
                <h5 class="mb-3">Coursework</h5>
                <div class="row mb-4">
                    <?php
                    $coursework_assessments = array_filter($assessments, function($a) {
                        return $a['category'] === 'coursework';
                    });
                    foreach ($coursework_assessments as $assessment):
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card assessment-card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($assessment['assessment_type']) ?></h6>
                                <p class="text-muted mb-2">Weight: <?= $assessment['weightage'] ?>%</p>
                                <?php if ($assessment['marks'] !== null): ?>
                                    <h3 class="mb-2">
                                        <?php 
                                        if (isset($assessment['weighted_marks'])) {
                                            echo number_format($assessment['weighted_marks'], 1) . '%';
                                        } else {
                                            // fallback for old records
                                            echo number_format($assessment['marks'] * $assessment['weightage'] / 100, 1) . '%';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">
                                        Date: <?= date('d M Y', strtotime($assessment['date_recorded'])) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Not graded yet</p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $assessment['assessment_id'] ?>">
                                        <i class="bi bi-pencil"></i> Edit Marks
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal for each assessment -->
                    <div class="modal fade" id="editModal<?= $assessment['assessment_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Marks - <?= htmlspecialchars($assessment['assessment_type']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update_grade">
                                        <input type="hidden" name="assessment_id" value="<?= $assessment['assessment_id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Assessment Type</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($assessment['assessment_type']) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', $assessment['category'])) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Weightage</label>
                                            <input type="text" class="form-control" value="<?= $assessment['weightage'] ?>%" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="marks<?= $assessment['assessment_id'] ?>" class="form-label">Marks</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="marks<?= $assessment['assessment_id'] ?>" 
                                                   name="marks" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.01"
                                                   value="<?= $assessment['marks'] ?? '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Please enter a valid mark between 0 and 100
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Final Exam -->
                <h5 class="mb-3">Final Exam</h5>
                <div class="row">
                    <?php
                    $final_assessments = array_filter($assessments, function($a) {
                        return $a['category'] === 'final_exam';
                    });
                    foreach ($final_assessments as $assessment):
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card assessment-card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($assessment['assessment_type']) ?></h6>
                                <p class="text-muted mb-2">Weight: <?= $assessment['weightage'] ?>%</p>
                                <?php if ($assessment['marks'] !== null): ?>
                                    <h3 class="mb-2">
                                        <?php 
                                        if (isset($assessment['weighted_marks'])) {
                                            echo number_format($assessment['weighted_marks'], 1) . '%';
                                        } else {
                                            // fallback for old records
                                            echo number_format($assessment['marks'] * $assessment['weightage'] / 100, 1) . '%';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">
                                        Date: <?= date('d M Y', strtotime($assessment['date_recorded'])) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Not graded yet</p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $assessment['assessment_id'] ?>">
                                        <i class="bi bi-pencil"></i> Edit Marks
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal for each assessment -->
                    <div class="modal fade" id="editModal<?= $assessment['assessment_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Marks - <?= htmlspecialchars($assessment['assessment_type']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update_grade">
                                        <input type="hidden" name="assessment_id" value="<?= $assessment['assessment_id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Assessment Type</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($assessment['assessment_type']) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', $assessment['category'])) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Weightage</label>
                                            <input type="text" class="form-control" value="<?= $assessment['weightage'] ?>%" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="marks<?= $assessment['assessment_id'] ?>" class="form-label">Marks</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="marks<?= $assessment['assessment_id'] ?>" 
                                                   name="marks" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.01"
                                                   value="<?= $assessment['marks'] ?? '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Please enter a valid mark between 0 and 100
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add success/error message display -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?= $_SESSION['success'] ?>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?= $_SESSION['error'] ?>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add form validation script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Auto-hide toasts after 5 seconds
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            setTimeout(() => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            }, 5000);
        });
    });
    </script>
</body>
</html> 