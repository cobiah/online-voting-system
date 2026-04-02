<?php
/**
 * Tamper Test Generator
 * Creates intentionally tampered vote records to test the tamper detection system
 * This script should be run as admin only
 */

include 'db.php';
session_start();

// Security check - admin only
if (!isset($_SESSION['admin'])) {
    die('Unauthorized access. Admin login required.');
}

header('Location: create_tamper_test_mongo.php');
exit;
__halt_compiler();

$results = [];
$error_msg = null;

try {
    // STEP 1: Ensure we have positions
    $posCount = $conn->query("SELECT COUNT(*) as count FROM positions")->fetch_assoc()['count'];
    if ($posCount == 0) {
        throw new Exception("No positions found. Please add positions first.");
    }

    // STEP 2: Get 3 positions
    $positionsQuery = $conn->query("SELECT position_id FROM positions LIMIT 3");
    $positions = [];
    while ($row = $positionsQuery->fetch_assoc()) {
        $positions[] = $row['position_id'];
    }
    
    if (count($positions) < 3) {
        throw new Exception("Need at least 3 positions. Only found " . count($positions));
    }
    
    $position_id_1 = $positions[0];
    $position_id_2 = $positions[1];
    $position_id_3 = $positions[2];

    // STEP 3: Ensure we have 2 candidates for each position
    $candidatesData = [];
    foreach ($positions as $pos_id) {
        $candQuery = $conn->query("SELECT candidate_id FROM candidates WHERE position_id = $pos_id LIMIT 2");
        $candidates = [];
        while ($row = $candQuery->fetch_assoc()) {
            $candidates[] = $row['candidate_id'];
        }
        
        // Create additional candidates if needed
        while (count($candidates) < 2) {
            $candName = 'Test Candidate ' . uniqid();
            $gender = 'any';
            $manifesto = 'Test candidate for tamper testing';
            
            $candStmt = $conn->prepare("INSERT INTO candidates (name, position_id, gender, manifesto) VALUES (?, ?, ?, ?)");
            if (!$candStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $candStmt->bind_param("siss", $candName, $pos_id, $gender, $manifesto);
            if (!$candStmt->execute()) {
                throw new Exception("Failed to create candidate: " . $candStmt->error);
            }
            $candidates[] = $conn->insert_id;
            $candStmt->close();
        }
        
        $candidatesData[$pos_id] = $candidates;
    }
    $results[] = "✓ Verified 2+ candidates per position";

    // STEP 4: Ensure we have 3 students
    $studentCheck = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
    if ($studentCheck == 0) {
        throw new Exception("No students in database. Please add students first.");
    }

    $testStudents = [];
    $studentsQuery = $conn->query("SELECT student_id FROM students LIMIT 3");
    while ($row = $studentsQuery->fetch_assoc()) {
        $testStudents[] = $row['student_id'];
    }

    while (count($testStudents) < 3) {
        $regNo = 'TEST_' . uniqid();
        $stmt = $conn->prepare("INSERT INTO students (reg_no, full_name, password_hash, department_id) VALUES (?, ?, ?, ?)");
        $testName = 'Test Student ' . count($testStudents);
        $testHash = password_hash('test123', PASSWORD_BCRYPT);
        $deptId = 1;
        $stmt->bind_param("sssi", $regNo, $testName, $testHash, $deptId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create student: " . $stmt->error);
        }
        $testStudents[] = $conn->insert_id;
        $stmt->close();
    }

    $student_id_1 = $testStudents[0];
    $student_id_2 = $testStudents[1];
    $student_id_3 = $testStudents[2];
    
    $results[] = "✓ Ensured 3 test students";

    // STEP 5: Clean up any existing test votes for these students
    $cleanupStmt = $conn->prepare("DELETE FROM votes WHERE student_id IN (?, ?, ?)");
    $cleanupStmt->bind_param("iii", $student_id_1, $student_id_2, $student_id_3);
    $cleanupStmt->execute();
    $cleanupStmt->close();

    // Clean up orphaned integrity records
    $integrityCleanup = $conn->prepare("DELETE FROM integrity WHERE vote_id NOT IN (SELECT vote_id FROM votes)");
    $integrityCleanup->execute();
    $integrityCleanup->close();
    
    $results[] = "✓ Cleaned up existing test data";

    // TEST 1: Valid vote with correct hash
    $candidate_1a = $candidatesData[$position_id_1][0];
    
    $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id, position_id, encrypted_ballot) VALUES (?, ?, ?, ?)");
    $ballot_1 = 'ballot_' . uniqid();
    $stmt->bind_param("iiis", $student_id_1, $candidate_1a, $position_id_1, $ballot_1);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert vote 1: " . $stmt->error);
    }
    $valid_vote_id = $conn->insert_id;
    $stmt->close();

    $correct_hash = hash('sha256', "$student_id_1-$position_id_1-$candidate_1a-$valid_vote_id");

    $stmt = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash, verified) VALUES (?, ?, TRUE)");
    $stmt->bind_param("is", $valid_vote_id, $correct_hash);
    $stmt->execute();
    $stmt->close();

    $results[] = "✓ TEST 1: Created VALID vote (ID: $valid_vote_id)";
    $results[] = "  Student: $student_id_1, Position: $position_id_1, Candidate: $candidate_1a";

    // TEST 2: Tampered vote
    $candidate_2a = $candidatesData[$position_id_2][0];
    $candidate_2b = $candidatesData[$position_id_2][1];

    $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id, position_id, encrypted_ballot) VALUES (?, ?, ?, ?)");
    $ballot_2 = 'ballot_' . uniqid();
    $stmt->bind_param("iiis", $student_id_2, $candidate_2a, $position_id_2, $ballot_2);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert vote 2: " . $stmt->error);
    }
    $tampered_vote_id = $conn->insert_id;
    $stmt->close();

    $correct_tamper_hash = hash('sha256', "$student_id_2-$position_id_2-$candidate_2a-$tampered_vote_id");

    // Modify the vote
    $stmt = $conn->prepare("UPDATE votes SET candidate_id = ? WHERE vote_id = ?");
    $stmt->bind_param("ii", $candidate_2b, $tampered_vote_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash, verified) VALUES (?, ?, TRUE)");
    $stmt->bind_param("is", $tampered_vote_id, $correct_tamper_hash);
    $stmt->execute();
    $stmt->close();

    $results[] = "✓ TEST 2: Created TAMPERED vote (ID: $tampered_vote_id)";
    $results[] = "  Candidate modified: $candidate_2a → $candidate_2b (hash won't match)";

    // TEST 3: Wrong hash
    $candidate_3a = $candidatesData[$position_id_3][0];

    $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id, position_id, encrypted_ballot) VALUES (?, ?, ?, ?)");
    $ballot_3 = 'ballot_' . uniqid();
    $stmt->bind_param("iiis", $student_id_3, $candidate_3a, $position_id_3, $ballot_3);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert vote 3: " . $stmt->error);
    }
    $wrong_hash_vote_id = $conn->insert_id;
    $stmt->close();

    $wrong_hash = hash('sha256', 'fabricated_' . uniqid());

    $stmt = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash, verified) VALUES (?, ?, FALSE)");
    $stmt->bind_param("is", $wrong_hash_vote_id, $wrong_hash);
    $stmt->execute();
    $stmt->close();

    $results[] = "✓ TEST 3: Created vote with WRONG HASH (ID: $wrong_hash_vote_id)";

} catch (Exception $e) {
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
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
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
        .button-danger {
            background-color: #dc3545;
        }
        .button-danger:hover {
            background-color: #c82333;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
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
        <h1>🔍 Tamper Detection Test Generator</h1>
        
        <?php if ($error_msg === null): ?>
        <div class="info">
            <strong>✅ Test Data Created Successfully!</strong>
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
            <form method="POST" action="../backend/tamper_check.php" style="display:inline;">
                <button type="submit" class="button">Run Tamper Detection Now</button>
            </form>
        </div>

        <?php else: ?>
        <div class="error">
            <strong>❌ ERROR:</strong><br>
            <?= htmlspecialchars($error_msg) ?>
        </div>
        <div style="margin-top: 20px;">
            <a href="../frontend/admin_dashboard.php" class="button button-secondary">Back to Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
