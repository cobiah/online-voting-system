<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/add_election.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid request. Please try again.'
    ];
    header('Location: ../frontend/add_election.php');
    exit;
}

$election_id = (int)($_POST['election_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

$allowedActions = ['start', 'stop', 'start_all', 'stop_all'];
if (!in_array($action, $allowedActions, true) || (($action !== 'start_all' && $action !== 'stop_all') && $election_id <= 0)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid election action.'
    ];
    header('Location: ../frontend/add_election.php');
    exit;
}

if ($action === 'start') {
    $election = db_get_election_by_id($election_id);
    $existingStart = (string) ($election['start_date'] ?? '');
    $durationHours = (int) ($election['duration_hours'] ?? 0);

    $today = date('Y-m-d');
    $startDate = ($existingStart !== null && $existingStart !== '') ? $existingStart : $today;
    $endDate = null;
    if ($durationHours > 0) {
        $endDate = date('Y-m-d', strtotime($startDate . ' + ' . (int)$durationHours . ' hours'));
    }

    if ($endDate !== null) {
        $updated = db_update_election_state($election_id, ['is_active' => 1, 'start_date' => $startDate, 'end_date' => $endDate]);
    } else {
        $updated = db_update_election_state($election_id, ['is_active' => 1, 'start_date' => $startDate]);
    }

    if ($updated >= 0) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Voting started successfully.'
        ];
        if (function_exists('log_action')) {
            log_action($conn, 'Voting started for election_id: ' . $election_id, 0);
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to start voting for this election.'
        ];
    }
} elseif ($action === 'stop') {
    $updated = db_update_election_state($election_id, ['is_active' => 0]);

    if ($updated >= 0) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Voting stopped successfully.'
        ];
        if (function_exists('log_action')) {
            log_action($conn, 'Voting stopped for election_id: ' . $election_id, 0);
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to stop voting for this election.'
        ];
    }
} elseif ($action === 'start_all') {
    $today = date('Y-m-d');
    db_start_all_elections($today);

    if (true) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Voting started for all departments.'
        ];
        if (function_exists('log_action')) {
            log_action($conn, 'Voting started for all elections', 0);
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to start voting for all departments.'
        ];
    }
} elseif ($action === 'stop_all') {
    db_stop_all_elections();

    if (true) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Voting stopped for all departments.'
        ];
        if (function_exists('log_action')) {
            log_action($conn, 'Voting stopped for all elections', 0);
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to stop voting for all departments.'
        ];
    }
}

header('Location: ../frontend/add_election.php');
exit;
