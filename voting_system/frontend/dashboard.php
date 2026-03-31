<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
if (isset($_SESSION['admin']) && !isset($_SESSION['student_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

include '../backend/db.php';

$studentId = (int) $_SESSION['student_id'];
$student = db_get_student_by_id($studentId) ?? [];
$fullName = $student['full_name'] ?? 'Student';
$studentDepartment = $student['department'] ?? 'Unknown Department';
if ($studentDepartment === '') {
    $studentDepartment = 'Unknown Department';
}

$currentElection = db_get_current_election();
$electionMessage = 'No voting process is currently active.';
$electionOpen = false;
$electionTitleLabel = 'No active election';

if ($currentElection) {
    $electionTitle = (string) ($currentElection['title'] ?? '');
    $electionTitleLabel = ($electionTitle && $electionTitle !== 'General Election') ? $electionTitle : 'Department Voting';
    $electionOpen = true;
    $electionMessage = 'Voting is currently open by admin.';
}

$hasVoted = db_count_votes_for_student($studentId) > 0;

include '../includes/header.php';
?>

<div class="dashboard">
  <?php include '../includes/sidebar_student.php'; ?>

  <section class="panel">
    <div class="profile-card">
      <div class="profile-badge">
        <div class="profile-avatar"><?= strtoupper(substr($fullName, 0, 1)) ?></div>
        <div>
          <p class="profile-title">Welcome, <?= htmlspecialchars($fullName) ?></p>
          <p class="profile-subtitle">Election Status:
            <span class="status-pill <?= $electionOpen ? 'status-active' : 'status-error' ?>">
              <?= $electionOpen ? 'LIVE' : 'CLOSED' ?></span>
          </p>
        </div>
      </div>

      <div style="margin-top: 18px; display: grid; gap: 10px;">
        <p style="margin: 0 0 4px; color: #333; font-weight: 700; font-size: 1.05rem;">
          <?= htmlspecialchars($electionTitleLabel) ?>
        </p>
        <p style="margin: 0 0 4px; color: #555;"><?= htmlspecialchars($electionMessage) ?></p>
        <p style="margin: 0; color: #555;">Department: <?= htmlspecialchars($studentDepartment) ?></p>
      </div>

      <div style="margin-top: 22px; display: grid; gap: 12px;">
        <?php if ($electionOpen && !$hasVoted): ?>
          <a href="vote.php" class="button button-primary">Go Vote</a>
        <?php elseif ($electionOpen && $hasVoted): ?>
          <span class="status-pill status-success">Vote submitted</span>
        <?php else: ?>
          <span class="status-pill status-secondary">No active voting process</span>
        <?php endif; ?>
        <a href="results.php" class="button button-secondary">View Results</a>
      </div>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
