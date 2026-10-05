USE cmuenrollment_db;

-- =========================================================
-- 1. Create users table
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('STUDENT', 'ADMIN', 'INSTRUCTOR') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_account_number (account_number)
) ENGINE=InnoDB;


-- =========================================================
-- 2. Add user_id to students
-- =========================================================

ALTER TABLE students
ADD COLUMN user_id INT UNSIGNED NULL AFTER student_id;


-- =========================================================
-- 3. Add enrollment_phase to students
-- =========================================================

ALTER TABLE students
ADD COLUMN enrollment_phase ENUM(
    'NOT_STARTED',
    'EVALUATION',
    'CLEARANCE',
    'COR',
    'ENROLLED'
) NOT NULL DEFAULT 'NOT_STARTED'
AFTER password_hash;


-- =========================================================
-- 4. Create student accounts in users
-- =========================================================

INSERT INTO users (
    account_number,
    password_hash,
    role
)
SELECT
    student_number,
    password_hash,
    'STUDENT'
FROM students;


-- =========================================================
-- 5. Link students to their users account
-- =========================================================

UPDATE students s
INNER JOIN users u
    ON u.account_number = s.student_number
SET s.user_id = u.user_id;


-- =========================================================
-- 6. Convert old enrollment status
--    to the new enrollment phase
-- =========================================================

UPDATE students
SET enrollment_phase =
    CASE enrollment_status
        WHEN 'Evaluation' THEN 'EVALUATION'
        WHEN 'Clearance' THEN 'CLEARANCE'
        WHEN 'COR' THEN 'COR'
        WHEN 'Enrolled' THEN 'ENROLLED'
        WHEN 'Closed' THEN 'NOT_STARTED'
        ELSE 'NOT_STARTED'
    END;


-- =========================================================
-- 7. Create system_settings
-- =========================================================

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_status ENUM('OPEN', 'CLOSED')
        NOT NULL DEFAULT 'CLOSED',
    semester VARCHAR(50) NULL,
    school_year VARCHAR(20) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- =========================================================
-- 8. Create default system settings
-- =========================================================

INSERT INTO system_settings (
    setting_id,
    enrollment_status,
    semester,
    school_year,
    start_date,
    end_date
)
VALUES (
    1,
    'OPEN',
    '1st Semester',
    '2026 - 2027',
    '2026-06-01',
    '2026-06-15'
);


-- =========================================================
-- 9. Create enrollment status history
-- =========================================================

CREATE TABLE IF NOT EXISTS enrollment_status_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status ENUM('OPENED', 'CLOSED') NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by INT UNSIGNED NULL,

    FOREIGN KEY (changed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;