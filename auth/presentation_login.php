<?php
session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../pages/admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: ../pages/student/student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'lecturer') {
        header("Location: ../pages/lecturer/select_edu_level.php");
        exit();
    }
}

// Handle quick login for presentation
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
            
            // Set session
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Presentation Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .presentation-container { 
            max-width: 800px; 
            margin: 2em auto; 
            background: #fff; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,193,254,0.15); 
            padding: 2.5em; 
        }
        .presentation-title { 
            font-weight: 700; 
            color: #1F1235; 
            margin-bottom: 1.5em; 
            text-align: center; 
            font-size: 2.2em;
        }
        .presentation-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 2em;
            font-size: 1.1em;
        }
        .user-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5em;
            margin-bottom: 1em;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .user-card:hover {
            border-color: #00C1FE;
            box-shadow: 0 4px 12px rgba(0,193,254,0.1);
            transform: translateY(-2px);
        }
        .user-card.admin { border-left: 4px solid #dc3545; }
        .user-card.lecturer { border-left: 4px solid #ffc107; }
        .user-card.student { border-left: 4px solid #28a745; }
        .user-icon {
            font-size: 2em;
            margin-bottom: 0.5em;
        }
        .user-title {
            font-weight: 600;
            margin-bottom: 0.5em;
        }
        .user-description {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 1em;
        }
        .btn-quick-login {
            background: #00C1FE;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5em 1.5em;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-quick-login:hover {
            background: #0090c1;
            color: #fff;
        }
        .regular-login-link {
            text-align: center;
            margin-top: 2em;
            padding-top: 2em;
            border-top: 1px solid #e9ecef;
        }
        .regular-login-link a {
            color: #00C1FE;
            text-decoration: none;
            font-weight: 500;
        }
        .regular-login-link a:hover {
            text-decoration: underline;
        }
        .demo-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1em;
            margin-bottom: 2em;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="presentation-container">
        <h1 class="presentation-title">SPAS Presentation Mode</h1>
        <p class="presentation-subtitle">Quick login for demonstration purposes</p>
        
        <div class="demo-note">
            <i class="bi bi-info-circle"></i>
            <strong>Demo Mode:</strong> Use these accounts to quickly switch between different user roles for presentation purposes. 
            Each account will open in a new session.
        </div>
        
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_SESSION['login_error']); ?>
            </div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Admin Account -->
            <div class="col-md-6 mb-3">
                <div class="user-card admin" onclick="quickLogin('admin')">
                    <div class="text-center">
                        <div class="user-icon text-danger">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="user-title">Administrator</div>
                        <div class="user-description">
                            Full system access<br>
                            Manage users, subjects, classes<br>
                            Academic configuration
                        </div>
                        <button class="btn btn-quick-login" onclick="event.stopPropagation(); quickLogin('admin')">
                            <i class="bi bi-arrow-right"></i> Login as Admin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lecturer Account 1 -->
            <div class="col-md-6 mb-3">
                <div class="user-card lecturer" onclick="quickLogin('lecturer1')">
                    <div class="text-center">
                        <div class="user-icon text-warning">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="user-title">Lecturer 1</div>
                        <div class="user-description">
                            Course management<br>
                            Assessment planning<br>
                            Grade input
                        </div>
                        <button class="btn btn-quick-login" onclick="event.stopPropagation(); quickLogin('lecturer1')">
                            <i class="bi bi-arrow-right"></i> Login as Lecturer 1
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lecturer Account 2 -->
            <div class="col-md-6 mb-3">
                <div class="user-card lecturer" onclick="quickLogin('lecturer2')">
                    <div class="text-center">
                        <div class="user-icon text-warning">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="user-title">Lecturer 2</div>
                        <div class="user-description">
                            Different education level<br>
                            Alternative course view<br>
                            Separate assessments
                        </div>
                        <button class="btn btn-quick-login" onclick="event.stopPropagation(); quickLogin('lecturer2')">
                            <i class="bi bi-arrow-right"></i> Login as Lecturer 2
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Account 1 -->
            <div class="col-md-6 mb-3">
                <div class="user-card student" onclick="quickLogin('student1')">
                    <div class="text-center">
                        <div class="user-icon text-success">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <div class="user-title">Student 1</div>
                        <div class="user-description">
                            View grades and progress<br>
                            Access course content<br>
                            Check academic calendar
                        </div>
                        <button class="btn btn-quick-login" onclick="event.stopPropagation(); quickLogin('student1')">
                            <i class="bi bi-arrow-right"></i> Login as Student 1
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Account 2 -->
            <div class="col-md-6 mb-3">
                <div class="user-card student" onclick="quickLogin('student2')">
                    <div class="text-center">
                        <div class="user-icon text-success">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <div class="user-title">Student 2</div>
                        <div class="user-description">
                            Different performance level<br>
                            Alternative course view<br>
                            Separate academic progress
                        </div>
                        <button class="btn btn-quick-login" onclick="event.stopPropagation(); quickLogin('student2')">
                            <i class="bi bi-arrow-right"></i> Login as Student 2
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="regular-login-link">
            <a href="login.php">
                <i class="bi bi-person-circle"></i> Use Regular Login Instead
            </a>
            <br>
            <a href="presentation_guide.php" class="mt-2 d-inline-block">
                <i class="bi bi-question-circle"></i> View Presentation Guide
            </a>
            <br>
            <a href="session_manager.php" class="mt-2 d-inline-block">
                <i class="bi bi-arrow-repeat"></i> Multi-Session Manager
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function quickLogin(userType) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'presentation_login.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'quick_login';
        input.value = '1';
        
        const userTypeInput = document.createElement('input');
        userTypeInput.type = 'hidden';
        userTypeInput.name = 'user_type';
        userTypeInput.value = userType;
        
        form.appendChild(input);
        form.appendChild(userTypeInput);
        document.body.appendChild(form);
        form.submit();
    }

    // Add click handlers for the entire cards
    document.querySelectorAll('.user-card').forEach(card => {
        card.addEventListener('click', function() {
            const userType = this.querySelector('button').getAttribute('onclick').match(/'([^']+)'/)[1];
            quickLogin(userType);
        });
    });
    </script>
</body>
</html> 