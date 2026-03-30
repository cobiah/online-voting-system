<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

include '../backend/db.php';

$studentId = (int)$_SESSION['student_id'];
$fullName = 'Student';

$stmt = $conn->prepare('SELECT full_name FROM students WHERE student_id = ?');
$stmt->bind_param('i', $studentId);
$stmt->execute();
$stmt->bind_result($fullName);
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

$today = date('Y-m-d');
$electionMessage = 'No voting process is currently active.';
$electionOpen = false;
$electionTitle = null;
$electionEnd = null;
if ($currentElection) {
    $electionTitle = $currentElection['title'];
    $electionEnd = $currentElection['end_date'];
    if (!empty($currentElection['start_date']) && $today < $currentElection['start_date']) {
        $electionMessage = 'Voting will begin on ' . date('F j, Y', strtotime($currentElection['start_date'])) . '.';
    } elseif (!empty($currentElection['end_date']) && $today > $currentElection['end_date']) {
        $electionMessage = 'This voting period ended on ' . date('F j, Y', strtotime($currentElection['end_date'])) . '.';
    } else {
        $electionOpen = true;
        $electionMessage = 'Voting is live now!';
        if (!empty($currentElection['end_date'])) {
            $electionMessage .= ' Ends on ' . date('F j, Y', strtotime($currentElection['end_date'])) . '.';
        }
    }
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

      <div style="margin-top: 18px;">
        <p style="margin: 0 0 10px; color: #333; font-weight: 500;"><?= htmlspecialchars($electionTitle ?? 'No election selected') ?></p>
        <p style="margin: 0 0 20px; color: #555;"><?= htmlspecialchars($electionMessage) ?></p>
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