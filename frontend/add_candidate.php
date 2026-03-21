<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

include '../backend/db.php';

$candidates = [];
$res = $conn->query('SELECT candidate_id, name, position FROM candidates ORDER BY position, name');
while ($row = $res->fetch_assoc()) {
    $candidates[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>Manage Candidates</h2>
    <p>Add new candidates and review the current list.</p>

    <form action="/voting_system/backend/add_candidate.php" method="post">
      <div class="form-group">
        <label for="name">Name</label>
        <input id="name" class="form-control" type="text" name="name" required>
      </div>

      <div class="form-group">
        <label for="position">Position</label>
        <input id="position" class="form-control" type="text" name="position" required>
      </div>

      <button class="button button-primary" type="submit">Add Candidate</button>
    </form>

    <?php if (!empty($candidates)): ?>
      <table class="data-table" style="margin-top: 22px;">
        <thead>
          <tr>
            <th>Name</th>
            <th>Position</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidates as $candidate): ?>
            <tr>
              <td><?= htmlspecialchars($candidate['name']) ?></td>
              <td><?= htmlspecialchars($candidate['position']) ?></td>
              <td>
                <form method="post" action="/voting_system/backend/delete_candidate.php" style="display:inline;">
                  <input type="hidden" name="candidate_id" value="<?= (int)$candidate['candidate_id'] ?>">
                  <button class="button button-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert">
        <span class="icon">ℹ️</span>
        <span>No candidates have been added yet.</span>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include '../includes/footer.php'; ?>