<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $gender = trim($_POST['gender'] ?? 'any');
    $manifesto = trim($_POST['manifesto'] ?? '');
    $imageUrl = '';

    $allowedGenders = ['male', 'female', 'any'];
    if ($student_id <= 0 || $position_id <= 0 || !in_array($gender, $allowedGenders, true)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Student, position, and gender are required.'
        ];
        header('Location: ../frontend/add_candidate.php');
        exit;
    }

    $studentStmt = $conn->prepare('SELECT full_name, department_id, department FROM students WHERE student_id = ?');
    $studentStmt->bind_param('i', $student_id);
    $studentStmt->execute();
    $studentStmt->bind_result($candidateName, $studentDepartmentId, $studentDepartment);
    if (!$studentStmt->fetch()) {
        $studentStmt->close();
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Selected student does not exist in the student database.'
        ];
        header('Location: ../frontend/add_candidate.php');
        exit;
    }
    $studentStmt->close();

    $checkExisting = $conn->prepare('SELECT candidate_id FROM candidates WHERE student_id = ? AND position_id = ?');
    $checkExisting->bind_param('ii', $student_id, $position_id);
    $checkExisting->execute();
    $checkExisting->store_result();
    if ($checkExisting->num_rows > 0) {
        $checkExisting->close();
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'This student is already registered as a candidate for the selected position.'
        ];
        header('Location: ../frontend/add_candidate.php');
        exit;
    }
    $checkExisting->close();

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
                $imageUrl = '/voting_system/assets/images/candidates/' . $filename;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO candidates (student_id, name, position_id, department_id, department, gender, manifesto, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiissss", $student_id, $candidateName, $position_id, $studentDepartmentId, $studentDepartment, $gender, $manifesto, $imageUrl);
    $stmt->execute();

    if (function_exists('log_action')) {
        log_action($conn, 'Candidate added: ' . $name . ' (position_id: ' . $position_id . ', department_id: ' . $department_id . ')', 0);
    }
}

header('Location: ../frontend/add_candidate.php');
exit;
?>