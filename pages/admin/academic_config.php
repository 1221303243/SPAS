<?php
session_start();
require_once '../../auth/db_connection.php';
require_once '../../config/academic_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login_handler.php');
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

// Handle set trimester form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_trimester'])) {
    $trimester_name = trim($_POST['trimester_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    if ($trimester_name && $start_date && $end_date) {
        // Set all previous trimesters to inactive
        $conn->query("UPDATE current_semester SET is_active = 0");
        // Insert new trimester as active
        $stmt = $conn->prepare("INSERT INTO current_semester (trimester_name, start_date, end_date, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $trimester_name, $start_date, $end_date);
        $stmt->execute();
        $stmt->close();
        header('Location: academic_config.php?success=trimester');
        exit();
    } else {
        $trimester_error = 'All fields are required.';
    }
}

// Handle edit trimester form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trimester'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_name = trim($_POST['edit_trimester_name']);
    $edit_start = $_POST['edit_start_date'];
    $edit_end = $_POST['edit_end_date'];
    $edit_active = isset($_POST['edit_is_active']) ? 1 : 0;
    if ($edit_name && $edit_start && $edit_end) {
        if ($edit_active) {
            $conn->query("UPDATE current_semester SET is_active = 0");
        }
        $stmt = $conn->prepare("UPDATE current_semester SET trimester_name=?, start_date=?, end_date=?, is_active=? WHERE id=?");
        $stmt->bind_param("sssii", $edit_name, $edit_start, $edit_end, $edit_active, $edit_id);
        $stmt->execute();
        $stmt->close();
        header('Location: academic_config.php?success=edit_trimester');
        exit();
    } else {
        $trimester_error = 'All fields are required for editing.';
    }
}

