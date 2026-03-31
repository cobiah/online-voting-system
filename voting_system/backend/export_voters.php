<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

$sql = "SELECT s.reg_no,
               s.full_name,
               s.email,
               COALESCE(d.name, s.department, 'Unknown') AS department,
               s.is_locked,
               COUNT(v.vote_id) AS votes_cast,
               s.created_at
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN votes v ON s.student_id = v.student_id
        GROUP BY s.student_id
        ORDER BY s.full_name";

$res = $conn->query($sql);
if (!$res) {
    die('Database error: ' . $conn->error);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="voter_list.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student ID', 'Name', 'Email', 'Department', 'Status', 'Votes Cast', 'Registered At']);

while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['reg_no'],
        $row['full_name'],
        $row['email'],
        $row['department'],
        $row['is_locked'] ? 'Locked' : 'Active',
        $row['votes_cast'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
