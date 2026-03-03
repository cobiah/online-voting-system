<?php include "includes/header.php"; ?>
<?php
include 'db.php';

$sql = "SELECT * FROM audit_log ORDER BY timestamp DESC";
$result = $conn->query($sql);

echo "<h2>Audit Logs</h2>";
echo "<table border='1'><tr><th>Action</th><th>User ID</th><th>Timestamp</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['action'] . "</td><td>" . $row['user_id'] . "</td><td>" . $row['timestamp'] . "</td></tr>";
}
echo "</table>";
?>
<?php include "includes/footer.php"; ?>