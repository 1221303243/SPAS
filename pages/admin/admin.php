<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SPAS - User Management</title>
    <link rel="stylesheet" href="../../css/admin.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        
        /* Additional style to ensure content is properly positioned */
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
            <button class="add-user-btn">
                Add user
                <span class="material-icons">add</span>
            </button>
        </div>

        <div class="search-filter-container">
            <div class="search-bar">
                <input type="text" placeholder="Search user" />
            </div>
            <button class="filters-btn">
                Filters
            </button>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" />
                    </th>
                    <th>ID User</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Sample data - In a real application, this would come from a database
                $users = array(
                    array("id" => "#12345", "name" => "Jhon Legend", "email" => "jogn.legend@gmail.com", "phone" => "+1234567890", "position" => "HR Manager"),
                    array("id" => "#12345", "name" => "Jhon Legend", "email" => "jogn.legend@gmail.com", "phone" => "+1234567890", "position" => "HR Manager"),
                    array("id" => "#12345", "name" => "Jhon Legend", "email" => "jogn.legend@gmail.com", "phone" => "+1234567890", "position" => "HR Manager"),
                    array("id" => "#12345", "name" => "Jhon Legend", "email" => "jogn.legend@gmail.com", "phone" => "+1234567890", "position" => "HR Manager"),
                    array("id" => "#12345", "name" => "Jhon Legend", "email" => "jogn.legend@gmail.com", "phone" => "+1234567890", "position" => "HR Manager")
                );

                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td class='checkbox-cell'><input type='checkbox' /></td>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['name']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['phone']}</td>";
                    echo "<td>{$user['position']}</td>";
                    echo "<td class='actions'>
                            <span class='material-icons'>edit</span>
                            <span class='material-icons'>delete</span>
                            <span class='material-icons'>more_vert</span>
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
