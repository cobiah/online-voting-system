<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/voter_management.php');
    exit;
}

$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$action = $_POST['action'] ?? '';
$action = trim(strtolower($action));

if ($action !== 'clear_all' && $studentId <= 0) {
    header('Location: ../frontend/voter_management.php');
    exit;
}

switch ($action) {
    case 'clear_all':
        $stmt = $conn->prepare('DELETE FROM votes');
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        } else {
            $conn->query('DELETE FROM votes');
        }
        log_action($conn, 'Cleared all voting history', 0);
        header('Location: ../frontend/voter_management.php');
        exit;
    case 'lock':
        $stmt = $conn->prepare('UPDATE students SET is_locked = 1 WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        log_action($conn, 'Student account locked (ID: ' . $studentId . ')', 0);
        break;
    case 'unlock':
        $stmt = $conn->prepare('UPDATE students SET is_locked = 0 WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        log_action($conn, 'Student account unlocked (ID: ' . $studentId . ')', 0);
        break;
    case 'reset':
        $stmt = $conn->prepare('DELETE FROM votes WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        log_action($conn, 'Reset votes for student (ID: ' . $studentId . ')', 0);
        break;
}

header('Location: ../frontend/voter_management.php');
exit;
