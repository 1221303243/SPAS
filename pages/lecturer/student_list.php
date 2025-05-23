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

function getRiskLevel($grade) {
    $low = ['A', 'A-', 'A+', 'B+'];
    $medium = ['B', 'B-','C+'];
    $high = ['C', 'C-', 'D', 'E', 'F'];

    if (in_array($grade, $low)) {
        return ['label' => 'Low', 'class' => 'badge bg-success'];
    } elseif (in_array($grade, $medium)) {
        return ['label' => 'Medium', 'class' => 'badge bg-warning text-dark'];
    } else {
        return ['label' => 'High', 'class' => 'badge bg-danger'];
    }
}

// Get class_id from URL
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
if ($class_id === 0) {
    echo "Invalid class ID.";
    exit();
}

require_once '../../auth/db_connection.php';

// Fetch students and their grades for this class
$sql = "
    SELECT 
        s.student_id, 
        s.name, 
        g.grade, 
        g.marks
    FROM student_classes sc
    JOIN students s ON sc.student_id = s.student_id
    LEFT JOIN grades g ON g.grade_id = (
        SELECT grade_id FROM grades 
        WHERE student_id = s.student_id AND class_id = sc.class_id
        ORDER BY date_recorded DESC, grade_id DESC
        LIMIT 1
    )
    WHERE sc.class_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Calculate summary statistics
$total_students = count($students);

// At-risk students: those with 'High' risk level
$at_risk_students = 0;
$total_marks = 0;
$marks_count = 0;

foreach ($students as $student) {
    $risk = getRiskLevel($student['grade']);
    if ($risk['label'] === 'High') {
        $at_risk_students++;
    }
    if (is_numeric($student['marks'])) {
        $total_marks += $student['marks'];
        $marks_count++;
    }
}

$average_performance = $marks_count > 0 ? round($total_marks / $marks_count, 2) : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Student List</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/student_list.css">
</head>

<body>
    <?php include 'topbar.php'; ?>

    <!-- Subject Code Banner -->
    <div class="subject-banner">
        <div class="container">
            <h2 class="mb-0">Student Performance Analytics</h2>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row stats">
            <div class="col-md-4 mb-3">
                <div class="card green">
                    <div class="card-body d-flex align-items-center">
                        <span class="icon me-3"><i class="bi bi-people-fill fs-1"></i></span>
                        <div>
                            <h3 class="mb-0"><?= $total_students ?></h3>
                            <small>Total Students</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card red">
                    <div class="card-body d-flex align-items-center">
                        <span class="icon me-3"><i class="bi bi-exclamation-triangle-fill fs-1"></i></span>
                        <div>
                            <h3 class="mb-0"><?= $at_risk_students ?></h3>
                            <small>At Risk Students</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card blue">
                    <div class="card-body d-flex align-items-center">
                        <span class="icon me-3"><i class="bi bi-graph-up fs-1"></i></span>
                        <div>
                            <h3 class="mb-0"><?= $average_performance ?>%</h3>
                            <small>Average Performance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h3 class="mb-4">Student List</h3>
                
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" class="form-control" placeholder="Search students...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" /></th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Risk</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($students as $student):
                            $risk = getRiskLevel($student['grade']);
                        ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" /></td>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['grade']) ?></td>
                                <td><span class="<?= $risk['class'] ?>"><?= $risk['label'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>Showing 5 of 30 students</div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
