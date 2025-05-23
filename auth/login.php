<?php
session_start();
include 'db_connection.php'; // create a db_connection.php file to connect to MySQL

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $password == $user['password']) { // (you are using plain text comparison now)
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        echo $user['role']; // <--- send role to JS
    } else {
        echo "Invalid username or password!";
    }
}
?>
