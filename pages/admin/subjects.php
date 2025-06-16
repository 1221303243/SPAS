<?php
require_once '../../auth/db_connection.php';

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_code = trim($_POST['subjectCode']);
    $subject_name = trim($_POST['subjectName']);
    $description = trim($_POST['description']);
    $lecturer_id = $_POST['lecturer'];
    $edu_level = $_POST['edu_level'] ?? 'Undergraduate';
    $errors = [];
    if (!$subject_code || !$subject_name || !$description || !$lecturer_id) {
        $errors[] = 'All fields are required.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, description, edu_level, lecturer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $subject_code, $subject_name, $description, $edu_level, $lecturer_id);
        if ($stmt->execute()) {
            header('Location: subjects.php?success=1');
            exit();
        } else {
            $errors[] = 'Failed to add subject: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Edit Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $subject_id = intval($_POST['edit_subject_id']);
    $subject_code = trim($_POST['edit_subjectCode']);
    $subject_name = trim($_POST['edit_subjectName']);
    $description = trim($_POST['edit_description']);
    $lecturer_id = $_POST['edit_lecturer'];
    $edu_level = $_POST['edit_edu_level'] ?? 'Undergraduate';
    $errors = [];
    if (!$subject_code || !$subject_name || !$description || !$lecturer_id) {
        $errors[] = 'All fields are required.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE subjects SET subject_code=?, subject_name=?, description=?, edu_level=?, lecturer_id=? WHERE subject_id=?");
        $stmt->bind_param("ssssii", $subject_code, $subject_name, $description, $edu_level, $lecturer_id, $subject_id);
        if ($stmt->execute()) {
            header('Location: subjects.php?success=2');
            exit();
        } else {
            $errors[] = 'Failed to update subject: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = intval($_POST['delete_subject_id']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    if ($stmt->execute()) {
        header('Location: subjects.php?success=3');
        exit();
    } else {
        $errors[] = 'Failed to delete subject: ' . $conn->error;
    }
    $stmt->close();
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

// Fetch all subjects from the database
$subjects = [];
$sql = "SELECT s.subject_id, s.subject_code, s.subject_name, s.description, s.edu_level, s.lecturer_id, l.name AS lecturer_name
        FROM subjects s
        LEFT JOIN lecturers l ON s.lecturer_id = l.lecturer_id";
$where = [];
$params = [];
$types = '';
if ($edu_level_filter) {
    $where[] = 's.edu_level = ?';
    $params[] = $edu_level_filter;
    $types .= 's';
}
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where[] = '(s.subject_name LIKE ? OR s.subject_code LIKE ? OR s.description LIKE ? OR l.name LIKE ? OR s.edu_level LIKE ? )';
    $params = array_merge($params, [$search, $search, $search, $search, $search]);
    $types .= 'sssss';
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.subject_id ASC';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Subject Management</title>
    <link rel="stylesheet" href="../../css/admin_subjects.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        html, body { margin: 0; padding: 0; height: 100%; width: 100%; overflow-x: hidden; }
        body { display: flex; }
        .container { flex: 1; margin-left: 318px; padding: 20px; }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    <div class="container">
        <div class="header">
            <h1>Subjects</h1>
            <button class="add-subject-btn" onclick="openAddSubjectModal()">
                Add Subject
                <span class="material-icons">add</span>
            </button>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] == 1) echo 'Subject added successfully!';
                elseif ($_GET['success'] == 2) echo 'Subject updated successfully!';
                elseif ($_GET['success'] == 3) echo 'Subject deleted successfully!';
                ?>
            </div>
        <?php endif; ?>
        <div class="subjects-list-container">
            <div class="search-filter-container">
                <form method="GET" style="width:100%;">
                    <div class="search-bar" style="position:relative; margin-bottom:0;">
                        <span class="material-icons search-icon" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#6c757d; font-size:20px;">search</span>
                        <input type="text" name="search" placeholder="Search subjects..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding-left:44px; width:100%;" />
                    </div>
                    <div class="filter-options" style="margin-top:16px;display:flex;align-items:center;gap:8px;">
                        <select name="edu_level_filter" class="filter-select">
                            <option value="">All Levels</option>
                            <option value="Foundation" <?php if($edu_level_filter==='Foundation') echo 'selected'; ?>>Foundation</option>
                            <option value="Diploma" <?php if($edu_level_filter==='Diploma') echo 'selected'; ?>>Diploma</option>
                            <option value="Undergraduate" <?php if($edu_level_filter==='Undergraduate') echo 'selected'; ?>>Undergraduate</option>
                            <option value="Postgraduate" <?php if($edu_level_filter==='Postgraduate') echo 'selected'; ?>>Postgraduate</option>
                        </select>
                        <button type="submit" class="filters-btn"><span class="material-icons">filter_list</span>Apply</button>
                    </div>
                </form>
            </div>
            <table class="subjects-list-table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Description</th>
                        <th>Education Level</th>
                        <th>Lecturer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($subjects)): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($subject['description']); ?></td>
                            <td><?php echo htmlspecialchars($subject['edu_level'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($subject['lecturer_name']); ?></td>
                            <td>
                                <span class='material-icons edit-btn'
                                    data-subject='<?php echo htmlspecialchars(json_encode([
                                        'subject_id' => $subject['subject_id'],
                                        'subject_code' => $subject['subject_code'],
                                        'subject_name' => $subject['subject_name'],
                                        'description' => $subject['description'],
                                        'edu_level' => $subject['edu_level'],
                                        'lecturer_id' => $subject['lecturer_id']
                                    ]), ENT_QUOTES, 'UTF-8'); ?>'
                                    onclick='openEditSubjectModal(this)'>edit</span>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                    <input type="hidden" name="delete_subject" value="1">
                                    <input type="hidden" name="delete_subject_id" value="<?php echo $subject['subject_id']; ?>">
                                    <button type="submit" class="material-icons delete-btn" style="background:none;border:none;color:#666;cursor:pointer;">delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#888;">No subjects found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Add Subject Modal -->
        <div id="addSubjectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Subject</h2>
                    <span class="close" onclick="closeAddSubjectModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST">
                    <input type="hidden" name="add_subject" value="1">
                    <div class="form-group">
                        <label for="subjectCode">Subject Code</label>
                        <input type="text" id="subjectCode" name="subjectCode" required>
                    </div>
                    <div class="form-group">
                        <label for="subjectName">Subject Name</label>
                        <input type="text" id="subjectName" name="subjectName" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edu_level">Education Level</label>
                        <select id="edu_level" name="edu_level" required>
                            <option value="Foundation">Foundation</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Undergraduate">Undergraduate</option>
                            <option value="Postgraduate">Postgraduate</option>
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
                        <button type="button" class="cancel-btn" onclick="closeAddSubjectModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Subject Modal -->
        <div id="editSubjectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Subject</h2>
                    <span class="close" onclick="closeEditSubjectModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST">
                    <input type="hidden" name="edit_subject" value="1">
                    <input type="hidden" id="edit_subject_id" name="edit_subject_id">
                    <div class="form-group">
                        <label for="edit_subjectCode">Subject Code</label>
                        <input type="text" id="edit_subjectCode" name="edit_subjectCode" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_subjectName">Subject Name</label>
                        <input type="text" id="edit_subjectName" name="edit_subjectName" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="edit_description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_edu_level">Education Level</label>
                        <select id="edit_edu_level" name="edit_edu_level" required>
                            <option value="Foundation">Foundation</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Undergraduate">Undergraduate</option>
                            <option value="Postgraduate">Postgraduate</option>
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
                        <button type="button" class="cancel-btn" onclick="closeEditSubjectModal()">Cancel</button>
                        <button type="submit" class="save-btn">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Move all modal JS functions here, just before </body> -->
    <script>
    function openAddSubjectModal() {
        document.getElementById('addSubjectModal').style.display = 'block';
    }
    function closeAddSubjectModal() {
        document.getElementById('addSubjectModal').style.display = 'none';
    }
    function openEditSubjectModal(el) {
        var subject = JSON.parse(el.getAttribute('data-subject'));
        document.getElementById('edit_subject_id').value = subject.subject_id;
        document.getElementById('edit_subjectCode').value = subject.subject_code;
        document.getElementById('edit_subjectName').value = subject.subject_name;
        document.getElementById('edit_description').value = subject.description;
        document.getElementById('edit_edu_level').value = subject.edu_level || 'Undergraduate';
        document.getElementById('edit_lecturer').value = subject.lecturer_id;
        document.getElementById('editSubjectModal').style.display = 'block';
    }
    function closeEditSubjectModal() {
        document.getElementById('editSubjectModal').style.display = 'none';
    }
    function deleteSubject(subjectId) {
        if (confirm('Are you sure you want to delete this subject?')) {
            // Implementation for deleting subject
            console.log('Delete subject:', subjectId);
        }
    }
    window.onclick = function(event) {
        const addModal = document.getElementById('addSubjectModal');
        const editModal = document.getElementById('editSubjectModal');
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
    }
    </script>
</body>
</html>
