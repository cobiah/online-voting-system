<aside class="sidebar">
  <h3>Student Panel</h3>
  <?php
    $studentHasVoted = false;
    if (isset($_SESSION['student_id']) && isset($conn)) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM votes WHERE student_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $_SESSION['student_id']);
            $stmt->execute();
            $stmt->bind_result($voteCount);
            $stmt->fetch();
            $stmt->close();
            $studentHasVoted = $voteCount > 0;
        }
    }
  ?>
  <nav>
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <?php if (!$studentHasVoted): ?>
      <a href="vote.php" class="<?= $current === 'vote.php' ? 'active' : '' ?>">🗳️ Vote</a>
    <?php endif; ?>
    <a href="results.php" class="<?= $current === 'results.php' ? 'active' : '' ?>">📊 Results</a>
    <a href="../backend/logout.php" class="button button-danger" style="margin-top: 12px;">Logout</a>
  </nav>
</aside>
