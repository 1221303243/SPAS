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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .login-container { max-width: 420px; margin: 3em auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,193,254,0.08); padding: 2.5em 2em; }
        .login-title { font-weight: 700; color: #1F1235; margin-bottom: 1.2em; text-align: center; }
        .form-label { font-weight: 500; }
        .form-control { border-radius: 8px; }
        .password-field { position: relative; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #6c757d; cursor: pointer; }
        .btn-login { background: #00C1FE; color: #fff; font-weight: 600; border-radius: 24px; }
        .btn-login:hover { background: #0090c1; }
        .signup-link { display: block; text-align: center; margin-top: 1.5em; color: #00C1FE; text-decoration: none; font-weight: 500; }
        .signup-link:hover { text-decoration: underline; color: #0090c1; }
    </style>
</head>

<body>
    <div class="login-container">
        <h2 class="login-title">Welcome Back to SPAS</h2>
        
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_SESSION['login_error']); ?>
            </div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['signup_success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_SESSION['signup_success']); ?>
            </div>
            <?php unset($_SESSION['signup_success']); ?>
        <?php endif; ?>
        
        <form method="post" action="login_handler.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>
        <a href="signup.php" class="signup-link"><i class="bi bi-person-plus"></i> Don't have an account? Sign Up</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.querySelector('.password-toggle i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.classList.remove('bi-eye');
            toggleButton.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleButton.classList.remove('bi-eye-slash');
            toggleButton.classList.add('bi-eye');
        }
    }
    </script>
</body>

</html>