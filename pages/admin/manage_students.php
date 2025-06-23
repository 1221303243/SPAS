<?php
require_once '../../auth/db_connection.php';

// Get class ID from URL parameter
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$class_id) {
    header('Location: classes.php');
    exit();
}

// Handle Add Student to Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $student_id = intval($_POST['student_id']);
    if ($student_id) {
        // Check if student is already enrolled
        $check_stmt = $conn->prepare("SELECT * FROM student_classes WHERE class_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $class_id, $student_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Add student to class
            $stmt = $conn->prepare("INSERT INTO student_classes (class_id, student_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $class_id, $student_id);
            if ($stmt->execute()) {
                header("Location: manage_students.php?class_id=$class_id&success=1");
                exit();
            } else {
                $errors[] = 'Failed to add student to class: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Student is already enrolled in this class.';
        }
        $check_stmt->close();
    }
}

// Handle Remove Student from Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student'])) {
    $student_id = intval($_POST['remove_student_id']);
    if ($student_id) {
        $stmt = $conn->prepare("DELETE FROM student_classes WHERE class_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $class_id, $student_id);
        if ($stmt->execute()) {
            header("Location: manage_students.php?class_id=$class_id&success=2");
            exit();
        } else {
            $errors[] = 'Failed to remove student from class: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get class information
$class_info = null;
$stmt = $conn->prepare("SELECT c.class_id, c.class_name, s.subject_name, l.name AS lecturer_name 
                       FROM classes c 
                       LEFT JOIN subjects s ON c.subject_id = s.subject_id 
                       LEFT JOIN lecturers l ON c.lecturer_id = l.lecturer_id 
                       WHERE c.class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $class_info = $result->fetch_assoc();
} else {
    header('Location: classes.php');
    exit();
}
$stmt->close();

// Search logic
$search_enrolled = isset($_GET['search_enrolled']) ? trim($_GET['search_enrolled']) : '';
$search_available = isset($_GET['search_available']) ? trim($_GET['search_available']) : '';

// Get enrolled students
$enrolled_students = [];
$sql_enrolled = "SELECT s.student_id, s.name, u.email 
                 FROM students s 
                 INNER JOIN student_classes sc ON s.student_id = sc.student_id 
                 INNER JOIN users u ON s.user_id = u.user_id
                 WHERE sc.class_id = ?";
$params_enrolled = [$class_id];
$types_enrolled = "i";

if ($search_enrolled) {
    $sql_enrolled .= " AND (s.name LIKE ? OR s.student_id LIKE ?)";
    $search_param_enrolled = "%" . $search_enrolled . "%";
    $params_enrolled[] = $search_param_enrolled;
    $params_enrolled[] = $search_param_enrolled;
    $types_enrolled .= "ss";
}
$sql_enrolled .= " ORDER BY s.name ASC";

$stmt = $conn->prepare($sql_enrolled);
$stmt->bind_param($types_enrolled, ...$params_enrolled);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrolled_students[] = $row;
}
$stmt->close();

// Get all students not enrolled in this class
$available_students = [];
$sql_available = "SELECT s.student_id, s.name, u.email 
                  FROM students s 
                  INNER JOIN users u ON s.user_id = u.user_id
                  WHERE s.student_id NOT IN (
                      SELECT student_id FROM student_classes WHERE class_id = ?
                  )";
$params_available = [$class_id];
$types_available = "i";

if ($search_available) {
    $sql_available .= " AND (s.name LIKE ? OR s.student_id LIKE ?)";
    $search_param_available = "%" . $search_available . "%";
    $params_available[] = $search_param_available;
    $params_available[] = $search_param_available;
    $types_available .= "ss";
}
$sql_available .= " ORDER BY s.name ASC";

$stmt = $conn->prepare($sql_available);
$stmt->bind_param($types_available, ...$params_available);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $available_students[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Manage Students</title>
    <link rel="stylesheet" href="../../css/admin_classes.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        
        body {
            display: flex;
        }
        
        .container {
            flex: 1;
            margin-left: 318px;
            padding: 20px;
        }
        
        .back-btn {
            background: #00C1FE;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .class-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .students-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .students-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .students-section h3 {
            margin-top: 0;
            color: #1F1235;
        }
        
        .search-box {
            margin-bottom: 15px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 8px 12px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .search-box .material-icons {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .student-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: bold;
            color: #1F1235;
        }
        
        .student-email {
            font-size: 0.9em;
            color: #666;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        
        .remove-btn {
            color: #dc3545;
        }
        
        .remove-btn:hover {
            background-color: #f8d7da;
        }
        
        .add-btn {
            color: #28a745;
        }
        
        .add-btn:hover {
            background-color: #d4edda;
        }
        
        .no-students {
            text-align: center;
            color: #888;
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="container">
        <button class="back-btn" onclick="window.location.href='classes.php'">
            <span class="material-icons">arrow_back</span>
            Back to Classes
        </button>

        <div class="class-info">
            <h1>Manage Students - <?php echo htmlspecialchars($class_info['class_name']); ?></h1>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($class_info['subject_name']); ?></p>
            <p><strong>Lecturer:</strong> <?php echo htmlspecialchars($class_info['lecturer_name']); ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] == 1) echo 'Student added to class successfully!';
                elseif ($_GET['success'] == 2) echo 'Student removed from class successfully!';
                ?>
            </div>
        <?php endif; ?>

        <div class="students-container">
            <div class="students-section">
                <h3>Enrolled Students (<?= count($enrolled_students) ?>)</h3>
                <form method="GET" class="search-box">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <span class="material-icons">search</span>
                    <input type="text" name="search_enrolled" placeholder="Search by ID or name..." value="<?= htmlspecialchars($search_enrolled) ?>" onchange="this.form.submit()">
                </form>
                <div class="student-list">
                    <?php if (!empty($enrolled_students)): ?>
                        <?php foreach ($enrolled_students as $student): ?>
                            <div class="student-item">
                                <div class="student-info">
                                    <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                    <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this student from the class?');">
                                    <input type="hidden" name="remove_student" value="1">
                                    <input type="hidden" name="remove_student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" class="action-btn remove-btn" title="Remove from class">
                                        <span class="material-icons">remove_circle</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-students">No students enrolled in this class.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="students-section">
                <h3>Available Students (<?= count($available_students) ?>)</h3>
                <form method="GET" class="search-box">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <span class="material-icons">search</span>
                    <input type="text" name="search_available" placeholder="Search by ID or name..." value="<?= htmlspecialchars($search_available) ?>" onchange="this.form.submit()">
                </form>
                <div class="student-list">
                    <?php if (!empty($available_students)): ?>
                        <?php foreach ($available_students as $student): ?>
                            <div class="student-item">
                                <div class="student-info">
                                    <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                    <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="add_student" value="1">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" class="action-btn add-btn" title="Add to class">
                                        <span class="material-icons">add_circle</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-students">All students are already enrolled in this class.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 