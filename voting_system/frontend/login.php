<?php 
session_start();
require_once __DIR__ . '/../includes/app.php';
// Prevent admins from accessing student login
if (isset($_SESSION['admin']) && !isset($_SESSION['student_id'])) {
    header('Location: ' . app_url('frontend/admin_dashboard.php'));
    exit;
}
// If already logged in as student, redirect to dashboard
if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: ' . app_url('frontend/dashboard.php'));
    exit;
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-center">
  <div class="card">
    <h2>Student Login</h2>
    <p>Sign in to access the voting portal and view your dashboard.</p>

    <form action="<?= htmlspecialchars(app_url('backend/login.php')) ?>" method="post">
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" class="form-control" type="email" name="email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" class="form-control" type="password" name="password" required>
      </div>

      <button class="button button-primary" type="submit">Login</button>

      <p style="text-align:center; margin-top: 16px;">
        <a href="register.php">Create an account</a> · <a href="#">Forgot password?</a>
      </p>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
