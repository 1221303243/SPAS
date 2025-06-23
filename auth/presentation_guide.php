<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Presentation Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .guide-container { 
            max-width: 800px; 
            margin: 2em auto; 
            background: #fff; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,193,254,0.15); 
            padding: 2.5em; 
        }
        .guide-title { 
            font-weight: 700; 
            color: #1F1235; 
            margin-bottom: 1.5em; 
            text-align: center; 
            font-size: 2.2em;
        }
        .step-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5em;
            margin-bottom: 1.5em;
            transition: all 0.3s ease;
        }
        .step-card:hover {
            border-color: #00C1FE;
            box-shadow: 0 4px 12px rgba(0,193,254,0.1);
        }
        .step-number {
            background: #00C1FE;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 1em;
        }
        .browser-tip {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1em;
            margin: 1em 0;
        }
        .account-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5em;
            margin: 1em 0;
        }
        .account-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5em;
            padding: 0.5em;
            border-radius: 6px;
            background: white;
        }
        .account-icon {
            margin-right: 1em;
            font-size: 1.2em;
        }
    </style>
</head>

<body>
    <div class="guide-container">
        <h1 class="guide-title">Presentation Guide</h1>
        <p class="text-center text-muted mb-4">How to demonstrate multiple user roles simultaneously</p>

        <div class="step-card">
            <div class="step-number">1</div>
            <h4>Open Multiple Browser Windows</h4>
            <p>To demonstrate different user roles simultaneously, you'll need to use multiple browser sessions:</p>
            <div class="browser-tip">
                <strong>Pro Tips:</strong>
                <ul class="mb-0 mt-2">
                    <li>Use <strong>Incognito/Private windows</strong> for each account</li>
                    <li>Use <strong>different browsers</strong> (Chrome, Firefox, Edge, Safari)</li>
                    <li>Use <strong>different browser profiles</strong></li>
                    <li>Use <strong>mobile browser</strong> alongside desktop</li>
                </ul>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">2</div>
            <h4>Available Demo Accounts</h4>
            <p>Use these pre-configured accounts for demonstration:</p>
            <div class="account-list">
                <div class="account-item">
                    <span class="account-icon text-danger"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <strong>Administrator:</strong> Full system access, user management, academic configuration
                    </div>
                </div>
                <div class="account-item">
                    <span class="account-icon text-warning"><i class="bi bi-person-workspace"></i></span>
                    <div>
                        <strong>Lecturer 1:</strong> Course management, assessment planning, grade input
                    </div>
                </div>
                <div class="account-item">
                    <span class="account-icon text-warning"><i class="bi bi-person-workspace"></i></span>
                    <div>
                        <strong>Lecturer 2:</strong> Different education level, alternative course view
                    </div>
                </div>
                <div class="account-item">
                    <span class="account-icon text-success"><i class="bi bi-mortarboard"></i></span>
                    <div>
                        <strong>Student 1:</strong> View grades, access course content, check calendar
                    </div>
                </div>
                <div class="account-item">
                    <span class="account-icon text-success"><i class="bi bi-mortarboard"></i></span>
                    <div>
                        <strong>Student 2:</strong> Different performance level, alternative view
                    </div>
                </div>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">3</div>
            <h4>Demonstration Scenarios</h4>
            <p>Here are some effective demonstration scenarios:</p>
            <ul>
                <li><strong>Admin + Lecturer:</strong> Show how admin creates classes and lecturers manage them</li>
                <li><strong>Lecturer + Student:</strong> Demonstrate grade input and student viewing</li>
                <li><strong>Multiple Students:</strong> Show different performance levels and risk detection</li>
                <li><strong>Full Workflow:</strong> Admin → Lecturer → Student complete cycle</li>
            </ul>
        </div>

        <div class="step-card">
            <div class="step-number">4</div>
            <h4>Key Features to Highlight</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-shield-check text-danger"></i> Admin Features:</h6>
                    <ul class="small">
                        <li>User management</li>
                        <li>Subject/class creation</li>
                        <li>Academic configuration</li>
                        <li>System monitoring</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-person-workspace text-warning"></i> Lecturer Features:</h6>
                    <ul class="small">
                        <li>Education level selection</li>
                        <li>Assessment planning</li>
                        <li>Grade input</li>
                        <li>Student monitoring</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-mortarboard text-success"></i> Student Features:</h6>
                    <ul class="small">
                        <li>Grade visualization</li>
                        <li>Performance tracking</li>
                        <li>Academic calendar</li>
                        <li>Course content access</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-graph-up text-info"></i> Analytics Features:</h6>
                    <ul class="small">
                        <li>Risk detection</li>
                        <li>Performance trends</li>
                        <li>Statistical analysis</li>
                        <li>Progress tracking</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="presentation_login.php" class="btn btn-primary btn-lg">
                <i class="bi bi-play-circle"></i> Start Demo
            </a>
            <a href="login.php" class="btn btn-outline-secondary btn-lg ms-2">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 