-- Create Database
CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- =========================
-- 1. STUDENTS TABLE
-- =========================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    reg_no VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    department VARCHAR(100),
    year_of_study INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 2. CANDIDATES TABLE
-- =========================
CREATE TABLE candidates (
    candidate_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    position VARCHAR(100) NOT NULL,
    manifesto TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);

-- =========================
-- 3. VOTES TABLE
-- =========================
CREATE TABLE votes (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id INT,
    candidate_id INT,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES students(student_id)
        ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id)
        ON DELETE CASCADE,
    UNIQUE (voter_id)  -- Prevent double voting
);

-- =========================
-- 4. AUDIT LOG TABLE
-- =========================
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    action_type VARCHAR(50),
    description TEXT,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 5. INTEGRITY TABLE
-- =========================
CREATE TABLE integrity (
    integrity_id INT PRIMARY KEY AUTO_INCREMENT,
    hash_value VARCHAR(255),
    verified BOOLEAN DEFAULT TRUE,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);