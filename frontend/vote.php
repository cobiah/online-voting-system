<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

include '../backend/db.php';

$studentId = (int)$_SESSION['student_id'];

// Determine which positions still need votes
$positions = [];
$res = $conn->query("SELECT DISTINCT position FROM candidates ORDER BY position");
while ($row = $res->fetch_assoc()) {
    $positions[] = $row['position'];
}

$votedPositions = [];
$voteCheck = $conn->prepare('SELECT DISTINCT position FROM votes WHERE student_id = ?');
$voteCheck->bind_param('i', $studentId);
$voteCheck->execute();
$voteResult = $voteCheck->get_result();
while ($row = $voteResult->fetch_assoc()) {
    $votedPositions[] = $row['position'];
}

$remainingPositions = array_values(array_diff($positions, $votedPositions));
$hasVoted = empty($remainingPositions);

// Fetch candidates grouped by position
$candidatesByPosition = [];
$statement = $conn->prepare("SELECT candidate_id, name, position FROM candidates ORDER BY position, name");
$statement->execute();
$res = $statement->get_result();
while ($row = $res->fetch_assoc()) {
    $candidatesByPosition[$row['position']][] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_student.php'; ?>

  <section class="panel">
    <h2>Cast Your Vote</h2>
    <p>Select a candidate below and submit your vote.</p>

    <?php if ($hasVoted): ?>
      <div class="alert success">
        <span class="icon">✅</span>
        <span>You have already cast your vote for all positions. Thank you for participating!</span>
      </div>
      <div style="margin-top: 16px;">
        <a href="dashboard.php" class="button button-secondary">Back to Dashboard</a>
      </div>
    <?php elseif (empty($candidatesByPosition)): ?>
      <div class="alert error">
        <span class="icon">⚠️</span>
        <span>No candidates are currently available. Please check back later.</span>
      </div>
    <?php else: ?>
      <form action="/voting_system/backend/vote.php" method="post" onsubmit="return confirmVote();">
        <?php foreach ($remainingPositions as $position): ?>
          <div class="card" style="margin-bottom: 18px;">
            <h3 style="margin-top: 0;"><?= htmlspecialchars($position) ?></h3>

            <?php foreach ($candidatesByPosition[$position] ?? [] as $candidate): ?>
              <div class="form-group">
                <label>
                  <input type="radio" name="candidate_id[<?= htmlspecialchars($position) ?>]" value="<?= $candidate['candidate_id'] ?>" required>
                  <strong><?= htmlspecialchars($candidate['name']) ?></strong>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <button class="button button-primary" type="submit">Submit Vote</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<?php include '../includes/footer.php'; ?>