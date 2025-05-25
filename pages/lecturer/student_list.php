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

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$risk_filter = isset($_GET['risk_filter']) ? $_GET['risk_filter'] : '';

// Modify the SQL query to include search and filter
$sql = "
    SELECT 
        s.student_id, 
        s.name, 
        g.grade, 
        g.marks,
        g.grade_id
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

$params = [$class_id];
$types = "i";

if (!empty($search)) {
    $sql .= " AND (s.student_id LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($risk_filter)) {
    $sql .= " AND (";
    switch ($risk_filter) {
        case 'high':
            $sql .= "g.grade IN ('C', 'C-', 'D', 'E', 'F')";
            break;
        case 'medium':
            $sql .= "g.grade IN ('B', 'B-', 'C+')";
            break;
        case 'low':
            $sql .= "g.grade IN ('A', 'A-', 'A+', 'B+')";
            break;
    }
    $sql .= ")";
}

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT s.student_id) as total 
              FROM student_classes sc
              JOIN students s ON sc.student_id = s.student_id
              WHERE sc.class_id = ?";

if (!empty($search)) {
    $count_sql .= " AND (s.student_id LIKE ? OR s.name LIKE ?)";
}

if (!empty($risk_filter)) {
    $count_sql .= " AND (";
    switch ($risk_filter) {
        case 'high':
            $count_sql .= "EXISTS (
                SELECT 1 FROM grades g 
                WHERE g.student_id = s.student_id 
                AND g.class_id = sc.class_id 
                AND g.grade IN ('C', 'C-', 'D', 'E', 'F')
            )";
            break;
        case 'medium':
            $count_sql .= "EXISTS (
                SELECT 1 FROM grades g 
                WHERE g.student_id = s.student_id 
                AND g.class_id = sc.class_id 
                AND g.grade IN ('B', 'B-', 'C+')
            )";
            break;
        case 'low':
            $count_sql .= "EXISTS (
                SELECT 1 FROM grades g 
                WHERE g.student_id = s.student_id 
                AND g.class_id = sc.class_id 
                AND g.grade IN ('A', 'A-', 'A+', 'B+')
            )";
            break;
    }
    $count_sql .= ")";
}

$stmt = $conn->prepare($count_sql);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("iss", $class_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $class_id);
}
$stmt->execute();
$total_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Calculate statistics
$stats_sql = "
    SELECT 
        COUNT(DISTINCT s.student_id) as total_students,
        SUM(CASE 
            WHEN EXISTS (
                SELECT 1 FROM grades g 
                WHERE g.student_id = s.student_id 
                AND g.class_id = sc.class_id 
                AND g.grade IN ('C', 'C-', 'D', 'E', 'F')
            ) THEN 1 ELSE 0 
        END) as at_risk_students,
        AVG(
            CASE 
                WHEN g.marks IS NOT NULL THEN g.marks 
                ELSE NULL 
            END
        ) as average_performance
    FROM student_classes sc
    JOIN students s ON sc.student_id = s.student_id
    LEFT JOIN grades g ON g.grade_id = (
        SELECT grade_id 
        FROM grades 
        WHERE student_id = s.student_id 
        AND class_id = sc.class_id
        ORDER BY date_recorded DESC, grade_id DESC
        LIMIT 1
    )
    WHERE sc.class_id = ?
";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Extract statistics with defaults
$total_students = $stats['total_students'] ?? 0;
$at_risk_students = $stats['at_risk_students'] ?? 0;
$average_performance = round($stats['average_performance'] ?? 0, 2);

// Add pagination to main query
$sql .= " ORDER BY s.name LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Execute main query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Handle grade update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_grade') {
    $student_id = intval($_POST['student_id']);
    $grade = strtoupper(trim($_POST['grade']));
    $marks = intval($_POST['marks']);
    
    // Validate grade format
    $valid_grades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'E', 'F'];
    if (!in_array($grade, $valid_grades)) {
        $error_message = "Invalid grade format";
    } elseif ($marks < 0 || $marks > 100) {
        $error_message = "Marks must be between 0 and 100";
    } else {
        // Update or insert grade
        $update_sql = "
            INSERT INTO grades (student_id, class_id, grade, marks, date_recorded)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            grade = VALUES(grade),
            marks = VALUES(marks),
            date_recorded = NOW()
        ";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iisi", $student_id, $class_id, $grade, $marks);
        
        if ($stmt->execute()) {
            $success_message = "Grade updated successfully";
            // Refresh the page to show updated data
            header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&page=" . $current_page);
            exit();
        } else {
            $error_message = "Error updating grade";
        }
        $stmt->close();
    }
}

// Calculate total pages
$total_pages = ceil($total_count / $items_per_page);

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
                    <form method="GET" class="d-flex gap-2" style="max-width: 600px;">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" name="search" class="form-control" placeholder="Search students..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <select name="risk_filter" class="form-select" style="max-width: 150px;" onchange="this.form.submit()">
                            <option value="">All Risk Levels</option>
                            <option value="high" <?= $risk_filter === 'high' ? 'selected' : '' ?>>High Risk</option>
                            <option value="medium" <?= $risk_filter === 'medium' ? 'selected' : '' ?>>Medium Risk</option>
                            <option value="low" <?= $risk_filter === 'low' ? 'selected' : '' ?>>Low Risk</option>
                        </select>
                    </form>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select-all" /></th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Risk</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No students found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): 
                                $risk = getRiskLevel($student['grade']);
                            ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input student-checkbox" /></td>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td>
                                        <a href="student_detail.php?student_id=<?= $student['student_id'] ?>&class_id=<?= $class_id ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="<?= $risk['class'] ?>"><?= $risk['label'] ?></span></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?= $student['student_id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $student['student_id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?= $student['student_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Student Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                                                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                                                <p><strong>Current Grade:</strong> <?= htmlspecialchars($student['grade'] ?? 'Not graded') ?></p>
                                                <p><strong>Marks:</strong> <?= htmlspecialchars($student['marks'] ?? 'N/A') ?></p>
                                                <p><strong>Risk Level:</strong> <span class="<?= $risk['class'] ?>"><?= $risk['label'] ?></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $student['student_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Grade</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="update_grade">
                                                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Student ID</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($student['student_id']) ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="grade<?= $student['student_id'] ?>" class="form-label">Grade</label>
                                                        <select class="form-select" id="grade<?= $student['student_id'] ?>" name="grade" required>
                                                            <option value="">Select Grade</option>
                                                            <?php
                                                            $grades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'E', 'F'];
                                                            foreach ($grades as $g) {
                                                                $selected = ($student['grade'] === $g) ? 'selected' : '';
                                                                echo "<option value=\"$g\" $selected>$g</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="marks<?= $student['student_id'] ?>" class="form-label">Marks</label>
                                                        <input type="number" class="form-control" id="marks<?= $student['student_id'] ?>" 
                                                               name="marks" min="0" max="100" value="<?= htmlspecialchars($student['marks'] ?? '') ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>Showing <?= min($items_per_page, count($students)) ?> of <?= $total_count ?> students</div>
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?class_id=<?= $class_id ?>&page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&risk_filter=<?= urlencode($risk_filter) ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?class_id=<?= $class_id ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&risk_filter=<?= urlencode($risk_filter) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?class_id=<?= $class_id ?>&page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&risk_filter=<?= urlencode($risk_filter) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        });
    </script>
</body>

</html>
