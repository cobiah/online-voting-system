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
        header('Location: ../frontend/add_election.php');
        exit;
    }

    $title = trim($_POST['title'] ?? 'General Election');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $duration_hours = isset($_POST['duration_hours']) ? (int)$_POST['duration_hours'] : 0;
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

    if ($title === '') {
        $title = 'General Election';
    }

    if ($start_date === '') {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Start date is required.'
        ];
        header('Location: ../frontend/add_election.php');
        exit;
    }

    if ($duration_hours <= 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Duration must be at least 1 hour.'
        ];
        header('Location: ../frontend/add_election.php');
        exit;
    }

    $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $duration_hours . ' hours'));

    try {
        db_create_election($title, $description, $start_date, $end_date, $duration_hours, $is_active);
        if (function_exists('log_action')) {
            log_action($conn, 'Election added: ' . $title, 0);
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Election added successfully.'
        ];
    } catch (Throwable $e) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to add election. Please try again.'
        ];
    }
}

header('Location: ../frontend/add_election.php');
exit;
