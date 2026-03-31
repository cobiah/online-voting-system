<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $stmt = $conn->prepare("SELECT student_id, full_name, password_hash FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($student_id, $full_name, $password_hash);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $password_hash)) {
        $_SESSION['student_id'] = $student_id;
        $_SESSION['student_name'] = $full_name;

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Reset attempts on success
        unset($_SESSION['login_attempts']);

        // Audit log
        if (function_exists('log_action')) {
            log_action($conn, 'User logged in', $student_id);
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