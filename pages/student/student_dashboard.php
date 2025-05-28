<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

if ($_SESSION['role'] !== 'student') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

$user_id = $_SESSION['user_id'];
$student_id = null;
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    echo "Student profile not found.";
    exit();
}

// Fetch all subjects and classes the student is enrolled in
$enrolledSubjects = [];
$stmt = $conn->prepare("
    SELECT s.subject_code, s.subject_name, c.class_id, c.class_name
    FROM student_classes sc
    JOIN classes c ON sc.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE sc.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrolledSubjects[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - My Courses</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../css/student_dashboard.css" />
</head>
<body>
     <?php include 'sidebar_student.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="header">
            <h1>My Courses</h1>
        </div>

        <!-- List of Subjects -->
        <?php
        if (empty($enrolledSubjects)) {
            echo '<p>You are not enrolled in any courses.</p>';
        } else {
            foreach ($enrolledSubjects as $subject) {
                echo '<a href="course_content.php?code=' . urlencode($subject['subject_code']) . '" style="text-decoration:none;color:inherit;">';
                echo '<div class="subject-card">';
                echo '<div class="subject-icon">';
                echo '<span class="material-icons">school</span>';
                echo '</div>';
                echo '<div class="subject-info">';
                echo '<h3>' . htmlspecialchars($subject['subject_name']) . ' (' . htmlspecialchars($subject['subject_code']) . ')</h3>';
                echo '<p>Class: ' . htmlspecialchars($subject['class_name']) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</a>';
            }
        }
        ?>
    </div>
</body>
</html>
