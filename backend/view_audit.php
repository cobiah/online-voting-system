<?php
include 'db.php';

$result = $conn->query("SELECT * FROM audit_log ORDER BY timestamp DESC");

echo "<h2>Audit Logs</h2>";
echo "<table border='1'>
        <tr><th>Time</th><th>Action</th><th>User ID</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['timestamp']}</td>
            <td>{$row['action']}</td>
            <td>{$row['user_id']}</td>
          </tr>";
}
echo "</table>";
?>