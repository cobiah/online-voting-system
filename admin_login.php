
<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Demo: hardcoded admin credentials
    if ($username === "admin" && $password === "admin123") {
        $_SESSION['admin'] = true;
        header("Location: admin_dashboard.php");
    } else {
        echo "Invalid admin credentials.";
    }
}
?>
<?php include "includes/footer.php"; ?>