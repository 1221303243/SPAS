<?php
require_once '../../auth/db_connection.php';

// Handle Add Class form submission
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['className']);
    $subject_id = intval($_POST['subject']);
    $lecturer_id = intval($_POST['lecturer']);
    
    if (!$class_name || !$subject_id || !$lecturer_id) {
        $errors[] = 'All fields are required.';
    } else {
        // Fetch edu_level from subject
        $edu_level = 'Undergraduate';
        $subject_stmt = $conn->prepare("SELECT edu_level FROM subjects WHERE subject_id = ?");
        $subject_stmt->bind_param("i", $subject_id);
        $subject_stmt->execute();
        $subject_stmt->bind_result($edu_level);
        $subject_stmt->fetch();
        $subject_stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO classes (class_name, edu_level, subject_id, lecturer_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $class_name, $edu_level, $subject_id, $lecturer_id);
        if ($stmt->execute()) {
            $success = true;
            header('Location: classes.php?success=1');
            exit();
        } else {
            $errors[] = 'Failed to add class: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Edit Class form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    $edit_class_id = intval($_POST['edit_class_id']);
    $edit_class_name = trim($_POST['edit_className']);
    $edit_subject_id = intval($_POST['edit_subject']);
    $edit_lecturer_id = intval($_POST['edit_lecturer']);
    
    if (!$edit_class_id || !$edit_class_name || !$edit_subject_id || !$edit_lecturer_id) {
        $errors[] = 'All fields are required for editing.';
    } else {
        // Fetch edu_level from subject
        $edit_edu_level = 'Undergraduate';
        $subject_stmt = $conn->prepare("SELECT edu_level FROM subjects WHERE subject_id = ?");
        $subject_stmt->bind_param("i", $edit_subject_id);
        $subject_stmt->execute();
        $subject_stmt->bind_result($edit_edu_level);
        $subject_stmt->fetch();
        $subject_stmt->close();
        
        $stmt = $conn->prepare("UPDATE classes SET class_name=?, edu_level=?, subject_id=?, lecturer_id=? WHERE class_id=?");
        $stmt->bind_param("ssiii", $edit_class_name, $edit_edu_level, $edit_subject_id, $edit_lecturer_id, $edit_class_id);
        if ($stmt->execute()) {
            header('Location: classes.php?success=2');
            exit();
        } else {
            $errors[] = 'Failed to update class: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Class form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $delete_class_id = intval($_POST['delete_class_id']);
    if ($delete_class_id) {
        $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->bind_param("i", $delete_class_id);
        if ($stmt->execute()) {
            header('Location: classes.php?success=3');
            exit();
        } else {
            $errors[] = 'Failed to delete class: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all subjects for dropdown
$subjects = [];
$result_sub = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
if ($result_sub) {
    while ($row = $result_sub->fetch_assoc()) {
        $subjects[] = $row;
    }
}
// Fetch all lecturers for dropdown
$lecturers = [];
$result_lect = $conn->query("SELECT lecturer_id, name FROM lecturers ORDER BY name ASC");
if ($result_lect) {
    while ($row = $result_lect->fetch_assoc()) {
        $lecturers[] = $row;
    }
}

// At the top, get the filter value:
$edu_level_filter = isset($_GET['edu_level_filter']) ? $_GET['edu_level_filter'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Class Management</title>
    <link rel="stylesheet" href="../../css/admin_classes.css" />
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
            <h1>Classes</h1>
            <button class="add-class-btn" onclick="openAddClassModal()">
                Add Class
                <span class="material-icons">add</span>
            </button>
        </div>

        <form method="GET" class="search-filter-container">
            <div class="search-bar">
                <span class="material-icons search-icon">search</span>
                <input type="text" name="search" placeholder="Search by Class ID or Class Name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" />
            </div>
            <div class="filter-options">
                <select name="edu_level_filter" class="filter-select">
                    <option value="">All Levels</option>
                    <option value="Foundation" <?php if($edu_level_filter==='Foundation') echo 'selected'; ?>>Foundation</option>
                    <option value="Diploma" <?php if($edu_level_filter==='Diploma') echo 'selected'; ?>>Diploma</option>
                    <option value="Undergraduate" <?php if($edu_level_filter==='Undergraduate') echo 'selected'; ?>>Undergraduate</option>
                    <option value="Postgraduate" <?php if($edu_level_filter==='Postgraduate') echo 'selected'; ?>>Postgraduate</option>
                </select>
                <button type="submit" class="filters-btn">
                    <span class="material-icons">filter_list</span>
                    Apply
                </button>
                <?php if (!empty($_GET['search']) || !empty($_GET['edu_level_filter'])): ?>
                    <a href="classes.php" class="filters-btn" style="text-decoration: none; color: inherit;">
                        <span class="material-icons">clear</span>
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="classes-list-container">
            <table class="classes-list-table" id="classesTable">
                <thead>
                    <tr>
                        <th>Class ID</th>
                        <th>Class Name</th>
                        <th>Education Level</th>
                        <th>Subject</th>
                        <th>Lecturer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Fetch all classes with subject and lecturer info
                $classes = [];
                $sql = "SELECT c.class_id, c.class_name, s.edu_level, c.semester, c.year, s.subject_name, l.name AS lecturer_name, c.subject_id, c.lecturer_id FROM classes c LEFT JOIN subjects s ON c.subject_id = s.subject_id LEFT JOIN lecturers l ON c.lecturer_id = l.lecturer_id";
                $where = [];
                $params = [];
                $types = '';
                
                // Education level filter
                if ($edu_level_filter) {
                    $where[] = 's.edu_level = ?';
                    $params[] = $edu_level_filter;
                    $types .= 's';
                }
                
                // Search functionality - search by class ID or class name
                if (!empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $where[] = '(c.class_id LIKE ? OR c.class_name LIKE ?)';
                    $params = array_merge($params, [$search, $search]);
                    $types .= 'ss';
                }
                
                if ($where) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
                }
                $sql .= ' ORDER BY c.class_id ASC';
                
                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $classes[] = $row;
                    }
                }

                if (!empty($classes)) {
                    foreach ($classes as $class) {
                        $class_json = htmlspecialchars(json_encode([
                            'class_id' => $class['class_id'],
                            'class_name' => $class['class_name'],
                            'edu_level' => $class['edu_level'],
                            'subject_name' => $class['subject_name'],
                            'lecturer_name' => $class['lecturer_name'],
                            'subject_id' => $class['subject_id'] ?? '',
                            'lecturer_id' => $class['lecturer_id'] ?? ''
                        ]), ENT_QUOTES, 'UTF-8');
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($class['class_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($class['class_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($class['edu_level'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($class['subject_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($class['lecturer_name']) . "</td>";
                        echo "<td>";
                        echo "<span class='material-icons edit-btn' data-class='$class_json' onclick='openEditClassModal(this)'>edit</span> ";
                        echo "<form method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure you want to delete this class?');\">";
                        echo "<input type='hidden' name='delete_class' value='1'>";
                        echo "<input type='hidden' name='delete_class_id' value='" . htmlspecialchars($class['class_id']) . "'>";
                        echo "<button type='submit' class='material-icons delete-btn' style='background:none;border:none;color:#666;cursor:pointer;'>delete</button>";
                        echo "</form> ";
                        echo "<span class='material-icons more-btn'>more_vert</span> ";
                        echo "<button class='manage-students-btn' onclick='window.location.href=\"manage_students.php?class_id=" . htmlspecialchars($class['class_id']) . "\"'>Manage Students</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    $searchTerm = !empty($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
                    $message = $searchTerm ? "No classes found matching '$searchTerm'" : "No classes found";
                    echo "<tr><td colspan='6' style='text-align:center; color:#888;'>$message</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">Class added successfully!</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">Class updated successfully!</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] == 3): ?>
            <div class="alert alert-success">Class deleted successfully!</div>
        <?php endif; ?>

        <!-- Add Class Modal -->
        <div id="addClassModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Class</h2>
                    <span class="close" onclick="closeAddClassModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST" name="addClassForm">
                    <input type="hidden" name="add_class" value="1">
                    <div class="form-group">
                        <label for="className">Class Name</label>
                        <input type="text" id="className" name="className" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?php echo $sub['subject_id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="lecturer">Lecturer</label>
                        <select id="lecturer" name="lecturer" required>
                            <option value="">Select Lecturer</option>
                            <?php foreach ($lecturers as $lect): ?>
                                <option value="<?php echo $lect['lecturer_id']; ?>"><?php echo htmlspecialchars($lect['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeAddClassModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save Class</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Class Modal -->
        <div id="editClassModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Class</h2>
                    <span class="close" onclick="closeEditClassModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST" name="editClassForm">
                    <input type="hidden" name="edit_class" value="1">
                    <input type="hidden" id="edit_class_id" name="edit_class_id">
                    <div class="form-group">
                        <label for="edit_className">Class Name</label>
                        <input type="text" id="edit_className" name="edit_className" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_subject">Subject</label>
                        <select id="edit_subject" name="edit_subject" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?php echo $sub['subject_id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_lecturer">Lecturer</label>
                        <select id="edit_lecturer" name="edit_lecturer" required>
                            <option value="">Select Lecturer</option>
                            <?php foreach ($lecturers as $lect): ?>
                                <option value="<?php echo $lect['lecturer_id']; ?>"><?php echo htmlspecialchars($lect['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeEditClassModal()">Cancel</button>
                        <button type="submit" class="save-btn">Update Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddClassModal() {
            document.getElementById('addClassModal').style.display = 'block';
        }

        function closeAddClassModal() {
            document.getElementById('addClassModal').style.display = 'none';
        }

        function editClass(classId) {
            // Implementation for editing class
            console.log('Edit class:', classId);
        }

        function deleteClass(classId) {
            if (confirm('Are you sure you want to delete this class?')) {
                // Implementation for deleting class
                console.log('Delete class:', classId);
            }
        }

        function openEditClassModal(el) {
            var classData = JSON.parse(el.getAttribute('data-class'));
            document.getElementById('edit_class_id').value = classData.class_id;
            document.getElementById('edit_className').value = classData.class_name;
            document.getElementById('edit_subject').value = classData.subject_id;
            document.getElementById('edit_lecturer').value = classData.lecturer_id;
            document.getElementById('editClassModal').style.display = 'block';
        }

        function closeEditClassModal() {
            document.getElementById('editClassModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addClassModal');
            const editModal = document.getElementById('editClassModal');
            if (event.target == addModal) {
                addModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 