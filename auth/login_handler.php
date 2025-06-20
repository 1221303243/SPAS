<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            // If using plain text password, update it to hashed version
            if ($password === $user['password']) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $hashedPassword, $user['user_id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            // Password is correct, set up session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: ../pages/admin/dashboard.php");
                    break;
                case 'student':
                    header("Location: ../pages/student/student_dashboard.php");
                    break;
                case 'lecturer':
                    header("Location: ../pages/lecturer/lecturer_dashboard.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}
?>
