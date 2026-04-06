<?php

require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/presentation.php';

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    $conn = null;
    $db_error = 'Composer dependencies are missing.';
    return;
}

require_once $autoloadPath;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Operation\FindOneAndUpdate;

define('VOTE_ENCRYPTION_KEY', getenv('VOTE_ENCRYPTION_KEY') ?: 'ChangeThisSecurely2026!');

$conn = null;
$db_error = null;

function db_now(): string
{
    return date('Y-m-d H:i:s');
}

function db_mongodb_extension_version(): string
{
    $version = phpversion('mongodb');
    return $version === false ? 'not-installed' : $version;
}

function db_validate_runtime(string $mongoUri): void
{
    if ($mongoUri === '') {
        throw new RuntimeException('MONGODB_URI is not set.');
    }

    if (!preg_match('/^mongodb(\\+srv)?:\\/\\//i', $mongoUri)) {
        throw new RuntimeException('MONGODB_URI must start with mongodb:// or mongodb+srv://.');
    }

    $extensionVersion = db_mongodb_extension_version();
    if ($extensionVersion === 'not-installed') {
        throw new RuntimeException('The PHP MongoDB extension is not installed.');
    }

    if (version_compare($extensionVersion, '2.2.0', '<')) {
        throw new RuntimeException(
            'The installed PHP MongoDB extension version ' . $extensionVersion
            . ' is too old for this project. Upgrade ext-mongodb to 2.2 or newer, or switch to a newer PHP/XAMPP runtime.'
        );
    }
}

function db(?Database $conn): ?Database
{
    return $conn;
}

function db_collection(string $name): Collection
{
    global $conn, $db_error;

    $database = db($conn);
    if (!$database) {
        throw new RuntimeException('Database is unavailable' . ($db_error ? ': ' . $db_error : '.'));
    }

    return $database->selectCollection($name);
}

function db_next_id(string $name): int
{
    $doc = db_collection('counters')->findOneAndUpdate(
        ['_id' => $name],
        ['$inc' => ['seq' => 1]],
        [
            'upsert' => true,
            'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
        ]
    );

    $data = $doc ? $doc->getArrayCopy() : [];
    return (int) ($data['seq'] ?? 1);
}

function db_to_array($document): array
{
    if (is_object($document) && method_exists($document, 'getArrayCopy')) {
        return $document->getArrayCopy();
    }

    if (is_object($document)) {
        return get_object_vars($document);
    }

    return is_array($document) ? $document : [];
}

function db_find_one(string $collection, array $filter = [], array $options = []): ?array
{
    $doc = db_collection($collection)->findOne($filter, $options);
    return $doc ? db_to_array($doc) : null;
}

function db_find_many(string $collection, array $filter = [], array $options = []): array
{
    $cursor = db_collection($collection)->find($filter, $options);
    $rows = [];
    foreach ($cursor as $document) {
        $rows[] = db_to_array($document);
    }
    return $rows;
}

function db_insert(string $collection, array $document): array
{
    db_collection($collection)->insertOne($document);
    return $document;
}

function db_update_one(string $collection, array $filter, array $update, array $options = []): int
{
    return db_collection($collection)->updateOne($filter, $update, $options)->getModifiedCount();
}

function db_update_many(string $collection, array $filter, array $update): int
{
    return db_collection($collection)->updateMany($filter, $update)->getModifiedCount();
}

function db_delete_one(string $collection, array $filter): int
{
    return db_collection($collection)->deleteOne($filter)->getDeletedCount();
}

function db_delete_many(string $collection, array $filter): int
{
    return db_collection($collection)->deleteMany($filter)->getDeletedCount();
}

function db_seed_defaults(): void
{
    $departments = [
        'Department of Education',
        'Department of Commercial Law',
        'Department of Community Health',
        'Department of Computing and Technology',
        'Department of Nursing',
        'Department of Business Administration',
        'Department of Accountancy',
        'Department of Human Resource Management',
        'Department of Public Health',
        'Department of Agriculture',
        'Department of Environmental Studies',
        'Department of Criminology',
        'Department of Social Work',
        'Department of Procurement and Logistics',
    ];

    foreach ($departments as $name) {
        if (!db_find_one('departments', ['name' => $name])) {
            db_insert('departments', [
                'department_id' => db_next_id('departments'),
                'name' => $name,
                'created_at' => db_now(),
            ]);
        }
    }

    $positions = [
        ['Male Delegate', 'Selected by male students in the department'],
        ['Female Delegate', 'Selected by female students in the department'],
        ['Departmental Delegate', 'Selected by all students in the department'],
    ];

    foreach ($positions as [$name, $description]) {
        if (!db_find_one('positions', ['position_name' => $name])) {
            db_insert('positions', [
                'position_id' => db_next_id('positions'),
                'position_name' => $name,
                'description' => $description,
                'created_at' => db_now(),
            ]);
        }
    }
}

