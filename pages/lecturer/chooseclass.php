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

// Get the target page from URL parameter
$target_page = isset($_GET['target']) ? $_GET['target'] : '';
$valid_targets = ['grade', 'student_list', 'class_planner'];

// Validate target page
if (!in_array($target_page, $valid_targets)) {
    header('Location: lecturer_dashboard.php');
    exit();
}

// Get lecturer's classes from database
$user_id = $_SESSION['user_id']; // Logged-in user ID

$sql = "SELECT 
            c.class_id, c.class_name, c.semester, c.year,
            l.lecturer_id, l.name AS lecturer_name, l.email
        FROM classes c
        JOIN lecturer l ON c.lecturer_id = l.lecturer_id
        WHERE l.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);

// Get page title based on target
$page_titles = [
    'grade' => 'Grade Management',
    'student_list' => 'Student List',
    'class_planner' => 'Class Planner'
];
$page_title = $page_titles[$target_page];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Class - <?php echo htmlspecialchars($page_title); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/chooseclass.css">
</head>
<body>
    <!-- Include topbar -->
    <?php include 'topbar.php'; ?>

    <!-- Page Title Banner -->
    <div class="subject-name">
        <div class="container">
            <h2 class="mb-0"><?php echo htmlspecialchars($page_title); ?> - Select Class</h2>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container container-custom">
        <?php if (empty($classes)): ?>
            <div class="alert alert-info">
                You don't have any classes assigned yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($classes as $class): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <a href="<?php echo htmlspecialchars($target_page); ?>.php?class_id=<?php echo $class['class_id']; ?>" 
                           class="text-decoration-none text-dark">
                            <div class="feature-card">
                                <div>
                                    <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($class['class_code']); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
