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

    $student_id = (int)$_SESSION['student_id'];
    $votes = $_POST['candidate_id'] ?? [];

    $today = date('Y-m-d');
    $electionStmt = $conn->prepare("SELECT election_id, start_date, end_date FROM elections WHERE is_active = 1 LIMIT 1");
    if ($electionStmt) {
        $electionStmt->execute();
        $electionStmt->bind_result($election_id, $start_date, $end_date);
        if ($electionStmt->fetch()) {
            if ((!empty($start_date) && $today < $start_date) || (!empty($end_date) && $today > $end_date)) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'message' => 'Voting is not open at this time.'
                ];
                header('Location: ../frontend/dashboard.php');
                exit;
            }
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'No active election is currently available.'
            ];
            header('Location: ../frontend/dashboard.php');
            exit;
        }
        $electionStmt->close();
    }

    $voteCountStmt = $conn->prepare('SELECT COUNT(*) FROM votes WHERE student_id = ?');
    $voteCountStmt->bind_param('i', $student_id);
    $voteCountStmt->execute();
    $voteCountStmt->bind_result($existingVoteCount);
    $voteCountStmt->fetch();
    $voteCountStmt->close();

    if ($existingVoteCount > 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'You have already cast your vote and cannot vote again.'
        ];
        header('Location: ../frontend/dashboard.php');
        exit;
    }

    $isLocked = 0;
    try {
        $colStmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = ?");
        $colStmt->bind_param('s', $fieldName);
        $fieldName = 'is_locked';
        $colStmt->execute();
        $colStmt->bind_result($colCount);
        $colStmt->fetch();
        $colStmt->close();

        if ($colCount > 0) {
            $lockCheck = $conn->prepare('SELECT is_locked FROM students WHERE student_id = ?');
            $lockCheck->bind_param('i', $student_id);
            $lockCheck->execute();
            $lockCheck->bind_result($isLocked);
            $lockCheck->fetch();
            $lockCheck->close();
        }
    } catch (mysqli_sql_exception $e) {
        $isLocked = 0;
    }

    if ($isLocked) {
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
    $successCount = 0;

    foreach ($votes as $positionId => $candidateId) {
        $positionId = (int)$positionId;
        $candidateId = (int)$candidateId;

        if ($candidateId <= 0 || $positionId <= 0) {
            continue;
        }

        $checkCandidate = $conn->prepare("SELECT candidate_id FROM candidates WHERE candidate_id = ? AND position_id = ?");
        $checkCandidate->bind_param("ii", $candidateId, $positionId);
        $checkCandidate->execute();
        $checkCandidate->store_result();

        if ($checkCandidate->num_rows === 0) {
            $errors[] = "Invalid choice for position ID " . $positionId;
            $checkCandidate->close();
            continue;
        }
        $checkCandidate->close();

        $check = $conn->prepare("SELECT vote_id FROM votes WHERE student_id = ? AND position_id = ?");
        $check->bind_param("ii", $student_id, $positionId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "You have already voted for this position.";
            $check->close();
            continue;
        }
        $check->close();

        $recordedVotes[] = [
            'position_id' => $positionId,
            'candidate_id' => $candidateId
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

    foreach ($recordedVotes as $voteData) {
        $candidateId = $voteData['candidate_id'];
        $positionId = $voteData['position_id'];

        $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id, position_id, encrypted_ballot) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $student_id, $candidateId, $positionId, $encryptedBallot);
        $stmt->execute();

        $vote_id = $stmt->insert_id;
        $vote_hash = hash("sha256", $student_id . "-" . $positionId . "-" . $candidateId . "-" . $vote_id);

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