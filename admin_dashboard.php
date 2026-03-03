
<?php
session_start();
if (!isset($_SESSION['admin'])) {
    die("Access denied.");
}
?>
<h2>Admin Dashboard</h2>
<ul>
    <li><a href="../frontend/add_candidate.html">Add Candidate</a></li>
    <li><a href="view_audit.php">View Audit Logs</a></li>
    <li><a href="tamper_check.php">Run Tamper Detection</a></li>
    <li><a href="results.php">View Results</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>
<?php include "includes/footer.php"; ?>