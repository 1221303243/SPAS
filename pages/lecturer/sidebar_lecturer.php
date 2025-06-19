<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}
require_once '../../auth/db_connection.php';
$user_id = $_SESSION['user_id'];
$lecturer_name = 'Lecturer';
$stmt = $conn->prepare("SELECT name FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $lecturer_name = $row['name'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../../css/sidebar_lecturer.css" />
    <link href="https://fonts.googleapis.com/css2?family=Rowdies:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <div class="text-wrapper-spas">SPAS</div>
        </div>
        <div class="navigation">
            <div class="sidebar-dropdown">
                <button class="course-link" id="classListDropdownBtn" type="button">
                    <i class="bi bi-journal-bookmark"></i>
                    <span class="text-wrapper">Class List</span>
                    <span style="margin-left:auto;"><i class="bi bi-caret-down-fill"></i></span>
                </button>
                <div class="sidebar-dropdown-menu" id="classListDropdownMenu" style="display: none;">
                    <a class="dropdown-item" href="set_edu_level.php?edu_level=Foundation">Foundation</a>
                    <a class="dropdown-item" href="set_edu_level.php?edu_level=Diploma">Diploma</a>
                    <a class="dropdown-item" href="set_edu_level.php?edu_level=Undergraduate">Undergraduate</a>
                    <a class="dropdown-item" href="set_edu_level.php?edu_level=Postgraduate">Postgraduate</a>
                </div>
            </div>
            <div class="user-list">
                <a href="plan.php" class="course-link">
                    <i class="bi bi-calendar-week"></i>
                    <div class="text-wrapper">Assessment Plan</div>
                </a>
            </div>
            <div class="user-list">
                <a href="assessment.php" class="course-link">
                    <i class="bi bi-list-check"></i>
                    <div class="text-wrapper">Assessment View</div>
                </a>
            </div>
            <div class="user-list">
                <a href="calendar.php" class="course-link">
                    <i class="bi bi-calendar-event"></i>
                    <div class="text-wrapper">Academic Calendar</div>
                </a>
            </div>
            <div class="user-list">
                <a href="grade.php" class="course-link">
                    <i class="bi bi-card-checklist"></i>
                    <div class="text-wrapper">Grade Input</div>
                </a>
            </div>            
            <div class="user-list">
                <a href="feedback_input.php" class="course-link">
                    <i class="bi bi-chat-dots"></i>
                    <div class="text-wrapper">Feedback Input</div>
                </a>
            </div>
        </div>
        <div class="user">
            <div class="frame">
                <!-- <img class="profile-pic" src="../../img/Profile Pic.png" /> -->
                <div class="group">
                    <div class="text-wrapper-3"><?php echo htmlspecialchars($lecturer_name); ?></div>
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
    <script>
    document.getElementById('classListDropdownBtn').onclick = function(event) {
        event.stopPropagation();
        var menu = document.getElementById('classListDropdownMenu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    };
    window.addEventListener('click', function(event) {
        var menu = document.getElementById('classListDropdownMenu');
        if (menu) menu.style.display = 'none';
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>