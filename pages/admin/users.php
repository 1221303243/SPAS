<?php
require_once '../../auth/db_connection.php';

// Handle search and role filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';

// Handle Add User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $edu_level = $_POST['edu_level'] ?? 'Undergraduate';
    $errors = [];

    if (!$name || !$email || !$role || !$password) {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();
            if ($role === 'student') {
                $stmt2 = $conn->prepare("INSERT INTO students (user_id, name, edu_level) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $user_id, $name, $edu_level);
                $stmt2->execute();
                $stmt2->close();
            } elseif ($role === 'lecturer') {
                $stmt2 = $conn->prepare("INSERT INTO lecturers (user_id, name) VALUES (?, ?)");
                $stmt2->bind_param("is", $user_id, $name);
                $stmt2->execute();
                $stmt2->close();
            } elseif ($role === 'admin') {
                $stmt2 = $conn->prepare("INSERT INTO admin (user_id, name) VALUES (?, ?)");
                $stmt2->bind_param("is", $user_id, $name);
                $stmt2->execute();
                $stmt2->close();
            }
            header('Location: users.php?success=1');
            exit();
        } else {
            $errors[] = 'Failed to add user: ' . $conn->error;
        }
    }
}

// Handle Edit User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['edit_user_id']);
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'];
    $edu_level = $_POST['edit_edu_level'] ?? 'Undergraduate';
    $errors = [];
    if (!$name || !$email || !$role) {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE user_id=?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        $stmt->execute();
        $stmt->close();
        if ($role === 'student') {
            $stmt2 = $conn->prepare("UPDATE students SET name=?, edu_level=? WHERE user_id=?");
            $stmt2->bind_param("ssi", $name, $edu_level, $user_id);
            $stmt2->execute();
            $stmt2->close();
        } elseif ($role === 'lecturer') {
            $stmt2 = $conn->prepare("UPDATE lecturers SET name=? WHERE user_id=?");
            $stmt2->bind_param("si", $name, $user_id);
            $stmt2->execute();
            $stmt2->close();
        } elseif ($role === 'admin') {
            $stmt2 = $conn->prepare("UPDATE admin SET name=? WHERE user_id=?");
            $stmt2->bind_param("si", $name, $user_id);
            $stmt2->execute();
            $stmt2->close();
        }
        header('Location: users.php?success=2');
        exit();
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['delete_user_id']);
    $role = $_POST['delete_role'];
    // Delete from role table first
    if ($role === 'student') {
        $conn->query("DELETE FROM students WHERE user_id = $user_id");
    } elseif ($role === 'lecturer') {
        $conn->query("DELETE FROM lecturers WHERE user_id = $user_id");
    } elseif ($role === 'admin') {
        $conn->query("DELETE FROM admin WHERE user_id = $user_id");
    }
    // Delete from users table
    $conn->query("DELETE FROM users WHERE user_id = $user_id");
    header('Location: users.php?success=3');
    exit();
}

