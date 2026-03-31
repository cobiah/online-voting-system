<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!database_ready($conn)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Registration is temporarily unavailable because the database connection is down.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Invalid request. Please try again.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    $reg_no = trim($_POST['reg_no'] ?? '');
    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($reg_no === '' || $full_name === '' || $email === '' || $department_id <= 0 || $password === '' || $confirm_password === '') {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'All fields are required.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if (strlen($reg_no) > 50 || strlen($full_name) > 100 || strlen($email) > 100) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Input fields exceed maximum length.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Invalid email format.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if ($department_id <= 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Please select a valid department.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    $department = db_get_department_by_id($department_id);
    $department_name = $department['name'] ?? '';
    if ($department_name === '') {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Selected department is invalid.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Password must be at least 8 characters long.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Passwords do not match.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    if (db_student_exists_by_email_or_regno($email, $reg_no)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'An account with that email or Student ID already exists.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $student_id = db_create_student($reg_no, $full_name, $email, $password_hash, $department_id, $department_name);
        if (function_exists('log_action')) {
            log_action($conn, 'Student registered', $student_id);
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Registration successful. Please log in.'
        ];
        header('Location: ../frontend/login.php');
        exit;
    } catch (Throwable $e) {
    }

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Unable to register. Please try again later.'
    ];
    header('Location: ../frontend/register.php');
    exit;
}
?>
