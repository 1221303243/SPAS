<?php
// signup.php - Registration page for students/lecturers
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPAS - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .signup-container { max-width: 420px; margin: 3em auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,193,254,0.08); padding: 2.5em 2em; }
        .signup-title { font-weight: 700; color: #1F1235; margin-bottom: 1.2em; text-align: center; }
        .form-label { font-weight: 500; }
        .form-select, .form-control { border-radius: 8px; }
        .btn-signup { background: #00C1FE; color: #fff; font-weight: 600; border-radius: 24px; }
        .btn-signup:hover { background: #0090c1; }
        .login-link { display: block; text-align: center; margin-top: 1.5em; color: #00C1FE; text-decoration: none; font-weight: 500; }
        .login-link:hover { text-decoration: underline; color: #0090c1; }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2 class="signup-title">Create Your SPAS Account</h2>
        <form id="signupForm" method="post" action="signup_handler.php" autocomplete="off">
            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Register as</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-signup w-100">Sign Up</button>
        </form>
        <a href="login.php" class="login-link"><i class="bi bi-box-arrow-in-right"></i> Already have an account? Login</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simple client-side validation for password match
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        var pw = document.getElementById('password').value;
        var cpw = document.getElementById('confirm_password').value;
        if (pw !== cpw) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });
    </script>
</body>
</html> 