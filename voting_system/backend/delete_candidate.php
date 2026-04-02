<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/add_candidate.php');
    exit;
}

if (!database_ready($conn)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Candidate management is temporarily unavailable because the database connection is down.'
    ];
    header('Location: ../frontend/add_candidate.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid request. Please try again.'
    ];
    header('Location: ../frontend/add_candidate.php');
    exit;
}

if (isset($_POST['candidate_id'])) {
    $id = (int) $_POST['candidate_id'];
    $deleted = db_delete_candidate($id);

    if (function_exists('log_action')) {
        log_action($conn, 'Candidate deleted (ID: ' . $id . ')', 0);
    }

    $_SESSION['flash'] = [
        'type' => $deleted > 0 ? 'success' : 'error',
        'message' => $deleted > 0 ? 'Candidate deleted successfully.' : 'Candidate could not be deleted.'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'No candidate was selected for deletion.'
    ];
}

header('Location: ../frontend/add_candidate.php');
exit;
?>