function ensure_default_admin(): void
{
    $username = trim((string) getenv('DEFAULT_ADMIN_USERNAME'));
    $password = (string) getenv('DEFAULT_ADMIN_PASSWORD');
    $email = trim((string) getenv('DEFAULT_ADMIN_EMAIL'));

    if ($username === '' || $password === '') {
        return;
    }

    if (db_find_one('admins', ['username' => $username])) {
        return;
    }

    db_insert('admins', [
        'admin_id' => db_next_id('admins'),
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'email' => $email,
        'created_at' => db_now(),
    ]);
}

function deactivate_expired_elections(): void
{
    $today = date('Y-m-d');
    $rows = db_find_many('elections', ['is_active' => 1]);
    foreach ($rows as $row) {
        $endDate = (string) ($row['end_date'] ?? '');
        if ($endDate !== '' && $endDate < $today) {
            db_update_one('elections', ['election_id' => (int) $row['election_id']], ['$set' => ['is_active' => 0]]);
        }
    }
}

function encrypt_vote_payload(string $payload): string
{
    $key = substr(hash('sha256', VOTE_ENCRYPTION_KEY, true), 0, 32);
    $iv = substr(hash('sha256', 'iv_' . VOTE_ENCRYPTION_KEY, true), 0, 16);
    $encrypted = openssl_encrypt($payload, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $encrypted === false ? '' : base64_encode($encrypted);
}

function decrypt_vote_payload(string $token): string
{
    $key = substr(hash('sha256', VOTE_ENCRYPTION_KEY, true), 0, 32);
    $iv = substr(hash('sha256', 'iv_' . VOTE_ENCRYPTION_KEY, true), 0, 16);
    $decoded = base64_decode($token, true);
    return $decoded === false ? '' : (openssl_decrypt($decoded, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv) ?: '');
}

function log_action($conn, $action, $user_id = 0): void
{
    if (!database_ready($conn)) {
        return;
    }

    db_insert('audit_log', [
        'log_id' => db_next_id('audit_log'),
        'timestamp' => db_now(),
        'action' => (string) $action,
        'user_id' => (int) $user_id,
    ]);
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

function db_get_departments(): array
{
    $rows = db_find_many('departments');
    usort($rows, static fn(array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));
    return array_map(static fn(array $row): array => [
        'department_id' => (int) $row['department_id'],
        'name' => (string) $row['name'],
    ], $rows);
}

function db_get_positions(): array
{
    $rows = db_find_many('positions');
    usort($rows, static fn(array $a, array $b): int => strcmp((string) $a['position_name'], (string) $b['position_name']));
    return array_map(static fn(array $row): array => [
        'position_id' => (int) $row['position_id'],
        'position_name' => (string) $row['position_name'],
        'description' => (string) ($row['description'] ?? ''),
    ], $rows);
}

function db_get_department_by_id(int $departmentId): ?array
{
    return db_find_one('departments', ['department_id' => $departmentId]);
}

function db_get_students_for_candidate_form(): array
{
    $rows = db_find_many('students');
    usort($rows, static fn(array $a, array $b): int => strcmp((string) $a['full_name'], (string) $b['full_name']));
    return array_map(static fn(array $row): array => [
        'student_id' => (int) $row['student_id'],
        'reg_no' => (string) $row['reg_no'],
        'full_name' => (string) $row['full_name'],
        'department' => (string) ($row['department'] ?? ''),
    ], $rows);
}

function db_get_student_by_id(int $studentId): ?array
{
    return db_find_one('students', ['student_id' => $studentId]);
}

function db_find_student_by_email(string $email): ?array
{
    return db_find_one('students', ['email' => $email]);
}

function db_find_admin_by_username(string $username): ?array
{
    return db_find_one('admins', ['username' => $username]);
}

function db_student_exists_by_email_or_regno(string $email, string $regNo): bool
{
    return db_find_one('students', ['$or' => [['email' => $email], ['reg_no' => $regNo]]]) !== null;
}

function db_create_student(string $regNo, string $fullName, string $email, string $passwordHash, int $departmentId, string $departmentName): int
{
    $studentId = db_next_id('students');
    db_insert('students', [
        'student_id' => $studentId,
        'reg_no' => $regNo,
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'department_id' => $departmentId,
        'department' => $departmentName,
        'is_locked' => 0,
        'year_of_study' => null,
        'created_at' => db_now(),
    ]);
    return $studentId;
}

function db_count_students(): int
{
    return count(db_find_many('students'));
}

function db_count_votes(): int
{
    return count(db_find_many('votes'));
}

function db_count_votes_for_student(int $studentId): int
{
    return count(db_find_many('votes', ['student_id' => $studentId]));
}

function db_get_students_admin(): array
{
    $departments = db_get_departments();
    $departmentMap = [];
    foreach ($departments as $department) {
        $departmentMap[$department['department_id']] = $department['name'];
    }

    $voteCounts = [];
    foreach (db_find_many('votes') as $vote) {
        $studentId = (int) ($vote['student_id'] ?? 0);
        if ($studentId > 0) {
            $voteCounts[$studentId] = ($voteCounts[$studentId] ?? 0) + 1;
        }
    }

    $rows = [];
    foreach (db_find_many('students') as $student) {
        $studentId = (int) ($student['student_id'] ?? 0);
        $departmentId = (int) ($student['department_id'] ?? 0);
        $rows[] = [
            'student_id' => $studentId,
            'reg_no' => (string) ($student['reg_no'] ?? ''),
            'full_name' => (string) ($student['full_name'] ?? ''),
            'email' => (string) ($student['email'] ?? ''),
            'department' => $departmentMap[$departmentId] ?? (string) ($student['department'] ?? 'Unknown'),
            'is_locked' => (int) ($student['is_locked'] ?? 0),
            'votes_cast' => (int) ($voteCounts[$studentId] ?? 0),
            'created_at' => (string) ($student['created_at'] ?? ''),
        ];
    }

    usort($rows, static fn(array $a, array $b): int => strcmp($a['full_name'], $b['full_name']));
    return $rows;
}

function db_get_current_election(): ?array
{
    $rows = db_find_many('elections', ['is_active' => 1]);
    usort($rows, static function (array $a, array $b): int {
        return strcmp((string) ($a['start_date'] ?? ''), (string) ($b['start_date'] ?? ''));
    });
    return $rows[0] ?? null;
}

function db_get_active_home_elections(): array
{
    $rows = db_find_many('elections', ['is_active' => 1]);
    usort($rows, static function (array $a, array $b): int {
        return strcmp((string) ($a['start_date'] ?? ''), (string) ($b['start_date'] ?? ''))
            ?: strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });
    return array_map(static fn(array $row): array => [
        'election_id' => (int) $row['election_id'],
        'title' => (string) $row['title'],
        'description' => (string) ($row['description'] ?? ''),
        'start_date' => (string) ($row['start_date'] ?? ''),
        'end_date' => (string) ($row['end_date'] ?? ''),
    ], $rows);
}

function db_get_all_elections(): array
{
    $rows = db_find_many('elections');
    usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return array_map(static fn(array $row): array => [
        'election_id' => (int) $row['election_id'],
        'title' => (string) $row['title'],
        'description' => (string) ($row['description'] ?? ''),
        'start_date' => (string) ($row['start_date'] ?? ''),
        'end_date' => (string) ($row['end_date'] ?? ''),
        'duration_hours' => (int) ($row['duration_hours'] ?? 0),
        'is_active' => (int) ($row['is_active'] ?? 0),
    ], $rows);
}

function db_create_election(string $title, string $description, string $startDate, string $endDate, int $durationHours, int $isActive): int
{
    $electionId = db_next_id('elections');
    db_insert('elections', [
        'election_id' => $electionId,
        'title' => $title,
        'description' => $description,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'duration_hours' => $durationHours,
        'is_active' => $isActive,
        'created_at' => db_now(),
    ]);
    return $electionId;
}

function db_get_election_by_id(int $electionId): ?array
{
    return db_find_one('elections', ['election_id' => $electionId]);
}

function db_update_election_state(int $electionId, array $fields): int
{
    return db_update_one('elections', ['election_id' => $electionId], ['$set' => $fields]);
}

function db_start_all_elections(string $today): void
{
    $rows = db_find_many('elections');
    foreach ($rows as $row) {
        $startDate = (string) ($row['start_date'] ?? '');
        if ($startDate === '') {
            $startDate = $today;
        }

        $update = [
            'is_active' => 1,
            'start_date' => $startDate,
        ];

        $duration = (int) ($row['duration_hours'] ?? 0);
        if ($duration > 0) {
            $update['end_date'] = date('Y-m-d', strtotime($startDate . ' + ' . $duration . ' hours'));
        }

        db_update_election_state((int) $row['election_id'], $update);
    }
}

function db_stop_all_elections(): void
{
    db_update_many('elections', [], ['$set' => ['is_active' => 0]]);
}

function db_find_position_by_name(string $positionName): ?array
{
    return db_find_one('positions', ['position_name' => $positionName]);
}

function db_create_position(string $positionName, string $description): int
{
    $positionId = db_next_id('positions');
    db_insert('positions', [
        'position_id' => $positionId,
        'position_name' => $positionName,
        'description' => $description,
        'created_at' => db_now(),
    ]);
    return $positionId;
}

function db_delete_position(int $positionId): int
{
    $candidates = db_find_many('candidates', ['position_id' => $positionId]);
    $candidateIds = array_map(static fn(array $row): int => (int) $row['candidate_id'], $candidates);

    $votes = db_find_many('votes', ['position_id' => $positionId]);
    $voteIds = array_map(static fn(array $row): int => (int) $row['vote_id'], $votes);

    if (!empty($candidateIds)) {
        db_delete_many('candidates', ['position_id' => $positionId]);
    }

    if (!empty($voteIds)) {
        db_delete_many('integrity', ['vote_id' => ['$in' => $voteIds]]);
        db_delete_many('votes', ['position_id' => $positionId]);
    }

    return db_delete_one('positions', ['position_id' => $positionId]);
}

function db_find_candidate_by_student_position(int $studentId, int $positionId): ?array
{
    return db_find_one('candidates', ['student_id' => $studentId, 'position_id' => $positionId]);
}

function db_create_candidate(?int $studentId, string $name, int $positionId, ?int $departmentId, string $department, string $gender, string $manifesto, string $imageUrl): int
{
    $candidateId = db_next_id('candidates');
    db_insert('candidates', [
        'candidate_id' => $candidateId,
        'student_id' => $studentId,
        'name' => $name,
        'position_id' => $positionId,
        'department_id' => $departmentId,
        'department' => $department,
        'gender' => $gender,
        'manifesto' => $manifesto,
        'image_url' => $imageUrl,
        'created_at' => db_now(),
    ]);
    return $candidateId;
}

function db_delete_candidate(int $candidateId): int
{
    $votes = db_find_many('votes', ['candidate_id' => $candidateId]);
    $voteIds = array_map(static fn(array $row): int => (int) $row['vote_id'], $votes);
    if (!empty($voteIds)) {
        db_delete_many('integrity', ['vote_id' => ['$in' => $voteIds]]);
        db_delete_many('votes', ['candidate_id' => $candidateId]);
    }
    return db_delete_one('candidates', ['candidate_id' => $candidateId]);
}

function db_get_candidates_admin(): array
{
    $positions = db_get_positions();
    $departments = db_get_departments();
    $positionMap = [];
    foreach ($positions as $position) {
        $positionMap[$position['position_id']] = $position['position_name'];
    }
    $departmentMap = [];
    foreach ($departments as $department) {
        $departmentMap[$department['department_id']] = $department['name'];
    }

    $rows = db_find_many('candidates');
    $mapped = array_map(static function (array $row) use ($positionMap, $departmentMap): array {
        $departmentId = (int) ($row['department_id'] ?? 0);
        return [
            'candidate_id' => (int) $row['candidate_id'],
            'name' => (string) $row['name'],
            'position_name' => $positionMap[(int) $row['position_id']] ?? '',
            'department' => $departmentMap[$departmentId] ?? (string) ($row['department'] ?? ''),
            'gender' => (string) ($row['gender'] ?? 'any'),
            'image_url' => (string) ($row['image_url'] ?? ''),
            'manifesto' => (string) ($row['manifesto'] ?? ''),
        ];
    }, $rows);

    usort($mapped, static fn(array $a, array $b): int => strcmp($a['position_name'], $b['position_name']) ?: strcmp($a['name'], $b['name']));
    return $mapped;
}

function db_get_candidates_for_department(?int $departmentId, string $department): array
{
    if ($departmentId) {
        $rows = db_find_many('candidates', ['department_id' => $departmentId]);
    } elseif ($department !== '') {
        $rows = db_find_many('candidates', ['department' => $department]);
    } else {
        $rows = db_find_many('candidates');
    }

    usort($rows, static fn(array $a, array $b): int => ((int) $a['position_id'] <=> (int) $b['position_id']) ?: strcmp((string) $a['name'], (string) $b['name']));

    return array_map(static fn(array $row): array => [
        'candidate_id' => (int) $row['candidate_id'],
        'name' => (string) $row['name'],
        'position_id' => (int) $row['position_id'],
        'image_url' => (string) ($row['image_url'] ?? ''),
        'manifesto' => (string) ($row['manifesto'] ?? ''),
    ], $rows);
}

function db_find_candidate_for_position(int $candidateId, int $positionId): ?array
{
    return db_find_one('candidates', ['candidate_id' => $candidateId, 'position_id' => $positionId]);
}

function db_find_vote_for_student_position(int $studentId, int $positionId): ?array
{
    return db_find_one('votes', ['student_id' => $studentId, 'position_id' => $positionId]);
}

function db_create_vote(int $studentId, int $candidateId, int $positionId, string $encryptedBallot): int
{
    $voteId = db_next_id('votes');
    db_insert('votes', [
        'vote_id' => $voteId,
        'student_id' => $studentId,
        'candidate_id' => $candidateId,
        'position_id' => $positionId,
        'encrypted_ballot' => $encryptedBallot,
        'vote_time' => db_now(),
    ]);
    return $voteId;
}

function db_create_integrity(int $voteId, string $voteHash, bool $verified = true): int
{
    $integrityId = db_next_id('integrity');
    db_insert('integrity', [
        'integrity_id' => $integrityId,
        'vote_id' => $voteId,
        'vote_hash' => $voteHash,
        'verified' => $verified ? 1 : 0,
        'checked_at' => db_now(),
    ]);
    return $integrityId;
}

function db_get_public_results(): array
{
    $positions = db_get_positions();
    $positionMap = [];
    foreach ($positions as $position) {
        $positionMap[$position['position_id']] = $position['position_name'];
    }

    $votes = db_find_many('votes');
    $voteTotals = [];
    foreach ($votes as $vote) {
        $candidateId = (int) $vote['candidate_id'];
        $voteTotals[$candidateId] = ($voteTotals[$candidateId] ?? 0) + 1;
    }

    $rows = [];
    foreach (db_find_many('candidates') as $candidate) {
        $rows[] = [
            'candidate_id' => (int) $candidate['candidate_id'],
            'name' => (string) $candidate['name'],
            'image_url' => (string) ($candidate['image_url'] ?? ''),
            'position_name' => $positionMap[(int) $candidate['position_id']] ?? '',
            'total_votes' => (int) ($voteTotals[(int) $candidate['candidate_id']] ?? 0),
        ];
    }

    usort($rows, static fn(array $a, array $b): int => strcmp($a['position_name'], $b['position_name']) ?: ($b['total_votes'] <=> $a['total_votes']) ?: strcmp($a['name'], $b['name']));
    return $rows;
}

function db_get_admin_results_flat(): array
{
    $departments = db_get_departments();
    $positions = db_get_positions();
    $departmentMap = [];
    $positionMap = [];

    foreach ($departments as $department) {
        $departmentMap[$department['department_id']] = $department['name'];
    }

    foreach ($positions as $position) {
        $positionMap[$position['position_id']] = $position['position_name'];
    }

    $voteTotals = [];
    foreach (db_find_many('votes') as $vote) {
        $candidateId = (int) ($vote['candidate_id'] ?? 0);
        if ($candidateId > 0) {
            $voteTotals[$candidateId] = ($voteTotals[$candidateId] ?? 0) + 1;
        }
    }

    $rows = [];
    foreach (db_find_many('candidates') as $candidate) {
        $departmentId = (int) ($candidate['department_id'] ?? 0);
        $positionId = (int) ($candidate['position_id'] ?? 0);
        $candidateId = (int) ($candidate['candidate_id'] ?? 0);
        $rows[] = [
            'department_id' => $departmentId,
            'department' => $departmentMap[$departmentId] ?? (string) ($candidate['department'] ?? 'Unknown'),
            'position_id' => $positionId,
            'position_name' => $positionMap[$positionId] ?? '',
            'candidate_id' => $candidateId,
            'candidate_name' => (string) ($candidate['name'] ?? ''),
            'total_votes' => (int) ($voteTotals[$candidateId] ?? 0),
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        return strcmp($a['department'], $b['department'])
            ?: strcmp($a['position_name'], $b['position_name'])
            ?: ($b['total_votes'] <=> $a['total_votes'])
            ?: strcmp($a['candidate_name'], $b['candidate_name']);
    });

    return $rows;
}

function db_get_admin_winners_flat(): array
{
    $rows = db_get_admin_results_flat();
    $winners = [];
    $topScores = [];

    foreach ($rows as $row) {
        $key = $row['department'] . '||' . $row['position_name'];
        $votes = (int) $row['total_votes'];
        if (!isset($topScores[$key]) || $votes > $topScores[$key]) {
            $topScores[$key] = $votes;
            $winners[$key] = [$row];
        } elseif ($votes === $topScores[$key]) {
            $winners[$key][] = $row;
        }
    }

    $flat = [];
    foreach ($winners as $winnerRows) {
        foreach ($winnerRows as $winner) {
            $flat[] = $winner;
        }
    }

    return $flat;
}

function db_get_audit_logs(int $limit = 200): array
{
    $rows = db_find_many('audit_log');
    usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? '')));
    $rows = array_slice($rows, 0, max(0, $limit));

    return array_map(static fn(array $row): array => [
        'timestamp' => (string) ($row['timestamp'] ?? ''),
        'action' => (string) ($row['action'] ?? ''),
        'user_id' => (int) ($row['user_id'] ?? 0),
    ], $rows);
}

