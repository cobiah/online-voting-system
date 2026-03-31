<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: 3306;
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME') ?: 'voting_system';

// Normalize newline artifacts and null values
$pass = is_string($pass) ? trim($pass) : '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Optional vote encryption key (store in environment for production)
define('VOTE_ENCRYPTION_KEY', getenv('VOTE_ENCRYPTION_KEY') ?: 'ChangeThisSecurely2026!');

if (!function_exists('schema_table_exists')) {
    function schema_table_exists(mysqli $conn, string $table): bool {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE() AND table_name = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $table);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return $count > 0;
    }
}

if (!function_exists('schema_column_exists')) {
    function schema_column_exists(mysqli $conn, string $table, string $column): bool {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return $count > 0;
    }
}

if (!function_exists('add_column_if_missing')) {
    function add_column_if_missing(mysqli $conn, string $table, string $column, string $definition): void {
        if (!schema_column_exists($conn, $table, $column)) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN $definition");
        }
    }
}

if (!function_exists('ensure_project_schema')) {
    function ensure_project_schema(mysqli $conn): void {
        $conn->query("CREATE TABLE IF NOT EXISTS departments (
            department_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(150) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
            'Department of Procurement and Logistics'
        ];

        $insertDept = $conn->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
        if ($insertDept) {
            foreach ($departments as $name) {
                $insertDept->bind_param('s', $name);
                $insertDept->execute();
            }
            $insertDept->close();
        }

        $conn->query("CREATE TABLE IF NOT EXISTS positions (
            position_id INT PRIMARY KEY AUTO_INCREMENT,
            position_name VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $positions = [
            ['Male Delegate', 'Selected by male students in the department'],
            ['Female Delegate', 'Selected by female students in the department'],
            ['Departmental Delegate', 'Selected by all students in the department']
        ];

        $insertPos = $conn->prepare('INSERT IGNORE INTO positions (position_name, description) VALUES (?, ?)');
        if ($insertPos) {
            foreach ($positions as $position) {
                $insertPos->bind_param('ss', $position[0], $position[1]);
                $insertPos->execute();
            }
            $insertPos->close();
        }

        if (!schema_table_exists($conn, 'students')) {
            $conn->query("CREATE TABLE students (
                student_id INT PRIMARY KEY AUTO_INCREMENT,
                reg_no VARCHAR(50) UNIQUE NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                department VARCHAR(100),
                department_id INT DEFAULT NULL,
                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                year_of_study INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            add_column_if_missing($conn, 'students', 'department', "department VARCHAR(100)");
            add_column_if_missing($conn, 'students', 'department_id', "department_id INT DEFAULT NULL");
            add_column_if_missing($conn, 'students', 'is_locked', "is_locked TINYINT(1) NOT NULL DEFAULT 0");
            add_column_if_missing($conn, 'students', 'year_of_study', "year_of_study INT");
        }

        if (!schema_table_exists($conn, 'admins')) {
            $conn->query("CREATE TABLE admins (
                admin_id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!schema_table_exists($conn, 'candidates')) {
            $conn->query("CREATE TABLE candidates (
                candidate_id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT DEFAULT NULL,
                name VARCHAR(100) NOT NULL,
                position_id INT NOT NULL,
                department VARCHAR(100),
                department_id INT DEFAULT NULL,
                gender ENUM('male','female','any') NOT NULL DEFAULT 'any',
                manifesto TEXT,
                image_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
                FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            add_column_if_missing($conn, 'candidates', 'student_id', "student_id INT DEFAULT NULL");
            add_column_if_missing($conn, 'candidates', 'department', "department VARCHAR(100)");
            add_column_if_missing($conn, 'candidates', 'department_id', "department_id INT DEFAULT NULL");
            add_column_if_missing($conn, 'candidates', 'gender', "gender ENUM('male','female','any') NOT NULL DEFAULT 'any'");
            add_column_if_missing($conn, 'candidates', 'manifesto', "manifesto TEXT");
            add_column_if_missing($conn, 'candidates', 'image_url', "image_url VARCHAR(255)");
        }

        if (!schema_table_exists($conn, 'votes')) {
            $conn->query("CREATE TABLE votes (
                vote_id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT,
                candidate_id INT,
                position_id INT NOT NULL,
                encrypted_ballot TEXT,
                vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
                FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
                UNIQUE KEY unique_student_position (student_id, position_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            add_column_if_missing($conn, 'votes', 'encrypted_ballot', "encrypted_ballot TEXT");
        }

        if (!schema_table_exists($conn, 'elections')) {
            $conn->query("CREATE TABLE elections (
                election_id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(150) NOT NULL,
                description TEXT,
                start_date DATE,
                end_date DATE,
                duration_hours INT DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        add_column_if_missing($conn, 'elections', 'duration_hours', "duration_hours INT DEFAULT NULL");

        if (!schema_table_exists($conn, 'audit_log')) {
            $conn->query("CREATE TABLE audit_log (
                log_id INT PRIMARY KEY AUTO_INCREMENT,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                action VARCHAR(255),
                user_id INT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!schema_table_exists($conn, 'integrity')) {
            $conn->query("CREATE TABLE integrity (
                integrity_id INT PRIMARY KEY AUTO_INCREMENT,
                vote_id INT,
                vote_hash VARCHAR(255),
                verified BOOLEAN DEFAULT TRUE,
                checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (vote_id) REFERENCES votes(vote_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!schema_column_exists($conn, 'students', 'department_id') && schema_column_exists($conn, 'students', 'department')) {
            $conn->query("ALTER TABLE students ADD COLUMN department_id INT DEFAULT NULL");
        }
    }
}

try {
    if ($user === 'root' && $pass === '') {
        // XAMPP default root has no password
        $conn = @new mysqli($host, $user, '', $dbname, $port);
        if ($conn->connect_errno) {
            // Fallback if root actually has a password configured with env variable
            $conn = new mysqli($host, $user, $pass, $dbname, $port);
        }
    } else {
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
    }

    if ($conn->connect_errno) {
        throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
    }

    $conn->set_charset('utf8mb4');
    ensure_project_schema($conn);
    // Voting open/close is now controlled only by admin actions.
} catch (mysqli_sql_exception $e) {
    die('Database connection failed: ' . $e->getMessage() . '. Make sure MySQL user/password in backend/db.php or environment variables are correct.');
}



if (!function_exists('deactivate_expired_elections')) {
    function deactivate_expired_elections(mysqli $conn): void {
        $today = date('Y-m-d');
        $conn->query("UPDATE elections SET is_active = 0 WHERE is_active = 1 AND end_date IS NOT NULL AND end_date != '' AND end_date < '" . $conn->real_escape_string($today) . "'");
    }
}

if (!function_exists('encrypt_vote_payload')) {
    function encrypt_vote_payload(string $payload): string {
        $key = substr(hash('sha256', VOTE_ENCRYPTION_KEY, true), 0, 32);
        $iv = substr(hash('sha256', 'iv_' . VOTE_ENCRYPTION_KEY, true), 0, 16);
        $encrypted = openssl_encrypt($payload, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $encrypted === false ? '' : base64_encode($encrypted);
    }
}

if (!function_exists('decrypt_vote_payload')) {
    function decrypt_vote_payload(string $token): string {
        $key = substr(hash('sha256', VOTE_ENCRYPTION_KEY, true), 0, 32);
        $iv = substr(hash('sha256', 'iv_' . VOTE_ENCRYPTION_KEY, true), 0, 16);
        $decoded = base64_decode($token, true);
        return $decoded === false ? '' : (openssl_decrypt($decoded, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv) ?: '');
    }
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

// CSRF token helpers
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
?>