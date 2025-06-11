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

        <div class="search-filter-container">
            <div class="search-bar">
                <span class="material-icons search-icon">search</span>
                <input type="text" placeholder="Search users..." />
            </div>
            <div class="filter-options">
                <select class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="student">Student</option>
                </select>
                <select class="filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
                <button class="filters-btn">
                    <span class="material-icons">filter_list</span>
                    More Filters
                </button>
            </div>
        </div>

        <div class="users-list-container">
            <table class="users-list-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Sample data - In a real application, this would come from a database
                $users = array(
                    array(
                        "id" => "USR001",
                        "name" => "John Legend",
                        "email" => "john.legend@gmail.com",
                        "phone" => "+1234567890",
                        "role" => "Admin",
                        "department" => "IT Department",
                        "status" => "active",
                        "avatar" => "JL"
                    ),
                    array(
                        "id" => "USR002",
                        "name" => "Sarah Johnson",
                        "email" => "sarah.johnson@university.edu",
                        "phone" => "+1234567891",
                        "role" => "Lecturer",
                        "department" => "Mathematics",
                        "status" => "active",
                        "avatar" => "SJ"
                    ),
                    array(
                        "id" => "USR003",
                        "name" => "Michael Brown",
                        "email" => "michael.brown@university.edu",
                        "phone" => "+1234567892",
                        "role" => "Student",
                        "department" => "Computer Science",
                        "status" => "active",
                        "avatar" => "MB"
                    ),
                    array(
                        "id" => "USR004",
                        "name" => "Emily Davis",
                        "email" => "emily.davis@university.edu",
                        "phone" => "+1234567893",
                        "role" => "Lecturer",
                        "department" => "Physics",
                        "status" => "inactive",
                        "avatar" => "ED"
                    ),
                    array(
                        "id" => "USR005",
                        "name" => "David Wilson",
                        "email" => "david.wilson@university.edu",
                        "phone" => "+1234567894",
                        "role" => "Student",
                        "department" => "Engineering",
                        "status" => "active",
                        "avatar" => "DW"
                    ),
                    array(
                        "id" => "USR006",
                        "name" => "Lisa Anderson",
                        "email" => "lisa.anderson@university.edu",
                        "phone" => "+1234567895",
                        "role" => "Lecturer",
                        "department" => "English",
                        "status" => "active",
                        "avatar" => "LA"
                    )
                );

                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['name']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['role']}</td>";
                    echo "<td><span class='status-badge {$user['status']}'>{$user['status']}</span></td>";
                    echo "<td>";
                    echo "<span class='material-icons edit-btn' onclick='editUser(\"{$user['id']}\")'>edit</span> ";
                    echo "<span class='material-icons delete-btn' onclick='deleteUser(\"{$user['id']}\")'>delete</span> ";
                    echo "<span class='material-icons more-btn'>more_vert</span> ";
                    echo "<button class='view-details-btn'>View Details</button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <!-- Add User Modal -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New User</h2>
                    <span class="close" onclick="closeAddUserModal()">&times;</span>
                </div>
                <form class="modal-form">
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
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeAddUserModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save User</button>
                    </div>
                </form>
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

        function editUser(userId) {
            // Implementation for editing user
            console.log('Edit user:', userId);
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                // Implementation for deleting user
                console.log('Delete user:', userId);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
