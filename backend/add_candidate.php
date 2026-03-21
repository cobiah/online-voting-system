<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];

    $stmt = $conn->prepare("INSERT INTO candidates (name, position) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $position);
    $stmt->execute();

    if (function_exists('log_action')) {
        log_action($conn, 'Candidate added: ' . $name . ' (' . $position . ')', 0);
    }
}

header('Location: ../frontend/add_candidate.php');
exit;
?>