<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

include '../backend/db.php';

$positions = [];
foreach (db_get_positions() as $row) {
    $positions[] = $row;
}

$students = [];
foreach (db_get_students_for_candidate_form() as $row) {
    $students[] = $row;
}

$departments = [];
foreach (db_get_departments() as $row) {
    $departments[] = $row;
}

$candidates = [];
foreach (db_get_candidates_admin() as $row) {
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
        <span class="icon"><?= $flash['type'] === 'success' ? '&#10003;' : '&#9888;' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <h2>Manage Candidates</h2>
    <p>Add new candidates - either from registered students or manual entry.</p>

    <form action="<?= htmlspecialchars(app_url('backend/add_candidate.php')) ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
      <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 14px; color: #555;"><strong>Add Candidate</strong> - Select an existing student OR manually enter candidate details.</p>
      </div>

      <div class="form-group">
        <label for="student_id">Select Existing Student (Optional)</label>
        <select id="student_id" class="form-control" name="student_id" onchange="populateFromStudent()">
          <option value="">--- Manual Entry (No Student) ---</option>
          <?php foreach ($students as $student): ?>
            <option value="<?= (int) $student['student_id'] ?>" data-name="<?= htmlspecialchars($student['full_name']) ?>" data-dept="<?= htmlspecialchars($student['department']) ?>">
              <?= htmlspecialchars($student['reg_no'] . ' - ' . $student['full_name'] . ' (' . $student['department'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small style="color: #666; display: block; margin-top: 5px;">Selecting a student will auto-fill their name and department.</small>
      </div>

      <div class="form-group">
        <label for="candidate_name">Candidate Name *</label>
        <input id="candidate_name" class="form-control" type="text" name="candidate_name" placeholder="Enter full name of candidate" required>
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
        <label for="department_id">Department (Optional)</label>
        <select id="department_id" class="form-control" name="department_id">
          <option value="">--- Select a department ---</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="department">Department Name (Text)</label>
        <input id="department" class="form-control" type="text" name="department" placeholder="Department name (auto-filled if student selected)">
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

    <script>
    function populateFromStudent() {
      const selectElement = document.getElementById('student_id');
      const candidateName = document.getElementById('candidate_name');
      const department = document.getElementById('department');

      if (selectElement.value) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        candidateName.value = selectedOption.getAttribute('data-name');
        department.value = selectedOption.getAttribute('data-dept');
      } else {
        candidateName.value = '';
        department.value = '';
      }
    }
    </script>

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
                <form method="post" action="<?= htmlspecialchars(app_url('backend/delete_candidate.php')) ?>" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                  <input type="hidden" name="candidate_id" value="<?= (int) $candidate['candidate_id'] ?>">
                  <button class="button button-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert">
        <span class="icon">&#9432;</span>
        <span>No candidates have been added yet.</span>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include '../includes/footer.php'; ?>
