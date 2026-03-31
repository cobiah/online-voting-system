<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/add_position.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid request. Please try again.'
    ];
    header('Location: ../frontend/add_position.php');
    exit;
}

$position_id = (int)($_POST['position_id'] ?? 0);
if ($position_id <= 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid position selected.'
    ];
    header('Location: ../frontend/add_position.php');
    exit;
}

$deleted = db_delete_position($position_id);

if ($deleted > 0) {
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Position deleted successfully.'
    ];
    if (function_exists('log_action')) {
        log_action($conn, 'Position deleted: ' . $position_id, 0);
    }
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Position could not be deleted.'
    ];
}

header('Location: ../frontend/add_position.php');
exit;
