<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no'] ?? '');
    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($reg_no === '' || $full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'All fields are required.'
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

    $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE email = ? OR reg_no = ?');
    $checkStmt->bind_param('ss', $email, $reg_no);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'An account with that email or Student ID already exists.'
        ];
        header('Location: ../frontend/register.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO students (reg_no, full_name, email, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $reg_no, $full_name, $email, $password_hash);

    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        if (function_exists('log_action')) {
            log_action($conn, 'Student registered', $student_id);
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Registration successful. Please log in.'
        ];
        header('Location: ../frontend/login.php');
        exit;
    }

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Unable to register. Please try again later.'
    ];
    header('Location: ../frontend/register.php');
    exit;
}
?>