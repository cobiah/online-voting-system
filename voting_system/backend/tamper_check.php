<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

$issues = [];
$sql = "SELECT v.vote_id, v.student_id, v.candidate_id, v.position_id, i.vote_hash
        FROM votes v
        JOIN integrity i ON v.vote_id = i.vote_id";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $recalc = hash('sha256', $row['student_id'] . "-" . $row['position_id'] . "-" . $row['candidate_id'] . "-" . $row['vote_id']);
    if (!hash_equals($recalc, $row['vote_hash'])) {
        $issues[] = $row['vote_id'];
    }
}

if (function_exists('log_action')) {
    log_action($conn, 'Tamper check performed', 0);
}

include '../includes/header.php';
?>

<div class="page-center">
  <div class="card">
    <h2>Tamper Detection</h2>
    <p>The system checks vote hashes against expected values.</p>

    <?php if (empty($issues)): ?>
      <div class="alert success">
        <span class="icon">✅</span>
        <span>No tampering detected.</span>
      </div>
    <?php else: ?>
      <div class="alert error">
        <span class="icon">⚠️</span>
        <span>Tampering detected for vote IDs: <?= htmlspecialchars(implode(', ', $issues)) ?></span>
      </div>
    <?php endif; ?>

    <div style="margin-top: 16px;">
      <a href="../frontend/admin_dashboard.php" class="button button-secondary">Return to Dashboard</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>