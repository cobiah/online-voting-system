<?php
require_once __DIR__ . '/app.php';
// Safe session start — prevents duplicate session warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$isStudent = isset($_SESSION['student_id']);
$isAdmin = isset($_SESSION['admin']);

$activePage = basename($_SERVER['SCRIPT_NAME']);
function navActive(string $page): string {
    global $activePage;
    return $activePage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Voting System</title>

    <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars(app_url('assets/css/style.css')) ?>">
</head>

<body>
    <header class="site-header">
        <div class="site-brand">
            <img src="<?= htmlspecialchars(app_url('assets/images/logo.png')) ?>" alt="Voting System Logo">
            <div>
                <h1>Secure Voting System</h1>
                <p style="margin:0; font-size:0.85rem; color: rgba(0,0,0,0.6);">Tertiary Institution Elections</p>
            </div>
        </div>

        <nav class="site-nav">
            <!-- Public Navigation (hidden when logged in) -->
            <?php if (!$isStudent && !$isAdmin): ?>
                <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="<?= navActive('index.php') ?>">Home</a>
                <a href="<?= htmlspecialchars(app_url('index.php')) ?>#elections" class="<?= navActive('index.php') ?>">Elections</a>
                <a href="<?= htmlspecialchars(app_url('index.php')) ?>#how" class="<?= navActive('index.php') ?>">How It Works</a>
                <a href="<?= htmlspecialchars(app_url('frontend/results.php')) ?>" class="<?= navActive('results.php') ?>">Results</a>
            <?php endif; ?>

            <!-- Student Navigation -->
            <?php if ($isStudent && !$isAdmin): ?>
                <a href="<?= htmlspecialchars(app_url('frontend/dashboard.php')) ?>" class="<?= navActive('dashboard.php') ?>">Dashboard</a>
                <a href="<?= htmlspecialchars(app_url('backend/logout.php')) ?>" class="button button-secondary">Logout</a>
            <?php endif; ?>

            <!-- Admin Navigation -->
            <?php if ($isAdmin): ?>
                <a href="<?= htmlspecialchars(app_url('frontend/admin_dashboard.php')) ?>" class="<?= navActive('admin_dashboard.php') ?>">Dashboard</a>
                <a href="<?= htmlspecialchars(app_url('backend/logout.php')) ?>" class="button button-secondary">Logout</a>
            <?php endif; ?>

            <!-- Login/Register (shown when not logged in) -->
            <?php if (!$isStudent && !$isAdmin): ?>
                <a href="<?= htmlspecialchars(app_url('frontend/login.php')) ?>" class="button button-secondary">Login</a>
                <a href="<?= htmlspecialchars(app_url('frontend/admin_login.php')) ?>" class="button button-danger">Admin</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="main">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert <?= htmlspecialchars($_SESSION['flash']['type']) ?>">
                <span class="icon"><?= $_SESSION['flash']['type'] === 'success' ? '✅' : '⚠️' ?></span>
                <span><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
