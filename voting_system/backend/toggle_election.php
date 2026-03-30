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

if ($election_id <= 0 || !in_array($action, ['start', 'stop'], true)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid election action.'
    ];
    header('Location: ../frontend/add_election.php');
    exit;
}

if ($action === 'start') {
    $stmt = $conn->prepare('SELECT start_date, duration_hours FROM elections WHERE election_id = ? LIMIT 1');
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $stmt->bind_result($existingStart, $durationHours);
    $stmt->fetch();
    $stmt->close();

    $today = date('Y-m-d');
    $startDate = ($existingStart !== null && $existingStart !== '') ? $existingStart : $today;
    $endDate = null;
    if ($durationHours > 0) {
        $endDate = date('Y-m-d', strtotime($startDate . ' + ' . (int)$durationHours . ' hours'));
    }

    if ($endDate !== null) {
        $stmt = $conn->prepare('UPDATE elections SET is_active = 1, start_date = ?, end_date = ? WHERE election_id = ?');
        $stmt->bind_param('ssi', $startDate, $endDate, $election_id);
    } else {
        $stmt = $conn->prepare('UPDATE elections SET is_active = 1, start_date = ? WHERE election_id = ?');
        $stmt->bind_param('si', $startDate, $election_id);
    }
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
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
} else {
    $stmt = $conn->prepare('UPDATE elections SET is_active = 0 WHERE election_id = ?');
    $stmt->bind_param('i', $election_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
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
}

header('Location: ../frontend/add_election.php');
exit;
