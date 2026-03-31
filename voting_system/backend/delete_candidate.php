<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {
    $id = (int)$_POST['candidate_id'];
    $stmt = $conn->prepare('DELETE FROM candidates WHERE candidate_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if (function_exists('log_action')) {
        log_action($conn, 'Candidate deleted (ID: ' . $id . ')', 0);
    }
}

header('Location: ../frontend/add_candidate.php');
exit;