function db_delete_integrity_for_vote_ids(array $voteIds): void
{
    $voteIds = array_values(array_filter(array_map('intval', $voteIds), static fn(int $id): bool => $id > 0));
    if ($voteIds !== []) {
        db_delete_many('integrity', ['vote_id' => ['$in' => $voteIds]]);
    }
}

function db_delete_votes_by_filter(array $filter): int
{
    $votes = db_find_many('votes', $filter);
    $voteIds = array_map(static fn(array $row): int => (int) ($row['vote_id'] ?? 0), $votes);
    db_delete_integrity_for_vote_ids($voteIds);
    return $votes === [] ? 0 : db_delete_many('votes', $filter);
}

function db_clear_all_votes(): int
{
    $deleted = count(db_find_many('votes'));
    if ($deleted > 0) {
        db_delete_many('votes', []);
    }
    db_delete_many('integrity', []);
    return $deleted;
}

function db_set_student_lock(int $studentId, bool $locked): int
{
    return db_update_one('students', ['student_id' => $studentId], ['$set' => ['is_locked' => $locked ? 1 : 0]]);
}

function db_reset_student_votes(int $studentId): int
{
    return db_delete_votes_by_filter(['student_id' => $studentId]);
}

function db_get_tamper_issues(): array
{
    $integrityRows = db_find_many('integrity');
    $issues = [];

    foreach ($integrityRows as $integrity) {
        $voteId = (int) ($integrity['vote_id'] ?? 0);
        if ($voteId <= 0) {
            continue;
        }

        $vote = db_find_one('votes', ['vote_id' => $voteId]);
        if (!$vote) {
            $issues[] = [
                'vote_id' => $voteId,
                'reason' => 'Integrity record exists without a matching vote.',
            ];
            continue;
        }

        $expectedHash = hash(
            'sha256',
            (int) $vote['student_id'] . '-' . (int) $vote['position_id'] . '-' . (int) $vote['candidate_id'] . '-' . (int) $vote['vote_id']
        );

        $storedHash = (string) ($integrity['vote_hash'] ?? '');
        if ($storedHash === '' || !hash_equals($expectedHash, $storedHash)) {
            $issues[] = [
                'vote_id' => $voteId,
                'reason' => 'Vote hash does not match the stored vote contents.',
            ];
        }
    }

    usort($issues, static fn(array $a, array $b): int => ((int) $a['vote_id']) <=> ((int) $b['vote_id']));
    return $issues;
}

try {
    $isRender = filter_var(getenv('RENDER') ?: false, FILTER_VALIDATE_BOOLEAN);
    $mongoUri = trim((string) getenv('MONGODB_URI'));
    $mongoDbName = trim((string) (getenv('MONGODB_DB') ?: 'voting_system'));

    if ($mongoUri === '' && !$isRender) {
        $mongoUri = 'mongodb://localhost:27017';
    }
    db_validate_runtime($mongoUri);

    $client = new Client($mongoUri);
    $client->selectDatabase('admin')->command(['ping' => 1]);
    $conn = $client->selectDatabase($mongoDbName);

    db_seed_defaults();
    ensure_default_admin();
    deactivate_expired_elections();
} catch (Throwable $e) {
    $conn = null;
    $db_error = $e->getMessage();
}