// Fetch all trimesters
$trimesters = [];
$result = $conn->query("SELECT * FROM current_semester ORDER BY start_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $trimesters[] = $row;
    }
}
$current_trimester = getCurrentTrimester($conn);

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
                <!-- Remove old semester settings, keep only passing percentage -->
                <div class="config-card">
                    <div class="card-header">
                        <h2><span class="material-icons">percent</span> Passing Percentage Setting</h2>
                    </div>
                    <form method="POST" class="config-form">
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
                                Save Passing Percentage
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Current Semesters List -->
                <div class="config-card">
                    <div class="card-header">
                        <h2><span class="material-icons">event</span> Semester/Trimester History</h2>
                    </div>
                    <div class="current-config">
                        <style>
                        .trimester-list {
                            display: flex;
                            flex-direction: column;
                            gap: 18px;
                        }
                        .trimester-item {
                            background: #f8f9fa;
                            border-radius: 10px;
                            padding: 18px 22px;
                            display: flex;
                            align-items: center;
                            gap: 18px;
                            box-shadow: 0 2px 8px rgba(0,193,254,0.06);
                            border: 1px solid #e9ecef;
                        }
                        .trimester-icon {
                            color: var(--primary-color);
                            font-size: 2rem;
                            margin-right: 8px;
                        }
                        .trimester-info {
                            flex: 1;
                        }
                        .trimester-name {
                            font-weight: 600;
                            font-size: 1.1rem;
                            color: var(--primary-color);
                            margin-right: 8px;
                            text-decoration: none;
                        }
                        .trimester-dates {
                            color: #888;
                            font-size: 1rem;
                            margin-right: 12px;
                        }
                        .trimester-active {
                            color: #00C1FE;
                            font-weight: 700;
                            margin-left: 8px;
                        }
                        .trimester-edit-btn {
                            background: #fff;
                            border: 2px solid #e9ecef;
                            border-radius: 8px;
                            padding: 8px 18px;
                            font-size: 15px;
                            color: #1F1235;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            cursor: pointer;
                            transition: all 0.2s;
                        }
                        .trimester-edit-btn:hover {
                            border-color: #00C1FE;
                            color: #00C1FE;
                            background: #e6f9fe;
                        }
                        </style>
                        <?php if (!empty($trimesters)): ?>
                            <div class="trimester-list">
                                <?php foreach ($trimesters as $t): ?>
                                    <div class="trimester-item<?php if($t['is_active']) echo ' trimester-active-card'; ?>">
                                        <span class="material-icons trimester-icon">event</span>
                                        <div class="trimester-info">
                                            <span class="trimester-name"><?php echo htmlspecialchars($t['trimester_name']); ?></span>
                                            <span class="trimester-dates">
                                                (<?php echo date('F j, Y', strtotime($t['start_date'])); ?> - <?php echo date('F j, Y', strtotime($t['end_date'])); ?>)
                                            </span>
                                            <?php if($t['is_active']) echo '<span class="trimester-active">Active</span>'; ?>
                                        </div>
                                        <button class="trimester-edit-btn" onclick="openEditTrimesterModal(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['trimester_name'])); ?>', '<?php echo $t['start_date']; ?>', '<?php echo $t['end_date']; ?>', <?php echo $t['is_active'] ? 'true' : 'false'; ?>)"><span class="material-icons" style="font-size:18px;">edit</span> Edit</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">No semesters/trimesters set yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Set new trimester form remains unchanged -->
                <div class="config-card">
                    <div class="card-header"><h2><span class="material-icons">event</span>Current Trimester</h2></div>
                    <div class="config-form">
                        <?php if (!empty($trimester_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($trimester_error) . '</div>'; ?>
                        <form method="POST" style="margin-bottom:24px;">
                            <input type="hidden" name="set_trimester" value="1">
                            <div class="form-group">
                                <label for="trimester_name">Trimester Name</label>
                                <input type="text" id="trimester_name" name="trimester_name" required>
                            </div>
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Set as Current Trimester</button>
                            </div>
                        </form>
                        <?php if (!$current_trimester) echo '<div class="alert alert-warning">No active trimester is set.</div>'; ?>
                    </div>
                </div>

                <!-- Edit Trimester Modal -->
                <style>
                #editTrimesterModal.modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.3);
                    backdrop-filter: blur(2px);
                }
                #editTrimesterModal .modal-content {
                    background: #fff;
                    margin: 60px auto;
                    border-radius: 16px;
                    max-width: 400px;
                    width: 95%;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                    animation: modalFadeIn 0.25s;
                    padding: 32px 28px 24px 28px;
                    position: relative;
                }
                @keyframes modalFadeIn {
                    from { opacity: 0; transform: translateY(-30px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                #editTrimesterModal .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 18px;
                }
                #editTrimesterModal .modal-header h2 {
                    margin: 0;
                    color: #1F1235;
                    font-size: 1.5rem;
                    font-weight: 700;
                }
                #editTrimesterModal .close {
                    color: #6c757d;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: color 0.2s ease;
                }
                #editTrimesterModal .close:hover {
                    color: #dc3545;
                }
                #editTrimesterModal .modal-form {
                    display: flex;
                    flex-direction: column;
                    gap: 18px;
                }
                #editTrimesterModal .form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }
                #editTrimesterModal .form-group label {
                    color: #495057;
                    font-weight: 500;
                    font-size: 14px;
                }
                #editTrimesterModal .form-group input[type="text"],
                #editTrimesterModal .form-group input[type="date"] {
                    width: 100%;
                    padding: 10px 12px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.2s ease;
                    box-sizing: border-box;
                }
                #editTrimesterModal .form-group input:focus {
                    outline: none;
                    border-color: #00C1FE;
                }
                #editTrimesterModal .form-group:last-child {
                    flex-direction: row;
                    align-items: center;
                    gap: 10px;
                }
                #editTrimesterModal .form-group input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    margin-right: 8px;
                }
                #editTrimesterModal .form-actions {
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                    margin-top: 10px;
                }
                #editTrimesterModal .cancel-btn,
                #editTrimesterModal .save-btn {
                    background: white;
                    border: 2px solid #e9ecef;
                    padding: 10px 22px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    color: #6c757d;
                    transition: all 0.2s ease;
                }
                #editTrimesterModal .save-btn {
                    background: #00C1FE;
                    color: #fff;
                    border: none;
                }
                #editTrimesterModal .save-btn:hover {
                    background: #0098cc;
                }
                #editTrimesterModal .cancel-btn:hover {
                    border-color: #dc3545;
                    color: #dc3545;
                }
                </style>
                <div id="editTrimesterModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Edit Trimester</h2>
                            <span class="close" onclick="closeEditTrimesterModal()">&times;</span>
                        </div>
                        <form class="modal-form" method="POST">
                            <input type="hidden" name="edit_trimester" value="1">
                            <input type="hidden" id="edit_id" name="edit_id">
                            <div class="form-group">
                                <label for="edit_trimester_name">Trimester Name</label>
                                <input type="text" id="edit_trimester_name" name="edit_trimester_name" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_start_date">Start Date</label>
                                <input type="date" id="edit_start_date" name="edit_start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_end_date">End Date</label>
                                <input type="date" id="edit_end_date" name="edit_end_date" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <input type="checkbox" id="edit_is_active" name="edit_is_active">
                                <label for="edit_is_active" style="margin:0;">Set as Active</label>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" onclick="closeEditTrimesterModal()">Cancel</button>
                                <button type="submit" class="save-btn">Save Changes</button>
                            </div>
                        </form>
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

        function openEditTrimesterModal(id, name, start, end, isActive) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_trimester_name').value = name;
            document.getElementById('edit_start_date').value = start;
            document.getElementById('edit_end_date').value = end;
            document.getElementById('edit_is_active').checked = !!isActive;
            document.getElementById('editTrimesterModal').style.display = 'block';
        }
        function closeEditTrimesterModal() {
            document.getElementById('editTrimesterModal').style.display = 'none';
        }
        window.onclick = function(event) {
            var modal = document.getElementById('editTrimesterModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 