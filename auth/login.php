<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../pages/admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: ../pages/student/student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'lecturer') {
        header("Location: ../pages/lecturer/select_edu_level.php");
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
    <style>
    input[type="password"], input[type="text"]#password {
        letter-spacing: 0.1em;
        font-family: inherit;
        font-size: 1em;
        padding: 8px 36px 8px 8px;
        border: 1px solid #bfc5ca;
        border-radius: 6px;
        width: 100%;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    input[type="password"]:focus, input[type="text"]#password:focus {
        border-color: #00C1FE;
        outline: none;
    }
    #togglePassword {
        position: absolute;
        right: 8px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        height: 100%;
    }
    #togglePasswordIcon {
        font-size: 1.2em;
        color: #7a7a7a;
    }
    </style>
</head>

<body>
    <div class="page-container">
        <div class="login-box">
            <h2>Login to SPAS</h2>
            <form id="loginForm">
                <div class="input-group">
                    <label for="email">Email</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="email" name="email" id="email" required style="flex:1;">
                    </div>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" name="password" id="password" required style="flex:1;">
                        <button type="button" id="togglePassword" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer;">
                            <span id="togglePasswordIcon" style="font-size: 1.2em;">👁️</span>
                        </button>
                    </div>
                </div>
                <button type="submit">Login</button>
                <p id="error-message"></p>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                toggleIcon.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
    });
    </script>
</body>

</html>