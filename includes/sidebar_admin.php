<aside class="sidebar">
  <h3>Admin Panel</h3>
  <nav>
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <a href="admin_dashboard.php" class="<?= $current === 'admin_dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="add_candidate.php" class="<?= $current === 'add_candidate.php' ? 'active' : '' ?>">✅ Manage Candidates</a>
    <a href="results.php" class="<?= $current === 'results.php' ? 'active' : '' ?>">📊 Results</a>
    <a href="view_audit.php" class="<?= $current === 'view_audit.php' ? 'active' : '' ?>">📝 Audit Logs</a>
    <a href="../backend/tamper_check.php" class="<?= $current === 'tamper_check.php' ? 'active' : '' ?>">🔍 Tamper Check</a>
    <a href="../backend/logout.php" class="button button-danger" style="margin-top: 12px;">Logout</a>
  </nav>
</aside>
