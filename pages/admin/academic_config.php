<?php
session_start();
require_once '../../config/academic_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester_start_date = $_POST['semester_start_date'] ?? '';
    $semester_weeks = $_POST['semester_weeks'] ?? 14;
    $passing_percentage = $_POST['passing_percentage'] ?? 50;
    
    // Validate inputs
    $errors = [];
    
    if (empty($semester_start_date)) {
        $errors[] = "Semester start date is required";
    } elseif (!strtotime($semester_start_date)) {
        $errors[] = "Invalid date format";
    }
    
    if ($semester_weeks < 1 || $semester_weeks > 52) {
        $errors[] = "Semester weeks must be between 1 and 52";
    }
    
    if ($passing_percentage < 0 || $passing_percentage > 100) {
        $errors[] = "Passing percentage must be between 0 and 100";
    }
    
    // If no errors, update configuration
    if (empty($errors)) {
        // Update the configuration file
        $config_content = "<?php\n";
        $config_content .= "// Academic year configuration\n";
        $config_content .= "define('SEMESTER_START_DATE', '" . $semester_start_date . "');\n";
        $config_content .= "define('SEMESTER_WEEKS', " . $semester_weeks . ");\n";
        $config_content .= "define('PASSING_PERCENTAGE', " . $passing_percentage . ");\n\n";
        $config_content .= "// Grade normalization constants\n";
        $config_content .= "define('MAX_GRADE', 100);  // Maximum possible grade\n";
        $config_content .= "define('MIN_GRADE', 0);    // Minimum possible grade\n\n";
        $config_content .= "// Assessment types\n";
        $config_content .= "define('ASSESSMENT_TYPES', [\n";
        $config_content .= "    'EXAM' => 'Examination',\n";
        $config_content .= "    'QUIZ' => 'Quiz',\n";
        $config_content .= "    'ASSIGNMENT' => 'Assignment',\n";
        $config_content .= "    'PROJECT' => 'Project',\n";
        $config_content .= "    'LAB' => 'Lab',\n";
        $config_content .= "    'MIDTERM' => 'Midterm',\n";
        $config_content .= "    'FINAL' => 'Final',\n";
        $config_content .= "    'TEST' => 'Test'\n";
        $config_content .= "]);\n";
        
        if (file_put_contents('../../config/academic_config.php', $config_content)) {
            header('Location: academic_config.php?success=1');
            exit();
        } else {
            $errors[] = "Failed to update configuration file. Please check file permissions.";
        }
    }
}

// Get current values
$current_semester_start = defined('SEMESTER_START_DATE') ? SEMESTER_START_DATE : '2025-06-02';
$current_semester_weeks = defined('SEMESTER_WEEKS') ? SEMESTER_WEEKS : 14;
$current_passing_percentage = defined('PASSING_PERCENTAGE') ? PASSING_PERCENTAGE : 50;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Configuration - SPAS Admin</title>
    <link rel="stylesheet" href="../../css/admin_academic_config.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar_admin.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><span class="material-icons">settings</span> Academic Configuration</h1>
                <p>Manage semester dates and academic settings</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <span class="material-icons">error</span>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <span class="material-icons">check_circle</span>
                    Academic configuration updated successfully!
                </div>
            <?php endif; ?>

            <div class="config-container">
                <div class="config-card">
                    <div class="card-header">
                        <h2><span class="material-icons">school</span> Semester Settings</h2>
                    </div>
                    
                    <form method="POST" class="config-form">
                        <div class="form-group">
                            <label for="semester_start_date">
                                <span class="material-icons">event</span>
                                Semester Start Date
                            </label>
                            <input 
                                type="date" 
                                id="semester_start_date" 
                                name="semester_start_date" 
                                value="<?php echo htmlspecialchars($current_semester_start); ?>"
                                required
                            >
                            <small>Set the official start date for the current semester</small>
                        </div>

                        <div class="form-group">
                            <label for="semester_weeks">
                                <span class="material-icons">schedule</span>
                                Semester Duration (Weeks)
                            </label>
                            <input 
                                type="number" 
                                id="semester_weeks" 
                                name="semester_weeks" 
                                value="<?php echo htmlspecialchars($current_semester_weeks); ?>"
                                min="1" 
                                max="52" 
                                required
                            >
                            <small>Number of weeks in the semester (1-52)</small>
                        </div>

                        <div class="form-group">
                            <label for="passing_percentage">
                                <span class="material-icons">percent</span>
                                Passing Percentage
                            </label>
                            <input 
                                type="number" 
                                id="passing_percentage" 
                                name="passing_percentage" 
                                value="<?php echo htmlspecialchars($current_passing_percentage); ?>"
                                min="0" 
                                max="100" 
                                required
                            >
                            <small>Minimum percentage required to pass (0-100)</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-icons">save</span>
                                Save Configuration
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <span class="material-icons">refresh</span>
                                Reset to Current
                            </button>
                        </div>
                    </form>
                </div>

                <div class="config-card">
                    <div class="card-header">
                        <h2><span class="material-icons">info</span> Current Configuration</h2>
                    </div>
                    
                    <div class="current-config">
                        <div class="config-item">
                            <span class="material-icons">event</span>
                            <div>
                                <strong>Semester Start Date:</strong>
                                <p><?php echo date('F j, Y', strtotime($current_semester_start)); ?></p>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <span class="material-icons">schedule</span>
                            <div>
                                <strong>Semester Duration:</strong>
                                <p><?php echo $current_semester_weeks; ?> weeks</p>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <span class="material-icons">percent</span>
                            <div>
                                <strong>Passing Percentage:</strong>
                                <p><?php echo $current_passing_percentage; ?>%</p>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <span class="material-icons">calculate</span>
                            <div>
                                <strong>Semester End Date:</strong>
                                <p><?php echo date('F j, Y', strtotime($current_semester_start . ' + ' . ($current_semester_weeks * 7) . ' days')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </main>
    </div>

    <script>
        // Add form validation and confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.config-form');
            const resetBtn = document.querySelector('button[type="reset"]');
            
            form.addEventListener('submit', function(e) {
                const startDate = document.getElementById('semester_start_date').value;
                const weeks = document.getElementById('semester_weeks').value;
                const percentage = document.getElementById('passing_percentage').value;
                
                if (!startDate || !weeks || !percentage) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (!confirm('Are you sure you want to update the academic configuration? This will affect the entire system.')) {
                    e.preventDefault();
                }
            });
            
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Reset form to current configuration values?')) {
                    form.reset();
                }
            });
        });
    </script>
</body>
</html> 