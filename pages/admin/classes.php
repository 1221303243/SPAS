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

        <div class="search-filter-container">
            <div class="search-bar">
                <span class="material-icons search-icon">search</span>
                <input type="text" placeholder="Search classes..." />
            </div>
            <div class="filter-options">
                <select class="filter-select">
                    <option value="">All Subjects</option>
                    <option value="math">Mathematics</option>
                    <option value="science">Science</option>
                    <option value="english">English</option>
                    <option value="history">History</option>
                </select>
                <select class="filter-select">
                    <option value="">All Lecturers</option>
                    <option value="dr-smith">Dr. Smith</option>
                    <option value="prof-johnson">Prof. Johnson</option>
                    <option value="ms-brown">Ms. Brown</option>
                </select>
                <button class="filters-btn">
                    <span class="material-icons">filter_list</span>
                    More Filters
                </button>
            </div>
        </div>

        <div class="classes-list-container">
            <table class="classes-list-table">
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Class Name</th>
                        <th>Subject</th>
                        <th>Lecturer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Sample data - In a real application, this would come from a database
                $classes = array(
                    array(
                        "id" => "MATH101",
                        "name" => "Introduction to Calculus",
                        "subject" => "Mathematics",
                        "lecturer" => "Dr. Smith",
                        "students" => 25,
                        "schedule" => "Mon, Wed, Fri 9:00 AM",
                        "room" => "Room 201",
                        "status" => "active"
                    ),
                    array(
                        "id" => "PHYS101",
                        "name" => "Physics Fundamentals",
                        "subject" => "Science",
                        "lecturer" => "Prof. Johnson",
                        "students" => 30,
                        "schedule" => "Tue, Thu 10:30 AM",
                        "room" => "Lab 105",
                        "status" => "active"
                    ),
                    array(
                        "id" => "ENG101",
                        "name" => "English Composition",
                        "subject" => "English",
                        "lecturer" => "Ms. Brown",
                        "students" => 20,
                        "schedule" => "Mon, Wed 2:00 PM",
                        "room" => "Room 301",
                        "status" => "active"
                    ),
                    array(
                        "id" => "HIST101",
                        "name" => "World History",
                        "subject" => "History",
                        "lecturer" => "Dr. Davis",
                        "students" => 28,
                        "schedule" => "Tue, Thu 1:00 PM",
                        "room" => "Room 401",
                        "status" => "inactive"
                    ),
                    array(
                        "id" => "CHEM101",
                        "name" => "General Chemistry",
                        "subject" => "Science",
                        "lecturer" => "Prof. Wilson",
                        "students" => 35,
                        "schedule" => "Mon, Wed, Fri 11:00 AM",
                        "room" => "Lab 201",
                        "status" => "active"
                    ),
                    array(
                        "id" => "BIO101",
                        "name" => "Biology Basics",
                        "subject" => "Science",
                        "lecturer" => "Dr. Miller",
                        "students" => 22,
                        "schedule" => "Tue, Thu 3:30 PM",
                        "room" => "Lab 301",
                        "status" => "active"
                    )
                );

                foreach ($classes as $class) {
                    $statusClass = $class['status'] === 'active' ? 'active' : 'inactive';
                    echo "<tr>";
                    echo "<td>{$class['id']}</td>";
                    echo "<td>{$class['name']}</td>";
                    echo "<td>{$class['subject']}</td>";
                    echo "<td>{$class['lecturer']}</td>";
                    echo "<td><span class='status-badge {$statusClass}'>{$class['status']}</span></td>";
                    echo "<td>";
                    echo "<span class='material-icons edit-btn' onclick='editClass(\"{$class['id']}\")'>edit</span> ";
                    echo "<span class='material-icons delete-btn' onclick='deleteClass(\"{$class['id']}\")'>delete</span> ";
                    echo "<span class='material-icons more-btn'>more_vert</span> ";
                    echo "<button class='manage-students-btn' onclick='openManageStudentsModal(\"{$class['id']}\")'>Manage Students</button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <!-- Add Class Modal -->
        <div id="addClassModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Class</h2>
                    <span class="close" onclick="closeAddClassModal()">&times;</span>
                </div>
                <form class="modal-form">
                    <div class="form-group">
                        <label for="classId">Class Code</label>
                        <input type="text" id="classId" name="classId" required>
                    </div>
                    <div class="form-group">
                        <label for="className">Class Name</label>
                        <input type="text" id="className" name="className" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            <option value="math">Mathematics</option>
                            <option value="science">Science</option>
                            <option value="english">English</option>
                            <option value="history">History</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="lecturer">Lecturer</label>
                        <select id="lecturer" name="lecturer" required>
                            <option value="">Select Lecturer</option>
                            <option value="dr-smith">Dr. Smith</option>
                            <option value="prof-johnson">Prof. Johnson</option>
                            <option value="ms-brown">Ms. Brown</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeAddClassModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save Class</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Students Modal -->
        <div id="manageStudentsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Manage Students in Class</h2>
                    <span class="close" onclick="closeManageStudentsModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="students-list-section">
                        <h3>Enrolled Students</h3>
                        <ul id="enrolledStudentsList">
                            <!-- Dynamically filled with JS -->
                        </ul>
                    </div>
                    <div class="add-student-section">
                        <h3>Add Student</h3>
                        <input type="text" id="studentSearchInput" placeholder="Search student by name or ID...">
                        <ul id="studentSearchResults">
                            <!-- Dynamically filled with JS -->
                        </ul>
                    </div>
                </div>
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

        function openManageStudentsModal(classId) {
            document.getElementById('manageStudentsModal').style.display = 'block';
            // TODO: Load students for the classId via AJAX or backend call
        }

        function closeManageStudentsModal() {
            document.getElementById('manageStudentsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addClassModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 