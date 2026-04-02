<?php
/**
 * Tamper Test Generator
 * Creates intentionally tampered vote records to test the tamper detection system.
 */

include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    die('Unauthorized access. Admin login required.');
}

$results = [];
$error_msg = null;

try {
    if (!database_ready($conn)) {
        throw new RuntimeException('Database connection is unavailable.');
    }

    $positions = array_slice(db_get_positions(), 0, 3);
    if (count($positions) < 3) {
        throw new RuntimeException('Need at least 3 positions. Please add more positions first.');
    }

    $departments = db_get_departments();
    if ($departments === []) {
        throw new RuntimeException('No departments found. Default seed data is missing.');
    }

    $defaultDepartment = $departments[0];
    $defaultDepartmentId = (int) $defaultDepartment['department_id'];
    $defaultDepartmentName = (string) $defaultDepartment['name'];

    $candidatesData = [];
    foreach ($positions as $position) {
        $positionId = (int) $position['position_id'];
        $candidateIds = [];

        foreach (db_find_many('candidates', ['position_id' => $positionId], ['sort' => ['candidate_id' => 1], 'limit' => 2]) as $row) {
            $candidateIds[] = (int) ($row['candidate_id'] ?? 0);
        }

        while (count($candidateIds) < 2) {
            $candidateName = 'Tamper Test Candidate ' . $positionId . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $candidateIds[] = db_create_candidate(
                null,
                $candidateName,
                $positionId,
                $defaultDepartmentId,
                $defaultDepartmentName,
                'any',
                'Auto-generated candidate for tamper detection testing.',
                ''
            );
        }

        $candidatesData[$positionId] = $candidateIds;
    }
    $results[] = 'Verified at least 2 candidates for each test position.';

    $testStudents = [];
    foreach (db_find_many('students', [], ['sort' => ['student_id' => 1], 'limit' => 3]) as $row) {
        $testStudents[] = (int) ($row['student_id'] ?? 0);
    }
    $testStudents = array_values(array_filter($testStudents, static fn(int $id): bool => $id > 0));

    while (count($testStudents) < 3) {
        $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $testStudents[] = db_create_student(
            'TEST_' . $suffix,
            'Tamper Test Student ' . (count($testStudents) + 1),
            'tamper_' . strtolower($suffix) . '@example.test',
            password_hash('test12345', PASSWORD_BCRYPT),
            $defaultDepartmentId,
            $defaultDepartmentName
        );
    }

    [$student_id_1, $student_id_2, $student_id_3] = $testStudents;
    $results[] = 'Ensured 3 test students are available.';

    $deletedVotes = db_delete_votes_by_filter(['student_id' => ['$in' => $testStudents]]);

    $existingVoteIds = [];
    foreach (db_find_many('votes', [], ['projection' => ['vote_id' => 1]]) as $vote) {
        $voteId = (int) ($vote['vote_id'] ?? 0);
        if ($voteId > 0) {
            $existingVoteIds[$voteId] = true;
        }
    }

    $orphanVoteIds = [];
    foreach (db_find_many('integrity', [], ['projection' => ['vote_id' => 1]]) as $integrityRow) {
        $voteId = (int) ($integrityRow['vote_id'] ?? 0);
        if ($voteId > 0 && !isset($existingVoteIds[$voteId])) {
            $orphanVoteIds[$voteId] = $voteId;
        }
    }

    if ($orphanVoteIds !== []) {
        db_delete_many('integrity', ['vote_id' => ['$in' => array_values($orphanVoteIds)]]);
    }

    $results[] = 'Cleared ' . $deletedVotes . ' existing test vote(s) and removed orphan integrity records.';

    $position_id_1 = (int) $positions[0]['position_id'];
    $position_id_2 = (int) $positions[1]['position_id'];
    $position_id_3 = (int) $positions[2]['position_id'];

    $candidate_1a = $candidatesData[$position_id_1][0];
    $ballot_1 = encrypt_vote_payload(json_encode([
        [
            'position_id' => $position_id_1,
            'candidate_id' => $candidate_1a,
        ],
    ]));
    $valid_vote_id = db_create_vote($student_id_1, $candidate_1a, $position_id_1, $ballot_1);
    $correct_hash = hash('sha256', $student_id_1 . '-' . $position_id_1 . '-' . $candidate_1a . '-' . $valid_vote_id);
    db_create_integrity($valid_vote_id, $correct_hash, true);
    $results[] = 'Test 1: Created valid vote ID ' . $valid_vote_id . ' for student ' . $student_id_1 . '.';

    $candidate_2a = $candidatesData[$position_id_2][0];
    $candidate_2b = $candidatesData[$position_id_2][1];
    $ballot_2 = encrypt_vote_payload(json_encode([
        [
            'position_id' => $position_id_2,
            'candidate_id' => $candidate_2a,
        ],
    ]));
    $tampered_vote_id = db_create_vote($student_id_2, $candidate_2a, $position_id_2, $ballot_2);
    $correct_tamper_hash = hash('sha256', $student_id_2 . '-' . $position_id_2 . '-' . $candidate_2a . '-' . $tampered_vote_id);
    db_update_one('votes', ['vote_id' => $tampered_vote_id], ['$set' => ['candidate_id' => $candidate_2b]]);
    db_create_integrity($tampered_vote_id, $correct_tamper_hash, true);
    $results[] = 'Test 2: Created tampered vote ID ' . $tampered_vote_id . ' by changing candidate from ' . $candidate_2a . ' to ' . $candidate_2b . ' after hashing.';

    $candidate_3a = $candidatesData[$position_id_3][0];
    $ballot_3 = encrypt_vote_payload(json_encode([
        [
            'position_id' => $position_id_3,
            'candidate_id' => $candidate_3a,
        ],
    ]));
    $wrong_hash_vote_id = db_create_vote($student_id_3, $candidate_3a, $position_id_3, $ballot_3);
    $wrong_hash = hash('sha256', 'fabricated_' . bin2hex(random_bytes(6)));
    db_create_integrity($wrong_hash_vote_id, $wrong_hash, false);
    $results[] = 'Test 3: Created vote ID ' . $wrong_hash_vote_id . ' with an intentionally wrong hash.';

    if (function_exists('log_action')) {
        log_action($conn, 'Tamper test data created', 0);
    }
} catch (Throwable $e) {
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tamper Test Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .info {
            background-color: #cfe2ff;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px 10px 0;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .button-secondary {
            background-color: #6c757d;
        }
        .button-secondary:hover {
            background-color: #545b62;
        }
        .test-details {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tamper Detection Test Generator</h1>

        <?php if ($error_msg === null): ?>
        <div class="info">
            <strong>Test data created successfully.</strong>
        </div>

        <?php foreach ($results as $result): ?>
            <div class="test-details">
                <?= htmlspecialchars($result) ?>
            </div>
        <?php endforeach; ?>

        <div class="info" style="margin-top: 30px;">
            <strong>Next Steps:</strong><br>
            Click "Run Tamper Detection" to verify the test behavior.
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../frontend/admin_dashboard.php" class="button button-secondary">Back to Dashboard</a>
            <form method="post" action="../backend/tamper_check.php" style="display:inline;">
                <button type="submit" class="button">Run Tamper Detection Now</button>
            </form>
        </div>

        <?php else: ?>
        <div class="error">
            <strong>ERROR:</strong><br>
            <?= htmlspecialchars($error_msg) ?>
        </div>
        <div style="margin-top: 20px;">
            <a href="../frontend/admin_dashboard.php" class="button button-secondary">Back to Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
