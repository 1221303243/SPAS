<!-- topbar.php -->
<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'lecturer') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

$subject_code = '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($class_id > 0) {
    // Get the subject code for the current class
    $sql = "SELECT s.subject_code 
            FROM classes c 
            JOIN subjects s ON c.subject_id = s.subject_id 
            WHERE c.class_id = ? AND c.lecturer_id IN (
                SELECT lecturer_id 
                FROM lecturers 
                WHERE user_id = ?
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $class_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $subject_code = $row['subject_code'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SPAS</title>
  <link rel="stylesheet" href="../../css/topbar.css">
</head>
<body>
  <div class="navbar">
    <div class="navbar-left">
      <a href="../lecturer/lecturer_dashboard.php" class="back-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </a>
      SPAS
    </div>
    <div class="navbar-right">
      <a href="../lecturer/lecturer_dashboard.php" class="nav-dashboard-btn">Dashboard</a>
      <span><?php echo htmlspecialchars($subject_code); ?></span>
    </div>
  </div>
</body>
</html>
