<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

include '../backend/db.php';

$totalVoters = $conn->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'] ?? 0;
$votesCast = $conn->query('SELECT COUNT(*) AS total FROM votes')->fetch_assoc()['total'] ?? 0;

?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>Admin Dashboard</h2>

    <div class="stats-grid">
      <div class="stat-card">
        <p>Total Voters</p>
        <div class="stat-value"><?= (int)$totalVoters ?></div>
      </div>
      <div class="stat-card">
        <p>Votes Cast</p>
        <div class="stat-value"><?= (int)$votesCast ?></div>
      </div>
      <div class="stat-card">
        <p>Election Status</p>
        <div class="status-pill status-active">ACTIVE</div>
      </div>
    </div>

    <div style="margin-top: 24px; display: grid; gap: 12px;">
      <a href="add_candidate.php" class="button button-primary">Add Candidate</a>
      <a href="results.php" class="button button-secondary">View Results</a>
      <a href="view_audit.php" class="button button-secondary">Audit Logs</a>
      <a href="../backend/tamper_check.php" class="button button-secondary">Tamper Check</a>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>