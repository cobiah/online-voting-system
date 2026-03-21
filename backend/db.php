<?php
$host = "127.0.0.1";
$port = 3306;
$user = "root";
$pass = "";
$dbname = "voting_system";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simple audit logging helper
if (!function_exists('log_action')) {
    /**
     * Log an action to the audit_log table.
     * @param mysqli $conn
     * @param string $action
     * @param int $user_id
     */
    function log_action($conn, $action, $user_id = 0) {
        if (!($conn instanceof mysqli)) {
            return;
        }

        $stmt = $conn->prepare("INSERT INTO audit_log (action, user_id) VALUES (?, ?)");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param("si", $action, $user_id);
        $stmt->execute();
    }
}
?>