<aside class="sidebar">
  <h3>Admin Panel</h3>
  <nav>
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <a href="admin_dashboard.php" class="<?= $current === 'admin_dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="add_candidate.php" class="<?= $current === 'add_candidate.php' ? 'active' : '' ?>">✅ Manage Candidates</a>
    <a href="add_election.php" class="<?= $current === 'add_election.php' ? 'active' : '' ?>">🗳️ Election Control</a>
    <a href="admin_results.php" class="<?= $current === 'admin_results.php' ? 'active' : '' ?>">📊 Results</a>
    <a href="voter_management.php" class="<?= $current === 'voter_management.php' ? 'active' : '' ?>">👥 Voter Management</a>
    <a href="view_audit.php" class="<?= $current === 'view_audit.php' ? 'active' : '' ?>">📝 Audit Logs</a>
    <a href="../backend/tamper_check.php" class="<?= $current === 'tamper_check.php' ? 'active' : '' ?>">🔍 Tamper Check</a>
    <a href="../backend/logout.php" class="button button-danger" style="margin-top: 12px;">Logout</a>
  </nav>
</aside>
