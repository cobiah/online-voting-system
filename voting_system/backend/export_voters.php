<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

if (!database_ready($conn)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Database unavailable\n";
    exit;
}

$students = db_get_students_admin();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="voter_list.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student ID', 'Name', 'Email', 'Department', 'Status', 'Votes Cast', 'Registered At']);

foreach ($students as $row) {
    fputcsv($output, [
        $row['reg_no'],
        $row['full_name'],
        $row['email'],
        $row['department'],
        !empty($row['is_locked']) ? 'Locked' : 'Active',
        (int) ($row['votes_cast'] ?? 0),
        (string) ($row['created_at'] ?? ''),
    ]);
}

fclose($output);
exit;
?>
