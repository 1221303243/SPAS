<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../../css/sidebar.css" />
    <style>
        /* This ensures the sidebar doesn't affect document flow */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 318px;
            height: 100vh;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="div">
            <div class="navigation">
                <div class="user-list">
                    <img class="vector" src="../../img/vector.png" />
                    <div class="text-wrapper">User List</div>
                </div>
                <div class="all-courses">
                    <img class="img" src="../../img/vector-1.png" />
                    <div class="text-wrapper-2">All Course</div>
                </div>
            </div>
            <div class="brand">
                <!-- Added SPAS text here -->
                <div class="text-wrapper-spas">SPAS</div>
            </div>
            <div class="user">
                <div class="frame">
                    <img class="profile-pic" src="../../img/Profile Pic.png" />
                    <div class="group">
                        <div class="text-wrapper-3">John Doe</div>
                    </div>
                </div>
                <img class="vector-2" src="../../img/setting_white.png" />
            </div>
        </div>
    </div>
</body>

</html>