<?php
session_start();

// Handle session switching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_session'])) {
    $session_id = $_POST['session_id'];
    
    // Store current session data if logged in
    if (isset($_SESSION['user_id'])) {
        $_SESSION['saved_sessions'][$_SESSION['user_id']] = [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'edu_level' => $_SESSION['edu_level'] ?? null,
            'timestamp' => time()
        ];
    }
    
    // Switch to selected session
    if (isset($_SESSION['saved_sessions'][$session_id])) {
        $saved_session = $_SESSION['saved_sessions'][$session_id];
        $_SESSION['user_id'] = $saved_session['user_id'];
        $_SESSION['username'] = $saved_session['username'];
        $_SESSION['role'] = $saved_session['role'];
        if (isset($saved_session['edu_level'])) {
            $_SESSION['edu_level'] = $saved_session['edu_level'];
        }
        
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: ../pages/admin/dashboard.php");
                break;
            case 'student':
                header("Location: ../pages/student/student_dashboard.php");
                break;
            case 'lecturer':
                header("Location: ../pages/lecturer/select_edu_level.php");
                break;
        }
        exit();
    }
}

// Handle quick login for new session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_login'])) {
    require_once 'db_connection.php';
    
    $user_type = $_POST['user_type'];
    
    // Sample user credentials for presentation
    $presentation_users = [
        'admin' => ['email' => 'admin@spas.com', 'password' => 'admin123'],
        'lecturer1' => ['email' => 'lecturer1@spas.com', 'password' => 'lecturer123'],
        'lecturer2' => ['email' => 'lecturer2@spas.com', 'password' => 'lecturer123'],
        'student1' => ['email' => 'student1@spas.com', 'password' => 'student123'],
        'student2' => ['email' => 'student2@spas.com', 'password' => 'student123']
    ];
    
    if (isset($presentation_users[$user_type])) {
        $user = $presentation_users[$user_type];
        
        // Verify credentials
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            
            // Store current session if logged in
            if (isset($_SESSION['user_id'])) {
                $_SESSION['saved_sessions'][$_SESSION['user_id']] = [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role'],
                    'edu_level' => $_SESSION['edu_level'] ?? null,
                    'timestamp' => time()
                ];
            }
            
            // Set new session
            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role'];
            
            // Redirect based on role
            switch ($user_data['role']) {
                case 'admin':
                    header("Location: ../pages/admin/dashboard.php");
                    break;
                case 'student':
                    header("Location: ../pages/student/student_dashboard.php");
                    break;
                case 'lecturer':
                    header("Location: ../pages/lecturer/select_edu_level.php");
                    break;
            }
            exit();
        }
    }
    
    $_SESSION['login_error'] = "Invalid credentials for presentation user";
}

