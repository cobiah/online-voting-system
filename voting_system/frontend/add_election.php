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

$elections = [];
$electionsError = null;
try {
    $elections = db_get_all_elections();
} catch (Throwable $e) {
    $electionsError = 'Unable to load elections: ' . htmlspecialchars($e->getMessage());
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>

  <section class="panel">
    <h2>Manage Elections</h2>
    <p>Create upcoming elections and set them active when they are ready to appear on the homepage.</p>

    <?php if ($flash): ?>
      <div class="alert <?= htmlspecialchars($flash['type']) ?>">
        <span class="icon"><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($electionsError)): ?>
      <div class="alert error">
        <span class="icon">⚠️</span>
        <span><?= htmlspecialchars($electionsError) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= htmlspecialchars(app_url('backend/add_election.php')) ?>" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
      <input type="hidden" name="title" value="General Election">
      <input type="hidden" name="description" value="">

      <div class="form-group">
        <label for="start_date">Voting Start Date</label>
        <input id="start_date" class="form-control" type="date" name="start_date" required>
      </div>

      <div class="form-group">
        <label for="duration_hours">Duration (hours)</label>
        <input id="duration_hours" class="form-control" type="number" name="duration_hours" min="1" value="24" required>
      </div>

      <div class="form-group">
        <label for="is_active">Open voting immediately</label>
        <select id="is_active" class="form-control" name="is_active" required>
          <option value="1">Yes</option>
          <option value="0">No</option>
        </select>
      </div>
      <p style="color: rgba(0,0,0,0.65); margin-bottom: 18px;">Set a start date and duration in hours, then activate voting with the start button when ready.</p>

      <button class="button button-primary" type="submit">Add Election</button>
    </form>

    <div style="margin: 24px 0; display: flex; gap: 12px; flex-wrap: wrap;">
      <form action="<?= htmlspecialchars(app_url('backend/toggle_election.php')) ?>" method="post" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="start_all">
        <button class="button button-primary" type="submit">Start Voting For All Departments</button>
      </form>
      <form action="<?= htmlspecialchars(app_url('backend/toggle_election.php')) ?>" method="post" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="stop_all">
        <button class="button button-secondary" type="submit">Stop Voting For All Departments</button>
      </form>
    </div>

    <?php if (!empty($elections)): ?>
      <table class="data-table" style="margin-top: 22px;">
        <thead>
          <tr>
            <th>Title</th>
            <th>Period</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($elections as $election): ?>
            <tr>
              <td><?= htmlspecialchars($election['title']) ?></td>
              <td>
                <?= htmlspecialchars($election['start_date'] ?: 'TBD') ?>
                &ndash;
                <?= htmlspecialchars($election['end_date'] ?: 'TBD') ?>
              </td>
              <td><?= htmlspecialchars($election['duration_hours'] ? $election['duration_hours'] . ' hr' : 'N/A') ?></td>
              <td><?= $election['is_active'] ? '<span class="status-pill status-active">Active</span>' : '<span class="status-pill status-error">Draft</span>' ?></td>
              <td>
                <form action="<?= htmlspecialchars(app_url('backend/toggle_election.php')) ?>" method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                  <input type="hidden" name="election_id" value="<?= (int)$election['election_id'] ?>">
                  <input type="hidden" name="action" value="<?= $election['is_active'] ? 'stop' : 'start' ?>">
                  <button class="button <?= $election['is_active'] ? 'button-secondary' : 'button-primary' ?>" type="submit">
                    <?= $election['is_active'] ? 'Stop Voting' : 'Start Voting' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert info" style="margin-top: 22px;">
        <span class="icon">ℹ️</span>
        <span>No elections have been added yet.</span>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include '../includes/footer.php'; ?>
