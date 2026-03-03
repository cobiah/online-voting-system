
<?php
include 'db.php';
session_start();

if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $candidate_id = $_POST['candidate_id'];

    // Prevent duplicate voting
    $check = $conn->prepare("SELECT * FROM votes WHERE student_id=?");
    $check->bind_param("i", $student_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "You have already voted!";
        exit;
    }

    // Insert vote
    $stmt = $conn->prepare("INSERT INTO votes (student_id, candidate_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $student_id, $candidate_id);
    $stmt->execute();
    $vote_id = $stmt->insert_id;

    // Generate SHA-256 hash
    $vote_data = $student_id . "-" . $candidate_id . "-" . $vote_id;
    $vote_hash = hash("sha256", $vote_data);

    $stmt2 = $conn->prepare("INSERT INTO integrity (vote_id, vote_hash) VALUES (?, ?)");
    $stmt2->bind_param("is", $vote_id, $vote_hash);
    $stmt2->execute();

    // Log action
    $log = $conn->prepare("INSERT INTO audit_log (action, user_id) VALUES ('Vote Cast', ?)");
    $log->bind_param("i", $student_id);
    $log->execute();

    echo "Vote successfully cast!";
}
?>
<?php include "includes/footer.php"; ?>