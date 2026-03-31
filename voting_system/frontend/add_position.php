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

$positions = [];
$positionsError = null;
try {
    $positions = db_get_positions();
} catch (Throwable $e) {
    $positionsError = 'Unable to load positions: ' . htmlspecialchars($e->getMessage());
}

// Handle flash messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>Manage Election Positions</h2>
    <p>Add new positions for the upcoming election and review the current list.</p>

    <?php if ($flash): ?>
      <div class="alert <?= htmlspecialchars($flash['type']) ?>">
        <span class="icon"><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($positionsError)): ?>
      <div class="alert error">
        <span class="icon">⚠️</span>
        <span><?= htmlspecialchars($positionsError) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= htmlspecialchars(app_url('backend/add_position.php')) ?>" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
      <div class="form-group">
        <label for="position_name">Position Name</label>
        <input id="position_name" class="form-control" type="text" name="position_name" required>
      </div>

      <div class="form-group">
        <label for="description">Description (Optional)</label>
        <textarea id="description" class="form-control" name="description" rows="3"></textarea>
      </div>

      <button class="button button-primary" type="submit">Add Position</button>
    </form>

    <?php if (!empty($positions)): ?>
      <table class="data-table" style="margin-top: 22px;">
        <thead>
          <tr>
            <th>Position Name</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($positions as $position): ?>
            <tr>
              <td><?= htmlspecialchars($position['position_name']) ?></td>
              <td><?= htmlspecialchars($position['description'] ?? '') ?></td>
              <td>
                <form action="<?= htmlspecialchars(app_url('backend/delete_position.php')) ?>" method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                  <input type="hidden" name="position_id" value="<?= (int)$position['position_id'] ?>">
                  <button class="button button-danger" type="submit" onclick="return confirm('Delete this position and all related candidates/votes?');">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert">
        <span class="icon">ℹ️</span>
        <span>No positions have been added yet.</span>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include '../includes/footer.php'; ?>
