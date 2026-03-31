<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

include '../backend/db.php';

$positions = [];
$posRes = $conn->query('SELECT position_id, position_name FROM positions ORDER BY position_name');
while ($row = $posRes->fetch_assoc()) {
    $positions[] = $row;
}

$students = [];
$studentRes = $conn->query('SELECT student_id, reg_no, full_name, department FROM students ORDER BY full_name');
while ($row = $studentRes->fetch_assoc()) {
    $students[] = $row;
}

$candidates = [];
$res = $conn->query('SELECT c.candidate_id, c.name, p.position_name, d.name AS department, c.gender, c.image_url, c.manifesto FROM candidates c JOIN positions p ON c.position_id = p.position_id LEFT JOIN departments d ON c.department_id = d.department_id ORDER BY p.position_name, c.name');
while ($row = $res->fetch_assoc()) {
    $candidates[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard">
  <?php include '../includes/sidebar_admin.php'; ?>
  <section class="panel">
    <?php if ($flash): ?>
      <div class="alert <?= htmlspecialchars($flash['type']) ?>">
        <span class="icon"><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <h2>Manage Candidates</h2>
    <p>Add new candidates and review the current list.</p>

    <form action="/voting_system/backend/add_candidate.php" method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="student_id">Student</label>
        <select id="student_id" class="form-control" name="student_id" required>
          <option value="">Select an existing student</option>
          <?php foreach ($students as $student): ?>
            <option value="<?= (int)$student['student_id'] ?>"><?= htmlspecialchars($student['reg_no'] . ' — ' . $student['full_name'] . ' (' . $student['department'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="position_id">Position</label>
        <select id="position_id" class="form-control" name="position_id" required>
          <option value="">Select a position</option>
          <?php foreach ($positions as $position): ?>
            <option value="<?= $position['position_id'] ?>"><?= htmlspecialchars($position['position_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="gender">Candidate Gender</label>
        <select id="gender" class="form-control" name="gender" required>
          <option value="">Select a gender</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="any">Any</option>
        </select>
      </div>

      <div class="form-group">
        <label for="manifesto">Manifesto</label>
        <textarea id="manifesto" class="form-control" name="manifesto" rows="4" placeholder="Candidate manifesto or campaign message"></textarea>
      </div>

      <div class="form-group">
        <label for="image">Candidate Photo</label>
        <input id="image" class="form-control" type="file" name="image" accept="image/png,image/jpeg">
      </div>

      <button class="button button-primary" type="submit">Add Candidate</button>
    </form>

    <?php if (!empty($candidates)): ?>
      <table class="data-table" style="margin-top: 22px;">
        <thead>
          <tr>
            <th>Name</th>
            <th>Photo</th>
            <th>Position</th>
            <th>Department</th>
            <th>Gender</th>
            <th>Manifesto</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidates as $candidate): ?>
            <tr>
              <td><?= htmlspecialchars($candidate['name']) ?></td>
              <td>
                <?php if (!empty($candidate['image_url'])): ?>
                  <img src="<?= htmlspecialchars($candidate['image_url']) ?>" alt="Candidate photo" class="table-thumbnail">
                <?php else: ?>
                  <span class="muted">No photo</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($candidate['position_name']) ?></td>
              <td><?= htmlspecialchars($candidate['department'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars(ucfirst($candidate['gender'])) ?></td>
              <td><?= htmlspecialchars($candidate['manifesto'] ?? '') ?></td>
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