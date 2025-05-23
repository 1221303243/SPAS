<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - Course Management</title>
    <link rel="stylesheet" href="../../css/course.css" />
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
            <h1>Courses</h1>
            <button class="add-course-btn">
                Add course
                <span class="material-icons">add</span>
            </button>
        </div>

        <div class="search-filter-container">
            <div class="search-bar">
                <input type="text" placeholder="Search course" />
            </div>
            <button class="filters-btn">
                Filters
            </button>
        </div>

        <table class="courses-table">
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" />
                    </th>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Faculty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Sample data - In a real application, this would come from a database
                $courses = array(
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI"),
                    array("code" => "12345", "name" => "Jhon Legend", "faculty" => "FCI")
                );

                foreach ($courses as $course) {
                    echo "<tr>";
                    echo "<td class='checkbox-cell'><input type='checkbox' /></td>";
                    echo "<td>{$course['code']}</td>";
                    echo "<td>{$course['name']}</td>";
                    echo "<td>{$course['faculty']}</td>";
                    echo "<td class='actions'>
                            <span class='material-icons'>edit</span>
                            <span class='material-icons'>delete</span>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="pagination">
            <div class="page-info">1 - 5 of 56</div>
            <div class="page-controls">
                <span>The page you're on</span>
                <select class="page-select">
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
                <div class="nav-buttons">
                    <button class="nav-btn">←</button>
                    <button class="nav-btn">→</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
