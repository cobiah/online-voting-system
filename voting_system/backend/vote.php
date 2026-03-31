<?php
include 'db.php';
session_start();

if (!isset($_SESSION['student_id'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please login to cast your vote.'
    ];
    header('Location: ../frontend/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Invalid request. Please try again.'
        ];
        header('Location: ../frontend/vote.php');
        exit;
    }

    $student_id = (int) $_SESSION['student_id'];
    $votes = $_POST['candidate_id'] ?? [];

    $today = date('Y-m-d');
    $currentElection = db_get_current_election();
    if (!$currentElection) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'No active election is currently available.'
        ];
        header('Location: ../frontend/dashboard.php');
        exit;
    }

    $start_date = (string) ($currentElection['start_date'] ?? '');
    $end_date = (string) ($currentElection['end_date'] ?? '');
    if ((!empty($start_date) && $today < $start_date) || (!empty($end_date) && $today > $end_date)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Voting is not open at this time.'
        ];
        header('Location: ../frontend/dashboard.php');
        exit;
    }

    if (db_count_votes_for_student($student_id) > 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'You have already cast your vote and cannot vote again.'
        ];
        header('Location: ../frontend/dashboard.php');
        exit;
    }

    $student = db_get_student_by_id($student_id) ?? [];
    if ((int) ($student['is_locked'] ?? 0) === 1) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Your account has been locked. Contact an administrator for help.'
        ];
        header('Location: ../frontend/dashboard.php');
        exit;
    }

    if (!is_array($votes) || empty($votes)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Please select at least one candidate before voting.'
        ];
        header('Location: ../frontend/vote.php');
        exit;
    }

    $errors = [];
    $recordedVotes = [];

    foreach ($votes as $positionId => $candidateId) {
        $positionId = (int) $positionId;
        $candidateId = (int) $candidateId;

        if ($candidateId <= 0 || $positionId <= 0) {
            continue;
        }

        if (!db_find_candidate_for_position($candidateId, $positionId)) {
            $errors[] = 'Invalid choice for position ID ' . $positionId;
            continue;
        }

        if (db_find_vote_for_student_position($student_id, $positionId)) {
            $errors[] = 'You have already voted for this position.';
            continue;
        }

        $recordedVotes[] = [
            'position_id' => $positionId,
            'candidate_id' => $candidateId,
        ];
    }

    if (empty($recordedVotes)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'No valid votes could be recorded. ' . implode(' ', $errors)
        ];
        header('Location: ../frontend/vote.php');
        exit;
    }

    $encryptedBallot = encrypt_vote_payload(json_encode($recordedVotes));
    $successCount = 0;

    foreach ($recordedVotes as $voteData) {
        $candidateId = $voteData['candidate_id'];
        $positionId = $voteData['position_id'];
        $vote_id = db_create_vote($student_id, $candidateId, $positionId, $encryptedBallot);
        $vote_hash = hash('sha256', $student_id . '-' . $positionId . '-' . $candidateId . '-' . $vote_id);
        db_create_integrity($vote_id, $vote_hash);
        $successCount++;
    }

    if ($successCount > 0 && function_exists('log_action')) {
        log_action($conn, 'Vote cast (' . $successCount . ' positions)', $student_id);
    }

    if ($successCount === 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'No votes were recorded. ' . implode(' ', $errors)
        ];
    } else {
        $message = 'Vote successfully cast';
        if (!empty($errors)) {
            $message .= '. Some positions were skipped: ' . implode(' ', $errors);
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $message
        ];
    }

    header('Location: ../frontend/dashboard.php');
    exit;
}
?>
