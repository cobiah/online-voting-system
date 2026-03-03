
<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT student_id, password_hash FROM students WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($student_id, $password_hash);
    $stmt->fetch();

    if (password_verify($password, $password_hash)) {
        $_SESSION['student_id'] = $student_id;
        header("Location: ../frontend/vote.html");
    } else {
        echo "Invalid credentials.";
    }
}
?>
<?php include "includes/footer.php"; ?>