// Initialize saved sessions array if not exists
if (!isset($_SESSION['saved_sessions'])) {
    $_SESSION['saved_sessions'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Multi-Session Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .session-container { max-width: 1000px; margin: 2em auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,193,254,0.15); padding: 2.5em; }
        .session-title { font-weight: 700; color: #1F1235; margin-bottom: 1.5em; text-align: center; font-size: 2.2em; }
        .session-card { border: 2px solid #e9ecef; border-radius: 12px; padding: 1.5em; margin-bottom: 1em; transition: all 0.3s ease; }
        .session-card:hover { border-color: #00C1FE; box-shadow: 0 4px 12px rgba(0,193,254,0.1); }
        .session-card.active { border-color: #28a745; background: #f8fff9; }
        .session-card.admin { border-left: 4px solid #dc3545; }
        .session-card.lecturer { border-left: 4px solid #ffc107; }
        .session-card.student { border-left: 4px solid #28a745; }
        .btn-switch { background: #00C1FE; color: #fff; border: none; border-radius: 8px; padding: 0.5em 1.5em; font-weight: 500; }
        .btn-new-session { background: #28a745; color: #fff; border: none; border-radius: 8px; padding: 0.5em 1.5em; font-weight: 500; }
        .current-session-badge { background: #28a745; color: white; padding: 0.2em 0.6em; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
    </style>
</head>

<body>
    <div class="session-container">
        <h1 class="session-title">SPAS Multi-Session Manager</h1>
        <p class="text-center text-muted mb-4">Switch between multiple user accounts in the same browser</p>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Multi-Session Mode:</strong> Maintain multiple user sessions simultaneously. Switch between accounts instantly.
        </div>
        
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_SESSION['login_error']); ?>
            </div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <!-- Current Session -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h4><i class="bi bi-person-check"></i> Current Session</h4>
                    <div class="session-card active">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                    <span class="current-session-badge">Active</span>
                                </div>
                                <div class="text-muted">
                                    Role: <?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?>
                                    <?php if (isset($_SESSION['edu_level'])): ?>
                                        | Education Level: <?php echo htmlspecialchars($_SESSION['edu_level']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?php 
                                    switch ($_SESSION['role']) {
                                        case 'admin': echo '../pages/admin/dashboard.php'; break;
                                        case 'student': echo '../pages/student/student_dashboard.php'; break;
                                        case 'lecturer': echo '../pages/lecturer/lecturer_dashboard.php'; break;
                                    }
                                ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-right"></i> Continue
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Saved Sessions -->
        <?php if (!empty($_SESSION['saved_sessions'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h4><i class="bi bi-clock-history"></i> Saved Sessions</h4>
                    <?php foreach ($_SESSION['saved_sessions'] as $session_id => $session_data): ?>
                        <div class="session-card <?php echo $session_data['role']; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($session_data['username']); ?></div>
                                    <div class="text-muted">
                                        Role: <?php echo ucfirst(htmlspecialchars($session_data['role'])); ?>
                                        <?php if (isset($session_data['edu_level'])): ?>
                                            | Education Level: <?php echo htmlspecialchars($session_data['edu_level']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        Last active: <?php echo date('M j, Y g:i A', $session_data['timestamp']); ?>
                                    </div>
                                </div>
                                <div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="switch_session" value="1">
                                        <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                                        <button type="submit" class="btn btn-switch">
                                            <i class="bi bi-arrow-repeat"></i> Switch
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- New Session Options -->
        <div class="row">
            <div class="col-12">
                <h4><i class="bi bi-plus-circle"></i> Start New Session</h4>
            </div>
        </div>

        <div class="row">
            <!-- Admin Account -->
            <div class="col-md-6 mb-3">
                <div class="session-card admin">
                    <div class="text-center">
                        <div class="text-danger mb-2"><i class="bi bi-shield-check fs-1"></i></div>
                        <div class="fw-bold">Administrator</div>
                        <div class="text-muted mb-3">Full system access, user management</div>
                        <form method="POST">
                            <input type="hidden" name="quick_login" value="1">
                            <input type="hidden" name="user_type" value="admin">
                            <button type="submit" class="btn btn-new-session">
                                <i class="bi bi-plus"></i> Login as Admin
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lecturer Account 1 -->
            <div class="col-md-6 mb-3">
                <div class="session-card lecturer">
                    <div class="text-center">
                        <div class="text-warning mb-2"><i class="bi bi-person-workspace fs-1"></i></div>
                        <div class="fw-bold">Lecturer 1</div>
                        <div class="text-muted mb-3">Course management, assessment planning</div>
                        <form method="POST">
                            <input type="hidden" name="quick_login" value="1">
                            <input type="hidden" name="user_type" value="lecturer1">
                            <button type="submit" class="btn btn-new-session">
                                <i class="bi bi-plus"></i> Login as Lecturer 1
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lecturer Account 2 -->
            <div class="col-md-6 mb-3">
                <div class="session-card lecturer">
                    <div class="text-center">
                        <div class="text-warning mb-2"><i class="bi bi-person-workspace fs-1"></i></div>
                        <div class="fw-bold">Lecturer 2</div>
                        <div class="text-muted mb-3">Different education level, alternative view</div>
                        <form method="POST">
                            <input type="hidden" name="quick_login" value="1">
                            <input type="hidden" name="user_type" value="lecturer2">
                            <button type="submit" class="btn btn-new-session">
                                <i class="bi bi-plus"></i> Login as Lecturer 2
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Student Account 1 -->
            <div class="col-md-6 mb-3">
                <div class="session-card student">
                    <div class="text-center">
                        <div class="text-success mb-2"><i class="bi bi-mortarboard fs-1"></i></div>
                        <div class="fw-bold">Student 1</div>
                        <div class="text-muted mb-3">View grades, access course content</div>
                        <form method="POST">
                            <input type="hidden" name="quick_login" value="1">
                            <input type="hidden" name="user_type" value="student1">
                            <button type="submit" class="btn btn-new-session">
                                <i class="bi bi-plus"></i> Login as Student 1
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Student Account 2 -->
            <div class="col-md-6 mb-3">
                <div class="session-card student">
                    <div class="text-center">
                        <div class="text-success mb-2"><i class="bi bi-mortarboard fs-1"></i></div>
                        <div class="fw-bold">Student 2</div>
                        <div class="text-muted mb-3">Different performance level, alternative view</div>
                        <form method="POST">
                            <input type="hidden" name="quick_login" value="1">
                            <input type="hidden" name="user_type" value="student2">
                            <button type="submit" class="btn btn-new-session">
                                <i class="bi bi-plus"></i> Login as Student 2
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="presentation_login.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Simple Demo
            </a>
            <a href="login.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-person-circle"></i> Regular Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 