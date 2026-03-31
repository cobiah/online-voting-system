<?php 
session_start();
// Prevent students from accessing admin login
if (isset($_SESSION['student_id']) && !isset($_SESSION['admin'])) {
    header('Location: /voting_system/frontend/dashboard.php');
    exit;
}
// If already admin, redirect to dashboard
if (isset($_SESSION['admin'])) {
    header('Location: /voting_system/frontend/admin_dashboard.php');
    exit;
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-center">
  <div class="card">
    <h2>Admin Login</h2>
    <p>Access the admin dashboard to manage candidates and view results.</p>

    <form action="/voting_system/backend/admin_login.php" method="post">
      <div class="form-group">
        <label for="username">Username</label>
        <input id="username" class="form-control" type="text" name="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" class="form-control" type="password" name="password" required>
      </div>

      <button class="button button-primary" type="submit">Login</button>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>