<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: ../../auth/login.php');
    exit();
}
if (isset($_GET['edu_level'])) {
    $_SESSION['edu_level'] = $_GET['edu_level'];
}
header('Location: lecturer_dashboard.php');
exit(); 