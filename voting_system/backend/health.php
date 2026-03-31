<?php
header('Content-Type: text/plain; charset=utf-8');

include __DIR__ . '/db.php';

if (database_ready($conn)) {
    echo "MongoDB connected\n";
    exit;
}

echo "MongoDB unavailable";
if (!empty($db_error)) {
    echo ': ' . $db_error;
}
echo "\n";
