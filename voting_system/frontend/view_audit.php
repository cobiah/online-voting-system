<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

include '../backend/db.php';
include '../includes/header.php';

$logs = database_ready($conn) ? db_get_audit_logs() : [];
?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>System Audit Logs</h2>
    <p>Recent actions tracked by the system.</p>

    <?php if (empty($logs)): ?>
      <div class="alert error">
        <span class="icon">&#9888;</span>
        <span>No audit logs found.</span>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Action</th>
            <th>User ID</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['timestamp']) ?></td>
              <td><?= htmlspecialchars($row['action']) ?></td>
              <td><?= htmlspecialchars((string) $row['user_id']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
