<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

$$type = $_GET['type'] ?? 'full';
$type = strtolower(trim($type));

if ($type !== 'winners') {
    $type = 'full';
}

if ($type === 'winners') {
    $sql = "SELECT d.name AS department,
                   p.position_name,
                   c.candidate_id,
                   c.name AS candidate_name,
                   COUNT(v.vote_id) AS total_votes
            FROM candidates c
            JOIN departments d ON c.department_id = d.department_id
            JOIN positions p ON c.position_id = p.position_id
            LEFT JOIN votes v ON c.candidate_id = v.candidate_id
            GROUP BY d.department_id, p.position_id, c.candidate_id
            ORDER BY d.name, p.position_name, total_votes DESC, c.name";
    $res = $conn->query($sql);
    if (!$res) {
        die('Database error: ' . $conn->error);
    }

    $winners = [];
    $topScores = [];
    while ($row = $res->fetch_assoc()) {
        $key = $row['department'] . '||' . $row['position_name'];
        $votes = (int)$row['total_votes'];
        if (!isset($topScores[$key]) || $votes > $topScores[$key]) {
            $topScores[$key] = $votes;
            $winners[$key] = [$row];
        } elseif ($votes === $topScores[$key]) {
            $winners[$key][] = $row;
        }
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="election_winners.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Position', 'Candidate', 'Votes']);

    foreach ($winners as $rows) {
        foreach ($rows as $row) {
            fputcsv($output, [$row['department'], $row['position_name'], $row['candidate_name'], $row['total_votes']]);
        }
    }

    fclose($output);
    exit;
}

$sql = "SELECT d.name AS department,
               p.position_name,
               c.name AS candidate_name,
               COUNT(v.vote_id) AS total_votes
        FROM candidates c
        JOIN departments d ON c.department_id = d.department_id
        JOIN positions p ON c.position_id = p.position_id
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id
        GROUP BY d.department_id, p.position_id, c.candidate_id
        ORDER BY d.name, p.position_name, total_votes DESC, c.name";

$res = $conn->query($sql);
if (!$res) {
    die('Database error: ' . $conn->error);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="election_results.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Department', 'Position', 'Candidate', 'Votes']);

while ($row = $res->fetch_assoc()) {
    fputcsv($output, [$row['department'], $row['position_name'], $row['candidate_name'], $row['total_votes']]);
}

fclose($output);
exit;
