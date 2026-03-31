-- Create Database
CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- =========================
-- 1. DEPARTMENTS TABLE
-- =========================
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name) VALUES
('Department of Education'),
('Department of Commercial Law'),
('Department of Community Health'),
('Department of Computing and Technology'),
('Department of Nursing'),
('Department of Business Administration'),
('Department of Accountancy'),
('Department of Human Resource Management'),
('Department of Public Health'),
('Department of Agriculture'),
('Department of Environmental Studies'),
('Department of Criminology'),
('Department of Social Work'),
('Department of Procurement and Logistics');

-- =========================
-- 2. STUDENTS TABLE
-- =========================
CREATE TABLE students (
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
);

-- =========================
-- 2.5. ADMINS TABLE
-- =========================
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 3. POSITIONS TABLE
-- =========================
CREATE TABLE positions (
    position_id INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO positions (position_name, description) VALUES
('Male Delegate', 'Selected by male students in the department'),
('Female Delegate', 'Selected by female students in the department'),
('Departmental Delegate', 'Selected by all students in the department');

-- =========================
-- 4. CANDIDATES TABLE
-- =========================
CREATE TABLE candidates (
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
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
);

-- =========================
-- 5. VOTES TABLE
-- =========================
CREATE TABLE votes (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    candidate_id INT,
    position_id INT NOT NULL,
    encrypted_ballot TEXT,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id)
        ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(position_id)
        ON DELETE CASCADE,
    UNIQUE (student_id, position_id)  -- Prevent double voting per position
);

-- =========================
-- 6. AUDIT LOG TABLE
-- =========================
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action VARCHAR(255),
    user_id INT
);

-- =========================
-- 7. INTEGRITY TABLE
-- =========================
CREATE TABLE integrity (
    integrity_id INT PRIMARY KEY AUTO_INCREMENT,
    vote_id INT,
    vote_hash VARCHAR(255),
    verified BOOLEAN DEFAULT TRUE,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(vote_id) ON DELETE CASCADE
);