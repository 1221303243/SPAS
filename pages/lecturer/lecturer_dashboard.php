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
if ($lecturer_id && isset($_SESSION['edu_level'])) {
    $edu_level = $_SESSION['edu_level'];
    // Fetch subject name, code, and class for classes taught by this lecturer and matching the selected education level
    $sql = "SELECT DISTINCT c.class_id, s.subject_name, s.subject_code, c.class_name, c.edu_level FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.lecturer_id = ? AND c.edu_level = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $lecturer_id, $edu_level);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard.css" />

</head>
<body>
    <?php include 'sidebar_lecturer.php'; ?>

    <!-- Main Content -->
    <div class="main-dashboard-content">
        <div class="header">
            <h1>My Class List<?php if (isset($_SESSION['edu_level'])) echo ' (' . htmlspecialchars($_SESSION['edu_level']) . ')'; ?></h1>
        </div>

        <!-- List of Subjects -->
        <?php
        if (empty($subjects)) {
            echo '<p>You are not assigned to any subjects for this education level.</p>';
        } else {
            foreach ($subjects as $subject) {
                echo '<a href="student_list.php?class_id=' . urlencode($subject['class_id']) . '" style="text-decoration:none;color:inherit;">';
                echo '<div class="subject-card">';
                echo '<div class="subject-icon">';
                echo '<span class="material-icons">school</span>';
                echo '</div>';
                echo '<div class="subject-info">';
                echo '<h3>' . htmlspecialchars($subject['subject_name']) . ' (' . htmlspecialchars($subject['subject_code']) . ')</h3>';
                echo '<div class="edu-level-badge">' . htmlspecialchars($subject['edu_level']) . '</div>';
                echo '</div>';
                echo '</div>';
                echo '</a>';
            }
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('get_reminder.php')
            .then(res => res.json())
            .then(data => {
                if (data.reminder) {
                    // Remove any existing toast
                    let oldToast = document.getElementById('reminderToast');
                    if (oldToast) oldToast.remove();

                    // Create toast container if not present
                    let toastContainer = document.getElementById('global-toast-container');
                    if (!toastContainer) {
                        toastContainer = document.createElement('div');
                        toastContainer.id = 'global-toast-container';
                        document.body.appendChild(toastContainer);
                    }

                    // Insert toast HTML
                    toastContainer.innerHTML = `
                        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true"
                             data-bs-autohide="true" data-bs-delay="6000" id="reminderToast">
                            <div class="toast-header bg-info text-white">
                                <strong class="me-auto">Upcoming Assessment</strong>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">${data.reminder}</div>
                        </div>
                    `;

                    // Initialize and show the toast
                    setTimeout(function() {
                        var reminderToast = document.getElementById('reminderToast');
                        if (reminderToast) {
                            var toast = new bootstrap.Toast(reminderToast);
                            toast.show();
                        }
                    }, 100); // slight delay to ensure DOM is updated
                }
            });
    });
    </script>
</body>
</html>
