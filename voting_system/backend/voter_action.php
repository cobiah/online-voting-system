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

if (!database_ready($conn)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Voter management is temporarily unavailable because the database connection is down.'
    ];
    header('Location: ../frontend/voter_management.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid request. Please try again.'
    ];
    header('Location: ../frontend/voter_management.php');
    exit;
}

$studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
$action = trim(strtolower((string) ($_POST['action'] ?? '')));

if ($action !== 'clear_all' && $studentId <= 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please select a valid voter.'
    ];
    header('Location: ../frontend/voter_management.php');
    exit;
}

switch ($action) {
    case 'clear_all':
        $deletedVotes = db_clear_all_votes();
        log_action($conn, 'Cleared all voting history', 0);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Voting history cleared for ' . $deletedVotes . ' vote(s).'
        ];
        header('Location: ../frontend/voter_management.php');
        exit;

    case 'lock':
        db_set_student_lock($studentId, true);
        log_action($conn, 'Student account locked (ID: ' . $studentId . ')', 0);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Student account locked successfully.'
        ];
        break;

    case 'unlock':
        db_set_student_lock($studentId, false);
        log_action($conn, 'Student account unlocked (ID: ' . $studentId . ')', 0);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Student account unlocked successfully.'
        ];
        break;

    case 'reset':
        $deletedVotes = db_reset_student_votes($studentId);
        log_action($conn, 'Reset votes for student (ID: ' . $studentId . ')', 0);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Reset ' . $deletedVotes . ' vote(s) for the selected student.'
        ];
        break;

    default:
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Invalid voter action.'
        ];
        break;
}

header('Location: ../frontend/voter_management.php');
exit;
?>
