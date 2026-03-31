<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
// Prevent students from accessing admin panel
if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

include '../backend/db.php';

$students = [];
$sql = "SELECT s.student_id,
               s.reg_no,
               s.full_name,
               s.email,
               COALESCE(d.name, s.department, 'Unknown') AS department,
               s.is_locked,
               COUNT(v.vote_id) AS votes_cast,
               s.created_at
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN votes v ON s.student_id = v.student_id
        GROUP BY s.student_id
        ORDER BY s.full_name";

$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>Voter Management</h2>
    <p>Review registered voters, lock accounts, reset votes, and export the voter list.</p>

    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
      <a href="../backend/export_voters.php" class="button button-primary">Export Voter CSV</a>
      <form method="post" action="../backend/voter_action.php" onsubmit="return confirm('This will clear all voting history. Continue?');" style="display:inline;">
        <input type="hidden" name="action" value="clear_all">
        <button class="button button-danger" type="submit">Clear Voting History</button>
      </form>
    </div>

    <?php if (empty($students)): ?>
      <div class="alert info">
        <span class="icon">ℹ️</span>
        <span>No registered voters found.</span>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Votes Cast</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): ?>
            <tr>
              <td><?= htmlspecialchars($student['reg_no']) ?></td>
              <td><?= htmlspecialchars($student['full_name']) ?></td>
              <td><?= htmlspecialchars($student['email']) ?></td>
              <td><?= htmlspecialchars($student['department']) ?></td>
              <td><?= (int)$student['votes_cast'] ?></td>
              <td><?= $student['is_locked'] ? '<span class="status-pill status-error">Locked</span>' : '<span class="status-pill status-active">Active</span>' ?></td>
              <td style="white-space: nowrap; display: flex; gap: 8px; flex-wrap: wrap;">
                <form method="post" action="../backend/voter_action.php">
                  <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">
                  <input type="hidden" name="action" value="<?= $student['is_locked'] ? 'unlock' : 'lock' ?>">
                  <button class="button <?= $student['is_locked'] ? 'button-secondary' : 'button-danger' ?>" type="submit"><?= $student['is_locked'] ? 'Unlock' : 'Lock' ?></button>
                </form>
                <form method="post" action="../backend/voter_action.php">
                  <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">
                  <input type="hidden" name="action" value="reset">
                  <button class="button button-secondary" type="submit">Reset Votes</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
