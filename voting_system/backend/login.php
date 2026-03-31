<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!database_ready($conn)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Login is temporarily unavailable because the database connection is down.'
        ];
        header("Location: ../frontend/login.php");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple rate limiting: max 20 attempts per session
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    if ($_SESSION['login_attempts'] >= 20) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Too many login attempts. Please try again later.'
        ];
        header("Location: ../frontend/login.php");
        exit;
    }

    $student = db_find_student_by_email($email);

    if ($student && password_verify($password, (string) $student['password_hash'])) {
        $_SESSION['student_id'] = (int) $student['student_id'];
        $_SESSION['student_name'] = (string) $student['full_name'];

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Reset attempts on success
        unset($_SESSION['login_attempts']);

        // Audit log
        if (function_exists('log_action')) {
            log_action($conn, 'User logged in', (int) $student['student_id']);
        }

        header("Location: ../frontend/dashboard.php");
        exit;
    }

    // Increment attempts on failure
    $_SESSION['login_attempts']++;

    // Authentication failed
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid email or password.'
    ];

    header("Location: ../frontend/login.php");
    exit;
}
?>
