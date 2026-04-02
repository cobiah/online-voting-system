<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/add_candidate.php');
    exit;
}

if (!database_ready($conn)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Candidate management is temporarily unavailable because the database connection is down.'
    ];
    header('Location: ../frontend/add_candidate.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid request. Please try again.'
    ];
    header('Location: ../frontend/add_candidate.php');
    exit;
}

$student_id = !empty($_POST['student_id']) ? (int) $_POST['student_id'] : null;
$candidate_name = trim((string) ($_POST['candidate_name'] ?? ''));
$position_id = (int) ($_POST['position_id'] ?? 0);
$department_id = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
$department = trim((string) ($_POST['department'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? 'any'));
$manifesto = trim((string) ($_POST['manifesto'] ?? ''));
$imageUrl = '';

$allowedGenders = ['male', 'female', 'any'];
if ($candidate_name === '' || $position_id <= 0 || !in_array($gender, $allowedGenders, true)) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Candidate name, position, and gender are required.'
    ];
    header('Location: ../frontend/add_candidate.php');
    exit;
}

$studentDepartmentId = $department_id;
$studentDepartment = $department;
if ($student_id) {
    $student = db_get_student_by_id($student_id);
    if (!$student) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Selected student does not exist in the student database.'
        ];
        header('Location: ../frontend/add_candidate.php');
        exit;
    }

    $studentDepartmentId = (int) ($student['department_id'] ?? 0);
    $studentDepartment = (string) ($student['department'] ?? '');

    if (db_find_candidate_by_student_position($student_id, $position_id)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'This student is already registered as a candidate for the selected position.'
        ];
        header('Location: ../frontend/add_candidate.php');
        exit;
    }
}

if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
    $allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if ($imageInfo && isset($allowedTypes[$imageInfo[2]])) {
        $extension = $allowedTypes[$imageInfo[2]];
        $uploadDir = __DIR__ . '/../assets/images/candidates';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'candidate_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $imageUrl = app_url('assets/images/candidates/' . $filename);
        }
    }
}

db_create_candidate($student_id, $candidate_name, $position_id, $studentDepartmentId, $studentDepartment, $gender, $manifesto, $imageUrl);

if (function_exists('log_action')) {
    log_action($conn, 'Candidate added: ' . $candidate_name . ' (position_id: ' . $position_id . ', department_id: ' . $studentDepartmentId . ')', 0);
}

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Candidate added successfully.'
];

header('Location: ../frontend/add_candidate.php');
exit;
?>
