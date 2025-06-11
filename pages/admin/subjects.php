<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Subject Management</title>
    <link rel="stylesheet" href="../../css/admin_subjects.css" />
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
            <h1>Subjects</h1>
            <button class="add-subject-btn" onclick="openAddSubjectModal()">
                Add Subject
                <span class="material-icons">add</span>
            </button>
        </div>

        <div class="search-filter-container">
            <div class="search-bar">
                <span class="material-icons search-icon">search</span>
                <input type="text" placeholder="Search subjects..." />
            </div>
            <div class="filter-options">
                <select class="filter-select">
                    <option value="">All Categories</option>
                    <option value="core">Core Subjects</option>
                    <option value="elective">Elective Subjects</option>
                    <option value="lab">Laboratory</option>
                </select>
                <select class="filter-select">
                    <option value="">All Levels</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
                <button class="filters-btn">
                    <span class="material-icons">filter_list</span>
                    More Filters
                </button>
            </div>
        </div>

        <div class="subjects-list-container">
            <table class="subjects-list-table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Description</th>
                        <th>Lecturer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Sample data - In a real application, this would come from a database
                $subjects = array(
                    array(
                        "id" => "MATH101",
                        "name" => "Mathematics",
                        "code" => "MATH101",
                        "description" => "Algebra, Geometry, Calculus fundamentals",
                        "credits" => 3,
                        "category" => "Core",
                        "level" => "Beginner",
                        "lecturer" => "Dr. Smith",
                        "status" => "active"
                    ),
                    array(
                        "id" => "PHYS101",
                        "name" => "Physics",
                        "code" => "PHYS101",
                        "description" => "Physics, Chemistry, Biology basics",
                        "credits" => 4,
                        "category" => "Core",
                        "level" => "Beginner",
                        "lecturer" => "Prof. Johnson",
                        "status" => "active"
                    ),
                    array(
                        "id" => "ENG101",
                        "name" => "English",
                        "code" => "ENG101",
                        "description" => "Literature, Grammar, Writing skills",
                        "credits" => 3,
                        "category" => "Core",
                        "level" => "Beginner",
                        "lecturer" => "Ms. Brown",
                        "status" => "active"
                    ),
                    array(
                        "id" => "HIST101",
                        "name" => "History",
                        "code" => "HIST101",
                        "description" => "World History, Local History studies",
                        "credits" => 3,
                        "category" => "Elective",
                        "level" => "Beginner",
                        "lecturer" => "Dr. Davis",
                        "status" => "active"
                    ),
                    array(
                        "id" => "CHEM101",
                        "name" => "Chemistry",
                        "code" => "CHEM101",
                        "description" => "General Chemistry with laboratory work",
                        "credits" => 4,
                        "category" => "Core",
                        "level" => "Intermediate",
                        "lecturer" => "Prof. Wilson",
                        "status" => "active"
                    ),
                    array(
                        "id" => "BIO101",
                        "name" => "Biology",
                        "code" => "BIO101",
                        "description" => "Biology Basics with practical sessions",
                        "credits" => 4,
                        "category" => "Core",
                        "level" => "Beginner",
                        "lecturer" => "Dr. Miller",
                        "status" => "inactive"
                    )
                );

                foreach ($subjects as $subject) {
                    echo "<tr>";
                    echo "<td>{$subject['code']}</td>";
                    echo "<td>{$subject['name']}</td>";
                    echo "<td>{$subject['description']}</td>";
                    echo "<td>{$subject['lecturer']}</td>";
                    echo "<td><span class='status-badge {$subject['status']}'>{$subject['status']}</span></td>";
                    echo "<td>";
                    echo "<span class='material-icons edit-btn' onclick='editSubject(\"{$subject['id']}\")'>edit</span> ";
                    echo "<span class='material-icons delete-btn' onclick='deleteSubject(\"{$subject['id']}\")'>delete</span> ";
                    echo "<span class='material-icons more-btn'>more_vert</span> ";
                    echo "<button class='view-details-btn'>View Details</button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
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
                <form class="modal-form">
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
                        <button type="button" class="cancel-btn" onclick="closeAddSubjectModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddSubjectModal() {
            document.getElementById('addSubjectModal').style.display = 'block';
        }

        function closeAddSubjectModal() {
            document.getElementById('addSubjectModal').style.display = 'none';
        }

        function editSubject(subjectId) {
            // Implementation for editing subject
            console.log('Edit subject:', subjectId);
        }

        function deleteSubject(subjectId) {
            if (confirm('Are you sure you want to delete this subject?')) {
                // Implementation for deleting subject
                console.log('Delete subject:', subjectId);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addSubjectModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