// Fetch users by role, including their respective IDs
$roles = ['admin' => 'Admin', 'lecturer' => 'Lecturer', 'student' => 'Student'];
$users_by_role = [];
foreach ($roles as $role_key => $role_label) {
    // Only show this role if no filter or filter matches
    if ($role_filter && $role_filter !== $role_key) {
        $users_by_role[$role_key] = [];
        continue;
    }
    $where = 'u.role = ?';
    $params = [$role_key];
    $types = 's';
    if ($search) {
        $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?';
        if ($role_key === 'student') {
            $where .= ' OR s.student_id LIKE ?';
        } elseif ($role_key === 'lecturer') {
            $where .= ' OR l.lecturer_id LIKE ?';
        } elseif ($role_key === 'admin') {
            $where .= ' OR a.admin_id LIKE ?';
        }
        $where .= ')';
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
        if ($role_key === 'student' || $role_key === 'lecturer' || $role_key === 'admin') {
            $params[] = $search_param;
            $types .= 's';
        }
    }
    if ($role_key === 'student') {
        $sql = "SELECT u.user_id, u.username, u.email, u.role, s.student_id, s.edu_level FROM users u LEFT JOIN students s ON u.user_id = s.user_id WHERE $where";
    } elseif ($role_key === 'lecturer') {
        $sql = "SELECT u.user_id, u.username, u.email, u.role, l.lecturer_id FROM users u LEFT JOIN lecturers l ON u.user_id = l.user_id WHERE $where";
    } else {
        $sql = "SELECT u.user_id, u.username, u.email, u.role, a.admin_id FROM users u LEFT JOIN admin a ON u.user_id = a.user_id WHERE $where";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $users_by_role[$role_key] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - User Management</title>
    <link rel="stylesheet" href="../../css/admin_users.css" />
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
        .role-section {
            margin-bottom: 40px;
        }
        .role-section h2 {
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Users</h1>
            <button class="add-user-btn" onclick="openAddUserModal()">
                Add User
                <span class="material-icons">add</span>
            </button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <?php
                if ($_GET['success'] == 1) echo 'User added successfully!';
                elseif ($_GET['success'] == 2) echo 'User updated successfully!';
                elseif ($_GET['success'] == 3) echo 'User deleted successfully!';
                ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="search-filter-container" style="margin-bottom:24px;">
            <div class="search-bar">
                <span class="material-icons search-icon">search</span>
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" />
            </div>
            <div class="filter-options">
                <select name="role_filter" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?php if($role_filter==='admin') echo 'selected'; ?>>Admin</option>
                    <option value="lecturer" <?php if($role_filter==='lecturer') echo 'selected'; ?>>Lecturer</option>
                    <option value="student" <?php if($role_filter==='student') echo 'selected'; ?>>Student</option>
                </select>
                <button type="submit" class="filters-btn">
                    <span class="material-icons">filter_list</span>
                    Apply
                </button>
                <?php if ($search || $role_filter): ?>
                    <a href="users.php" class="filters-btn" style="text-decoration: none; color: inherit;">
                        <span class="material-icons">clear</span>
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php
        // Only show the selected role's table if a role filter is applied
        $roles_to_show = $role_filter && isset($roles[$role_filter]) ? [$role_filter => $roles[$role_filter]] : $roles;
        foreach ($roles_to_show as $role_key => $role_label): ?>
            <div class="role-section">
                <h2><?php echo $role_label; ?>s</h2>
                <table class="users-list-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <?php if ($role_key === 'student'): ?>
                                <th>Student ID</th>
                                <th>Education Level</th>
                            <?php elseif ($role_key === 'lecturer'): ?>
                                <th>Lecturer ID</th>
                            <?php elseif ($role_key === 'admin'): ?>
                                <th>Admin ID</th>
                            <?php endif; ?>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($users_by_role[$role_key])): ?>
                        <?php foreach ($users_by_role[$role_key] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <?php if ($role_key === 'student'): ?>
                                    <td><?php echo htmlspecialchars($user['student_id'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['edu_level'] ?? '-'); ?></td>
                                <?php elseif ($role_key === 'lecturer'): ?>
                                    <td><?php echo htmlspecialchars($user['lecturer_id'] ?? '-'); ?></td>
                                <?php elseif ($role_key === 'admin'): ?>
                                    <td><?php echo htmlspecialchars($user['admin_id'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td>
                                    <span class='material-icons edit-btn' onclick='openEditUserModal(<?php echo htmlspecialchars(json_encode([
                                        'user_id' => $user['user_id'],
                                        'name' => $user['username'],
                                        'email' => $user['email'],
                                        'role' => $user['role'],
                                        'student_id' => $user['student_id'] ?? null,
                                        'lecturer_id' => $user['lecturer_id'] ?? null,
                                        'admin_id' => $user['admin_id'] ?? null,
                                        'edu_level' => $user['edu_level'] ?? null
                                    ])); ?>)'>edit</span>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $user['user_id']; ?>">
                                        <input type="hidden" name="delete_role" value="<?php echo $user['role']; ?>">
                                        <button type="submit" class="material-icons delete-btn" style="background:none;border:none;color:#666;cursor:pointer;">delete</button>
                                    </form>
                                    <button class='view-details-btn' type="button" onclick='openViewUserModal(<?php echo htmlspecialchars(json_encode([
                                        'user_id' => $user['user_id'],
                                        'name' => $user['username'],
                                        'email' => $user['email'],
                                        'role' => $user['role'],
                                        'student_id' => $user['student_id'] ?? null,
                                        'lecturer_id' => $user['lecturer_id'] ?? null,
                                        'admin_id' => $user['admin_id'] ?? null,
                                        'edu_level' => $user['edu_level'] ?? null
                                    ])); ?>)'>View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#888;">No <?php echo strtolower($role_label); ?>s found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Add User Modal -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New User</h2>
                    <span class="close" onclick="closeAddUserModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group" id="add-edu-level-group" style="display:none;">
                        <label for="add_edu_level">Education Level</label>
                        <select id="add_edu_level" name="edu_level">
                            <option value="Foundation">Foundation</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Undergraduate">Undergraduate</option>
                            <option value="Postgraduate">Postgraduate</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeAddUserModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save User</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit User</h2>
                    <span class="close" onclick="closeEditUserModal()">&times;</span>
                </div>
                <form class="modal-form" method="POST">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" id="edit_user_id" name="edit_user_id">
                    <input type="hidden" id="edit_role" name="edit_role">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" id="edit_name" name="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="edit_email" required>
                    </div>
                    <div class="form-group" id="edit-edu-level-group" style="display:none;">
                        <label for="edit_edu_level">Education Level</label>
                        <select id="edit_edu_level" name="edit_edu_level">
                            <option value="Foundation">Foundation</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Undergraduate">Undergraduate</option>
                            <option value="Postgraduate">Postgraduate</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeEditUserModal()">Cancel</button>
                        <button type="submit" class="save-btn">Update User</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- View User Modal -->
        <div id="viewUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>User Details</h2>
                    <span class="close" onclick="closeViewUserModal()">&times;</span>
                </div>
                <div class="modal-body" id="viewUserBody">
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            setEditEduLevelVisibility(user.role, user.edu_level);
            document.getElementById('editUserModal').style.display = 'block';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        function openViewUserModal(user) {
            let idLabel = '';
            let idValue = '';
            if (user.role === 'student') {
                idLabel = 'Student ID';
                idValue = user.student_id || '-';
            } else if (user.role === 'lecturer') {
                idLabel = 'Lecturer ID';
                idValue = user.lecturer_id || '-';
            } else if (user.role === 'admin') {
                idLabel = 'Admin ID';
                idValue = user.admin_id || '-';
            }
            let roleBadge = `<span style='display:inline-block;padding:2px 10px;border-radius:12px;background:#00C1FE;color:#fff;font-size:13px;margin-left:8px;'>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span>`;
            let html = `<div style='padding:10px 0 0 0;'>`;
            html += `<dl style='margin:0;'>`;
            html += `<dt style='font-weight:600;margin-bottom:4px;'>User ID</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${user.user_id}</dd>`;
            if (idLabel) {
                html += `<dt style='font-weight:600;margin-bottom:4px;'>${idLabel}</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${idValue}</dd>`;
            }
            html += `<dt style='font-weight:600;margin-bottom:4px;'>Name</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${user.name || '-'}</dd>`;
            html += `<dt style='font-weight:600;margin-bottom:4px;'>Email</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${user.email || '-'}</dd>`;
            html += `<dt style='font-weight:600;margin-bottom:4px;'>Role</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${roleBadge}</dd>`;
            html += `<dt style='font-weight:600;margin-bottom:4px;'>Education Level</dt><dd style='margin:0 0 12px 0;padding-left:24px;'>${user.edu_level || '-'}</dd>`;
            html += `</dl></div>`;
            document.getElementById('viewUserBody').innerHTML = html;
            document.getElementById('viewUserModal').style.display = 'block';
        }

        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addUserModal');
            const editModal = document.getElementById('editUserModal');
            const viewModal = document.getElementById('viewUserModal');
            if (event.target == addModal) addModal.style.display = 'none';
            if (event.target == editModal) editModal.style.display = 'none';
            if (event.target == viewModal) viewModal.style.display = 'none';
        }

        // Add User Modal: show edu_level if student
        const addRoleSelect = document.getElementById('role');
        const addEduLevelGroup = document.getElementById('add-edu-level-group');
        addRoleSelect.addEventListener('change', function() {
            addEduLevelGroup.style.display = (this.value === 'student') ? 'block' : 'none';
        });

        // Edit User Modal: show edu_level if student
        const editRoleInput = document.getElementById('edit_role');
        const editEduLevelGroup = document.getElementById('edit-edu-level-group');
        const editEduLevelSelect = document.getElementById('edit_edu_level');
        function setEditEduLevelVisibility(role, eduLevel) {
            if (role === 'student') {
                editEduLevelGroup.style.display = 'block';
                if (eduLevel) editEduLevelSelect.value = eduLevel;
            } else {
                editEduLevelGroup.style.display = 'none';
            }
        }

        // Patch openEditUserModal to set edu_level
        const originalOpenEditUserModal = openEditUserModal;
        openEditUserModal = function(user) {
            originalOpenEditUserModal(user);
            setEditEduLevelVisibility(user.role, user.edu_level);
        };
    </script>
</body>
</html>