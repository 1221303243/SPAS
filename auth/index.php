<?php
session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../pages/admin/admin.php");
        exit();
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: ../pages/student/student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'lecturer') {
        header("Location: ../pages/lecturer/lecturer_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SPAS - Login</title>
    <link rel="stylesheet" href="index.css" />
    <script src="index.js" defer></script>
</head>

<body>
    <div class="page-container">
        <div class="login-box">
            <h2>Login to SPAS</h2>
            <form id="loginForm">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit">Login</button>
                <p id="error-message"></p>
            </form>
        </div>
    </div>
</body>

</html>