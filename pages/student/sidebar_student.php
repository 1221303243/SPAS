<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$student_name = 'Student';
$stmt = $conn->prepare("SELECT name FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $student_name = $row['name'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../../css/sidebar_student.css" />
    <link href="https://fonts.googleapis.com/css2?family=Rowdies:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <div class="text-wrapper-spas">SPAS</div>
        </div>
        <div class="navigation">
            <div class="user-list">
                <a href="student_dashboard.php" class="course-link">
                    <i class="bi bi-journal-bookmark"></i>
                    <div class="text-wrapper">Courses</div>
                </a>
            </div>
            <div class="user-list">
                <a href="calendar.php" class="course-link">
                    <i class="bi bi-calendar-event"></i>
                    <div class="text-wrapper">Calendar</div>
                </a>
            </div>
        </div>
        <div class="user">
            <div class="frame">
                <img class="profile-pic" src="../../img/Profile Pic.png" />
                <div class="group">
                <div class="text-wrapper-3"><?php echo htmlspecialchars($student_name); ?></div>
                </div>
            </div>
            <div class="settings-container">
                <img class="vector-2" src="../../img/setting_white.png" onclick="toggleDropup(event)" />
                <div class="dropup-menu" id="dropupMenu" style="display: none;">
                    <a href="../../auth/logout.php" class="dropup-item logout">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleDropup(event) {
            event.stopPropagation();
            const dropupMenu = document.getElementById('dropupMenu');
            dropupMenu.style.display = (dropupMenu.style.display === 'block') ? 'none' : 'block';
        }
        window.onclick = function(event) {
            if (!event.target.matches('.vector-2')) {
                const dropupMenu = document.getElementById('dropupMenu');
                if (dropupMenu) dropupMenu.style.display = 'none';
            }
        }
    </script>
</body>
</html>