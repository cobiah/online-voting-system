<aside class="sidebar">
  <h3>Student Panel</h3>
  <nav>
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="vote.php" class="<?= $current === 'vote.php' ? 'active' : '' ?>">🗳️ Vote</a>
    <a href="results.php" class="<?= $current === 'results.php' ? 'active' : '' ?>">📊 Results</a>
    <a href="view_audit.php" class="<?= $current === 'view_audit.php' ? 'active' : '' ?>">📝 Audit Logs</a>
    <a href="../backend/logout.php" class="button button-danger" style="margin-top: 12px;">Logout</a>
  </nav>
</aside>
