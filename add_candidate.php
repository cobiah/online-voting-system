<?php include "includes/header.php"; ?>
<?php
include 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $position = $_POST['position'];

    $stmt = $conn->prepare("INSERT INTO candidates (name, position) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $position);
    $stmt->execute();

    echo "Candidate added successfully!";
}
?>
<?php include "includes/footer.php"; ?>
<?php include "includes/footer.php"; ?>