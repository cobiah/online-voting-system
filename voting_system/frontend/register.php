<?php include '../includes/header.php'; ?>
<?php include '../backend/db.php'; ?>

<?php
$departments = [];
if (database_ready($conn)) {
    foreach (db_get_departments() as $row) {
        $departments[] = $row;
    }
}
?>

<div class="page-center">
  <div class="card">
    <h2>Create Account</h2>
    <p>Register to vote in the upcoming election.</p>

    <?php if (!database_ready($conn)): ?>
      <div class="alert error">
        <span class="icon">!</span>
        <span>Registration is temporarily unavailable because the database connection is down.</span>
      </div>
    <?php else: ?>

    <form action="<?= htmlspecialchars(app_url('backend/register.php')) ?>" method="post" onsubmit="return validateRegistration();">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
      <div class="form-group">
        <label for="reg_no">Student ID</label>
        <input id="reg_no" class="form-control" type="text" name="reg_no" required>
      </div>

      <div class="form-group">
        <label for="name">Full Name</label>
        <input id="name" class="form-control" type="text" name="name" required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" class="form-control" type="email" name="email" required>
      </div>

      <div class="form-group">
        <label for="department_id">Department</label>
        <select id="department_id" class="form-control" name="department_id" required>
          <option value="">Select your department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= (int)$dept['department_id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" class="form-control" type="password" name="password" required>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" class="form-control" type="password" name="confirm_password" required>
      </div>

      <button class="button button-primary" type="submit">Register</button>

      <p style="text-align:center; margin-top: 16px;">
        Already have an account? <a href="login.php">Login</a>
      </p>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
