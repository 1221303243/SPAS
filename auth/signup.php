<?php
// signup.php - Registration page for students/lecturers
session_start();
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
        #eduLevelGroup { display: none; }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2 class="signup-title">Create Your SPAS Account</h2>
        
        <?php if (isset($_SESSION['signup_errors'])): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['signup_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['signup_errors']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['signup_success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_SESSION['signup_success']); ?>
            </div>
            <?php unset($_SESSION['signup_success']); ?>
        <?php endif; ?>
        
        <form id="signupForm" method="post" action="signup_handler.php" autocomplete="off">
            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" 
                       value="<?php echo htmlspecialchars($_SESSION['signup_data']['fullname'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_SESSION['signup_data']['email'] ?? ''); ?>" required>
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
                    <option value="student" <?php echo ($_SESSION['signup_data']['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="lecturer" <?php echo ($_SESSION['signup_data']['role'] ?? '') === 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                </select>
            </div>
            <div class="mb-3" id="eduLevelGroup">
                <label for="edu_level" class="form-label">Education Level</label>
                <select class="form-select" id="edu_level" name="edu_level">
                    <option value="">Select Education Level</option>
                    <option value="Foundation" <?php echo ($_SESSION['signup_data']['edu_level'] ?? '') === 'Foundation' ? 'selected' : ''; ?>>Foundation</option>
                    <option value="Diploma" <?php echo ($_SESSION['signup_data']['edu_level'] ?? '') === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                    <option value="Degree" <?php echo ($_SESSION['signup_data']['edu_level'] ?? '') === 'Degree' ? 'selected' : ''; ?>>Degree</option>
                </select>
            </div>
            <button type="submit" class="btn btn-signup w-100">Sign Up</button>
        </form>
        <a href="login.php" class="login-link"><i class="bi bi-box-arrow-in-right"></i> Already have an account? Login</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show/hide education level based on role selection
    document.getElementById('role').addEventListener('change', function() {
        var eduLevelGroup = document.getElementById('eduLevelGroup');
        var eduLevel = document.getElementById('edu_level');
        
        if (this.value === 'student') {
            eduLevelGroup.style.display = 'block';
            eduLevel.required = true;
        } else {
            eduLevelGroup.style.display = 'none';
            eduLevel.required = false;
            eduLevel.value = '';
        }
    });

    // Trigger change event on page load if role is pre-selected
    window.addEventListener('load', function() {
        var roleSelect = document.getElementById('role');
        if (roleSelect.value === 'student') {
            roleSelect.dispatchEvent(new Event('change'));
        }
    });

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