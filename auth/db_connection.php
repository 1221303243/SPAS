<?php
// Database configuration

$servername = "localhost";  // Because it's local using XAMPP/Laragon/etc
$username = "root";          // Default username in phpMyAdmin
$password = "";              // Default password is usually empty
$database = "SPAS";          // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
