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
        // Sample data - In a real application, this would come from a database
        $subjects = array(
            array("name" => "Mathematics", "code" => "MTH101", "description" => "Introduction to calculus, algebra, and mathematical reasoning"),
            array("name" => "Science", "code" => "SCI201", "description" => "Exploration of physical and biological sciences"),
            array("name" => "English", "code" => "ENG101", "description" => "Development of reading, writing, and critical thinking skills"),
            array("name" => "History", "code" => "HIS202", "description" => "Study of major historical events and their significance")
        );

        foreach ($subjects as $subject) {
            echo '<div class="subject-card" onclick="location.href=\'course_content.php?code=' . $subject['code'] . '\';" style="cursor:pointer;">';
            echo '<div class="subject-icon">';
            echo '<span class="material-icons">school</span>';
            echo '</div>';
            echo '<div class="subject-info">';
            echo '<h3>' . $subject['name'] . ' (' . $subject['code'] . ')</h3>';
            echo '<p>' . $subject['description'] . '</p>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
