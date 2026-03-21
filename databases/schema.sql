-- Create Database
CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- =========================
-- 1. STUDENTS TABLE
-- =========================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    reg_no VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    year_of_study INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 2. CANDIDATES TABLE
-- =========================
CREATE TABLE candidates (
    candidate_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    manifesto TEXT
);

-- =========================
-- 3. VOTES TABLE
-- =========================
CREATE TABLE votes (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    candidate_id INT,
    position VARCHAR(100) NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id)
        ON DELETE CASCADE,
    UNIQUE (student_id, position)  -- Prevent double voting per position
);

-- =========================
-- 4. AUDIT LOG TABLE
-- =========================
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action VARCHAR(255),
    user_id INT
);

-- =========================
-- 5. INTEGRITY TABLE
-- =========================
CREATE TABLE integrity (
    integrity_id INT PRIMARY KEY AUTO_INCREMENT,
    vote_id INT,
    vote_hash VARCHAR(255),
    verified BOOLEAN DEFAULT TRUE,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(vote_id) ON DELETE CASCADE
);