<?php
include 'db.php';

$sql = "SELECT c.name, c.position, COUNT(v.vote_id) AS total_votes
        FROM candidates c
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id
        GROUP BY c.candidate_id, c.name, c.position
        ORDER BY total_votes DESC";

$result = $conn->query($sql);

echo "<h2>Election Results</h2>";
echo "<table border='1'>
        <tr><th>Candidate</th><th>Position</th><th>Total Votes</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['name']}</td>
            <td>{$row['position']}</td>
            <td>{$row['total_votes']}</td>
          </tr>";
}
echo "</table>";
?>