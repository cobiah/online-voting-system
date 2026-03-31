<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Invalid request. Please try again.'
        ];
        header('Location: ../frontend/add_position.php');
        exit;
    }

    $position_name = trim($_POST['position_name']);
    $description = trim($_POST['description']);

    if (empty($position_name)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Position name is required.'
        ];
        header('Location: ../frontend/add_position.php');
        exit;
    }

    // Check if position already exists
    if (db_find_position_by_name($position_name)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Position already exists.'
        ];
        header('Location: ../frontend/add_position.php');
        exit;
    }

    db_create_position($position_name, $description);

    if (function_exists('log_action')) {
        log_action($conn, 'Position added: ' . $position_name, 0);
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Position added successfully.'
    ];
}

header('Location: ../frontend/add_position.php');
exit;
?>
