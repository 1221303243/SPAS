<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';


    // Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $totalUsers = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Total subjects
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects");
    $stmt->execute();
    $totalSubjects = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Total classes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM classes");
    $stmt->execute();
    $totalClasses = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/admin_dashboard.css" />
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
            margin-left: 318px; /* Match sidebar width */
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="header-actions">
                <span class="welcome-text">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</span>
                <span class="date"><?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon users-icon">
                    <span class="material-icons">people</span>
                </div>
                <div class="stat-content">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $totalUsers; ?></p>
                    <!-- <p class="stat-change positive">+12% from last month</p> -->
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon subjects-icon">
                    <span class="material-icons">book</span>
                </div>
                <div class="stat-content">
                    <h3>Total Subjects</h3>
                    <p class="stat-number"><?php echo $totalSubjects; ?></p>
                    <!-- <p class="stat-change positive">+3 new this month</p> -->
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon classes-icon">
                    <span class="material-icons">class</span>
                </div>
                <div class="stat-content">
                    <h3>Total Classes</h3>
                    <p class="stat-number"><?php echo $totalClasses; ?></p>
                    <!-- <p class="stat-change positive">+8% from last month</p> -->
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <a href="users.php" class="action-btn">
                    <span class="material-icons">person_add</span>
                    <span>Add New User</span>
                </a>
                <a href="subjects.php" class="action-btn">
                    <span class="material-icons">add_circle</span>
                    <span>Add New Subject</span>
                </a>
                <a href="classes.php" class="action-btn">
                    <span class="material-icons">group_add</span>
                    <span>Create New Class</span>
                </a>
                <a href="#" class="action-btn">
                    <span class="material-icons">assessment</span>
                    <span>View Reports</span>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <h2>System Status</h2>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-header">
                        <span class="status-title">Database</span>
                        <span class="status-indicator online"></span>
                    </div>
                    <p class="status-desc">All systems operational</p>
                </div>

                <div class="status-item">
                    <div class="status-header">
                        <span class="status-title">Server</span>
                        <span class="status-indicator online"></span>
                    </div>
                    <p class="status-desc">Running smoothly</p>
                </div>

                <div class="status-item">
                    <div class="status-header">
                        <span class="status-title">Backup</span>
                        <span class="status-indicator warning"></span>
                    </div>
                    <p class="status-desc">Last backup: 6 hours ago</p>
                </div>

                <div class="status-item">
                    <div class="status-header">
                        <span class="status-title">Security</span>
                        <span class="status-indicator online"></span>
                    </div>
                    <p class="status-desc">All security checks passed</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 