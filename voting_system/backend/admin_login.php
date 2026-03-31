<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!database_ready($conn)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Admin login is temporarily unavailable because the database connection is down.'
        ];
        header("Location: ../frontend/admin_login.php");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple rate limiting: max 20 attempts per session
    if (!isset($_SESSION['admin_login_attempts'])) {
        $_SESSION['admin_login_attempts'] = 0;
    }
    if ($_SESSION['admin_login_attempts'] >= 20) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Too many login attempts. Please try again later.'
        ];
        header("Location: ../frontend/admin_login.php");
        exit;
    }

    $admin = db_find_admin_by_username($username);

    if ($admin && password_verify($password, (string) $admin['password_hash'])) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = (int) $admin['admin_id'];
        $_SESSION['admin_username'] = $username;

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Reset attempts on success
        unset($_SESSION['admin_login_attempts']);

        if (function_exists('log_action')) {
            log_action($conn, 'Admin logged in', 0);
        }

        header("Location: ../frontend/admin_dashboard.php");
        exit;
    }

    // Increment attempts on failure
    $_SESSION['admin_login_attempts']++;

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid admin username or password.'
    ];

    header("Location: ../frontend/admin_login.php");
    exit;
}
