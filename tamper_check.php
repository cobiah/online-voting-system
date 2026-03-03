<?php include "includes/header.php"; ?>
<?php
include 'db.php';

$sql = "SELECT v.vote_id, v.student_id, v.candidate_id, i.vote_hash 
        FROM votes v 
        JOIN integrity i ON v.vote_id = i.vote_id";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $expected_hash = hash("sha256", $row['student_id'] . "-" . $row['candidate_id'] . "-" . $row['vote_id']);
    if ($expected_hash !== $row['vote_hash']) {
        echo "Tampering detected on vote ID: " . $row['vote_id'] . "<br>";
    }
}
?>
<?php include "includes/footer.php"; ?>