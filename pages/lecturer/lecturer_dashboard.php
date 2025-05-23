<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

// Include database connection
require_once '../../auth/db_connection.php';

// Get the lecturer's user_id from session
$user_id = $_SESSION['user_id'];

// Fetch the lecturer_id for this user
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$lecturer_id = $lecturer ? $lecturer['lecturer_id'] : null;
$stmt->close();

$subjects = array();
if ($lecturer_id) {
    // Fetch subject name and code for classes taught by this lecturer
    $sql = "SELECT DISTINCT c.class_id, s.subject_name, s.subject_code FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.lecturer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
}
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
    <?php include 'sidebar_lecturer.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="header">
            <h1>My Subjects</h1>
        </div>

        <!-- List of Subjects -->
        <?php
        if (empty($subjects)) {
            echo '<p>You are not assigned to any subjects.</p>';
        } else {
            foreach ($subjects as $subject) {
                echo '<a href="student_list.php?class_id=' . urlencode($subject['class_id']) . '" style="text-decoration:none;color:inherit;">';
                echo '<div class="subject-card">';
                echo '<div class="subject-icon">';
                echo '<span class="material-icons">school</span>';
                echo '</div>';
                echo '<div class="subject-info">';
                echo '<h3>' . htmlspecialchars($subject['subject_name']) . ' (' . htmlspecialchars($subject['subject_code']) . ')</h3>';
                echo '</div>';
                echo '</div>';
                echo '</a>';
            }
        }
        ?>
    </div>
</body>
</html>
