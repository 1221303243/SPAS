<?php
session_start();
require_once 'db_connection.php';

// Initialize arrays for errors and form data
$errors = [];
$_SESSION['signup_data'] = $_POST;

// Validate input
if (empty($_POST['fullname'])) {
    $errors[] = "Full name is required";
} elseif (strlen($_POST['fullname']) < 2) {
    $errors[] = "Full name must be at least 2 characters long";
}

if (empty($_POST['email'])) {
    $errors[] = "Email is required";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address (e.g., user@example.com)";
} elseif (!str_ends_with(strtolower($_POST['email']), '.edu.my')) {
    $errors[] = "Students must use a Malaysian educational institution email address ending with .edu.my";
}

// Password validation
if (empty($_POST['password'])) {
    $errors[] = "Password is required";
} else {
    $password = $_POST['password'];
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
}

if ($_POST['password'] !== $_POST['confirm_password']) {
    $errors[] = "Passwords do not match";
}

// Role is always 'student' from the form
if (empty($_POST['role']) || $_POST['role'] !== 'student') {
    $errors[] = "Invalid registration request";
}

if (empty($_POST['edu_level'])) {
    $errors[] = "Please select your education level";
}

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $_POST['email']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $errors[] = "This email address is already registered. Please use a different email or login to your existing account.";
}
$stmt->close();

// If there are errors, redirect back to signup page
if (!empty($errors)) {
    $_SESSION['signup_errors'] = $errors;
    header("Location: signup.php");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $_POST['fullname'], $_POST['email'], $hashedPassword, $_POST['role']);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: Failed to create user account. " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to create user account. No rows affected.");
    }
    
    $userId = $conn->insert_id;
    $stmt->close();

    // Insert into students table
    $stmt = $conn->prepare("INSERT INTO students (user_id, name, edu_level) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $_POST['fullname'], $_POST['edu_level']);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: Failed to create profile. " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to create profile. No rows affected.");
    }
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Set success message and clear form data
    $_SESSION['signup_success'] = "Registration successful! You can now login with your email and password.";
    unset($_SESSION['signup_data']);
    
    header("Location: login.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Provide more specific error message based on the exception
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, "Duplicate entry") !== false) {
        $_SESSION['signup_errors'] = ["This email address is already registered. Please use a different email."];
    } else {
        $_SESSION['signup_errors'] = ["Registration failed: " . $errorMessage];
    }
    
    header("Location: signup.php");
    exit();
}
?> 