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

$student_id = (int)$_SESSION['student_id'];
$votes = $_POST['candidate_id'] ?? [];

if (!is_array($votes) || empty($votes)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please select at least one candidate before voting.'
    ];
    header('Location: ../frontend/vote.php');
    exit;
}

$errors = [];
$successCount = 0;

foreach ($votes as $position => $candidateId) {
    $position = trim($position);
    $candidateId = (int)$candidateId;

    if ($candidateId <= 0 || $position === '') {
        continue;
    }

    // Ensure the selected candidate exists and matches the expected position
    $checkCandidate = $conn->prepare("SELECT candidate_id, position FROM candidates WHERE candidate_id = ? AND position = ?");
    $checkCandidate->bind_param("is", $candidateId, $position);
    $checkCandidate->execute();
    $checkCandidate->bind_result($foundCandidateId, $foundPosition);
    $checkCandidate->fetch();
    $checkCandidate->close();

    if (!$foundCandidateId) {
        $errors[] = "Invalid selection for position: " . htmlspecialchars($position);
        continue;
    }

    // Prevent double voting per position
    $check = $conn->prepare("SELECT vote_id FROM votes WHERE student_id = ? AND position = ?");
    $check->bind_param("is", $student_id, $position);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "You have already voted for {$position}.";
        continue;
    }

    $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id, position) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $student_id, $candidateId, $position);
    $stmt->execute();
    $vote_id = $stmt->insert_id;

    $vote_data = $student_id . "-" . $position . "-" . $candidateId . "-" . $vote_id;
    $vote_hash = hash("sha256", $vote_data);

    $stmt2 = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash) VALUES (?, ?)");
    $stmt2->bind_param("is", $vote_id, $vote_hash);
    $stmt2->execute();

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
    $message = "Vote successfully cast";
    if (!empty($errors)) {
        $message .= ". Some positions were skipped: " . implode(' ', $errors);
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => $message
    ];
}

header('Location: ../frontend/dashboard.php');
exit;
$stmt2 = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash) VALUES (?, ?)");
$stmt2->bind_param("is", $vote_id, $vote_hash);
$stmt2->execute();

if (function_exists('log_action')) {
    log_action($conn, 'Vote cast', $student_id);
}

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Vote successfully cast. Thank you for participating!'
];

header('Location: ../frontend/dashboard.php');
exit;
?>