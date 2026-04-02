<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
// Prevent students from accessing admin dashboard
if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

include '../backend/db.php';

$totalVoters = db_count_students();
$votesCast = db_count_votes();

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
      <a href="add_position.php" class="button button-primary">Add Election Position</a>
      <a href="add_candidate.php" class="button button-primary">Add Candidate</a>
      <a href="add_election.php" class="button button-primary">Manage Elections</a>
      <a href="admin_results.php" class="button button-secondary">View Results</a>
      <a href="voter_management.php" class="button button-secondary">Voter Management</a>
      <a href="view_audit.php" class="button button-secondary">Audit Logs</a>
      <a href="../backend/tamper_check.php" class="button button-secondary">Tamper Check</a>
      <a href="../backend/create_tamper_test_mongo.php" class="button" style="background-color: #ff9800; color: white;">Create Tamper Test Data</a>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
