<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    exit();
}

require_once '../../auth/db_connection.php';

// Function to check database connectivity and response time
function checkDatabaseStatus($conn) {
    $start_time = microtime(true);
    $status = [
        'connected' => false,
        'response_time' => 0,
        'message' => 'Connection failed'
    ];
    
    try {
        // Test basic connectivity
        if ($conn && $conn->ping()) {
            // Test a simple query to measure response time
            $result = $conn->query("SELECT 1");
            if ($result) {
                $end_time = microtime(true);
                $response_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
                
                $status['connected'] = true;
                $status['response_time'] = $response_time;
                $status['message'] = "Connected ({$response_time}ms)";
                $result->close();
            }
        }
    } catch (Exception $e) {
        $status['message'] = 'Connection error: ' . $e->getMessage();
    }
    
    return $status;
}

// Function to check server health metrics
function checkServerHealth() {
    $health = [
        'status' => 'healthy',
        'message' => 'All systems operational',
        'details' => []
    ];
    
    // Check disk space
    $disk_free = disk_free_space('.');
    $disk_total = disk_total_space('.');
    $disk_usage_percent = round((($disk_total - $disk_free) / $disk_total) * 100, 1);
    
    $health['details']['disk_usage'] = $disk_usage_percent;
    
    // Check memory usage (if available)
    if (function_exists('memory_get_usage')) {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = return_bytes($memory_limit);
        $memory_usage_percent = round(($memory_usage / $memory_limit_bytes) * 100, 1);
        $health['details']['memory_usage'] = $memory_usage_percent;
    }
    
    // Check PHP version and configuration
    $php_version = PHP_VERSION;
    $health['details']['php_version'] = $php_version;
    
    // Determine overall health status
    if ($disk_usage_percent > 90) {
        $health['status'] = 'critical';
        $health['message'] = 'Disk space critically low';
    } elseif ($disk_usage_percent > 80 || (isset($memory_usage_percent) && $memory_usage_percent > 80)) {
        $health['status'] = 'warning';
        $health['message'] = 'High resource usage';
    }
    
    return $health;
}

// Function to convert memory limit string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

// Perform system checks
$db_status = checkDatabaseStatus($conn);
$server_health = checkServerHealth();

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
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <h2>System Status</h2>
            <div class="status-grid">
                <div class="status-item" onclick="showDatabaseDetails()">
                    <div class="status-header">
                        <span class="status-title">Database</span>
                        <span class="status-indicator <?php echo $db_status['connected'] ? 'online' : 'offline'; ?>"></span>
                    </div>
                    <p class="status-desc"><?php echo $db_status['message']; ?></p>
                </div>

                <div class="status-item" onclick="showServerDetails()">
                    <div class="status-header">
                        <span class="status-title">Server</span>
                        <span class="status-indicator <?php 
                            echo $server_health['status'] === 'healthy' ? 'online' : 
                                ($server_health['status'] === 'warning' ? 'warning' : 'offline'); 
                        ?>"></span>
                    </div>
                    <p class="status-desc"><?php echo $server_health['message']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status Details Modal -->
    <div id="statusModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="modalTitle">System Details</h2>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <style>
        .status-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .status-item:hover {
            background-color: #f8f9fa;
        }
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            color: #1F1235;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .modal-body {
            padding: 20px;
        }
        .detail-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .detail-label {
            font-weight: bold;
            color: #1F1235;
        }
        .detail-value {
            margin-top: 5px;
            color: #666;
        }
    </style>

    <script>
        // Store PHP data in JavaScript variables
        const dbStatus = <?php echo json_encode($db_status); ?>;
        const serverHealth = <?php echo json_encode($server_health); ?>;

        function showDatabaseDetails() {
            document.getElementById('modalTitle').textContent = 'Database Status Details';
            let content = '<div class="detail-item">';
            content += '<div class="detail-label">Connection Status</div>';
            content += '<div class="detail-value">' + (dbStatus.connected ? 'Connected' : 'Disconnected') + '</div>';
            content += '</div>';
            content += '<div class="detail-item">';
            content += '<div class="detail-label">Response Time</div>';
            content += '<div class="detail-value">' + dbStatus.response_time + ' ms</div>';
            content += '</div>';
            content += '<div class="detail-item">';
            content += '<div class="detail-label">Status Message</div>';
            content += '<div class="detail-value">' + dbStatus.message + '</div>';
            content += '</div>';
            
            document.getElementById('modalBody').innerHTML = content;
            document.getElementById('statusModal').style.display = 'block';
        }

        function showServerDetails() {
            document.getElementById('modalTitle').textContent = 'Server Health Details';
            let content = '<div class="detail-item">';
            content += '<div class="detail-label">Overall Status</div>';
            content += '<div class="detail-value">' + serverHealth.status.charAt(0).toUpperCase() + serverHealth.status.slice(1) + '</div>';
            content += '</div>';
            
            if (serverHealth.details.disk_usage !== undefined) {
                content += '<div class="detail-item">';
                content += '<div class="detail-label">Disk Usage</div>';
                content += '<div class="detail-value">' + serverHealth.details.disk_usage + '%</div>';
                content += '</div>';
            }
            
            if (serverHealth.details.memory_usage !== undefined) {
                content += '<div class="detail-item">';
                content += '<div class="detail-label">Memory Usage</div>';
                content += '<div class="detail-value">' + serverHealth.details.memory_usage + '%</div>';
                content += '</div>';
            }
            
            if (serverHealth.details.php_version !== undefined) {
                content += '<div class="detail-item">';
                content += '<div class="detail-label">PHP Version</div>';
                content += '<div class="detail-value">' + serverHealth.details.php_version + '</div>';
                content += '</div>';
            }
            
            content += '<div class="detail-item">';
            content += '<div class="detail-label">Status Message</div>';
            content += '<div class="detail-value">' + serverHealth.message + '</div>';
            content += '</div>';
            
            document.getElementById('modalBody').innerHTML = content;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 