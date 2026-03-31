<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
// Prevent admins from accessing student dashboard
if (isset($_SESSION['admin']) && !isset($_SESSION['student_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

include '../backend/db.php';

$studentId = (int)$_SESSION['student_id'];
$fullName = 'Student';

$studentDepartment = 'Unknown Department';
$stmt = $conn->prepare('SELECT full_name, COALESCE(department, "", "Unknown Department") AS department FROM students WHERE student_id = ?');
$stmt->bind_param('i', $studentId);
$stmt->execute();
$stmt->bind_result($fullName, $studentDepartment);
$stmt->fetch();
$stmt->close();

$stmtElection = $conn->prepare("SELECT election_id, title, start_date, end_date, duration_hours FROM elections WHERE is_active = 1 ORDER BY start_date ASC LIMIT 1");
$currentElection = null;
if ($stmtElection) {
    $stmtElection->execute();
    $resultElection = $stmtElection->get_result();
    $currentElection = $resultElection->fetch_assoc();
    $stmtElection->close();
}

$electionMessage = 'No voting process is currently active.';
$electionOpen = false;
$electionTitle = null;
$electionTitleLabel = 'No active election';
if ($currentElection) {
    $electionTitle = $currentElection['title'];
    $electionTitleLabel = ($electionTitle && $electionTitle !== 'General Election') ? $electionTitle : 'Department Voting';
    $electionOpen = true;
    $electionMessage = 'Voting is currently open by admin.';
}

$voteStatus = $conn->prepare('SELECT COUNT(*) FROM votes WHERE student_id = ?');
$voteStatus->bind_param('i', $studentId);
$voteStatus->execute();
$voteStatus->bind_result($studentVoteCount);
$voteStatus->fetch();
$voteStatus->close();

$hasVoted = $studentVoteCount > 0;

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