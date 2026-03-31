<?php
session_start();
include 'db.php';

$userId = $_SESSION['student_id'] ?? 0;

if (function_exists('log_action') && $userId) {
    log_action($conn, 'User logged out', $userId);
}

session_destroy();
header("Location: ../frontend/login.php");
exit;
?>