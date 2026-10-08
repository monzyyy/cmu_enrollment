-- cmuenrollment_db.sql
CREATE DATABASE IF NOT EXISTS cmuenrollment_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cmuenrollment_db;


-- =========================================================
-- SYSTEM SETTINGS
-- =========================================================

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'CLOSED',
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- =========================================================
-- USERS
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('STUDENT', 'ADMIN', 'INSTRUCTOR') NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_account_number (account_number)
) ENGINE=InnoDB;


-- =========================================================
-- STUDENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS students (
    student_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NULL,
    student_number    VARCHAR(50) NOT NULL,
    first_name        VARCHAR(100) NOT NULL,
    middle_name       VARCHAR(100) NULL,
    last_name         VARCHAR(100) NOT NULL,
    program           VARCHAR(100) NOT NULL,
    year_level        INT NOT NULL,
    section           VARCHAR(50) NULL,
    email             VARCHAR(100) NOT NULL,
    phone             VARCHAR(20) NULL,
    password_hash     VARCHAR(255) NOT NULL,
    enrollment_phase  ENUM(
        'NOT_STARTED',
        'EVALUATION',
        'CLEARANCE',
        'COR',
        'ENROLLED'
    ) NOT NULL DEFAULT 'NOT_STARTED',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_students_number (student_number),
    UNIQUE KEY uq_students_email (email)
) ENGINE=InnoDB;


-- =========================================================
-- DEFAULT SYSTEM SETTING
-- =========================================================

INSERT INTO system_settings (
    setting_id,
    enrollment_status
)
VALUES (
    1,
    'OPEN'
)
ON DUPLICATE KEY UPDATE
    setting_id = setting_id;


-- =========================================================
-- SAMPLE USERS
-- =========================================================

INSERT INTO users (
    account_number,
    password_hash,
    role
)
VALUES
(
    '202400745',
    '$2y$12$lm2MxPm56vuPy2K34LTXFul8TkQ.EXKuB0nOYYk2DE3WevD6lmiqq',
    'STUDENT'
),
(
    '202401207',
    '$2y$12$a4xpiRKGvEuoU0AKeqg6O.7XeFf1IV3noypuTm6bXDnolobmNM4q',
    'STUDENT'
),
(
    '202400847',
    '$2y$12$BssqryuGxbzUZ90yyFpgZeXQ3eVR1S4auspwrB.o6r0kLWrU0X6Ci',
    'STUDENT'
),
(
    '202400764',
    '$2y$12$SwbK3T2kVm4snbYB2owD6euLERtNAOItGOtRkq260COlhFNGRNkoO',
    'STUDENT'
),
(
    '202400924',
    '$2y$12$aBy8VT4RBUDlru.HbOp6uOjrO4ynuQe6Ssh2sLkWDVBwbV0NeWWdS',
    'STUDENT'
),
(
    'ADMIN001',
    '$2y$12$lW4XC5INw7dcSYGuI0r4XOBuCxFFgB2inPzAocdUD9dUNhmXtSCRa',
    'ADMIN'
);


-- =========================================================
-- SAMPLE STUDENTS
-- =========================================================

INSERT INTO students (
    user_id,
    student_number,
    first_name,
    middle_name,
    last_name,
    program,
    year_level,
    section,
    email,
    phone,
    password_hash,
    enrollment_phase
)
SELECT
    u.user_id,
    '202400745',
    'Jonathan',
    NULL,
    'Pasa',
    'BSIT',
    3,
    NULL,
    'jonathan@example.com',
    NULL,
    u.password_hash,
    'NOT_STARTED'
FROM users u
WHERE u.account_number = '202400745'
  AND u.role = 'STUDENT';


INSERT INTO students (
    user_id,
    student_number,
    first_name,
    middle_name,
    last_name,
    program,
    year_level,
    section,
    email,
    phone,
    password_hash,
    enrollment_phase
)
SELECT
    u.user_id,
    '202401207',
    'Jhosua',
    NULL,
    'Alfaro',
    'BSIT',
    3,
    NULL,
    'jhosua@example.com',
    NULL,
    u.password_hash,
    'EVALUATION'
FROM users u
WHERE u.account_number = '202401207'
  AND u.role = 'STUDENT';


INSERT INTO students (
    user_id,
    student_number,
    first_name,
    middle_name,
    last_name,
    program,
    year_level,
    section,
    email,
    phone,
    password_hash,
    enrollment_phase
)
SELECT
    u.user_id,
    '202400847',
    'John Romar',
    NULL,
    'Saptang',
    'BSIT',
    3,
    NULL,
    'johnromar@example.com',
    NULL,
    u.password_hash,
    'CLEARANCE'
FROM users u
WHERE u.account_number = '202400847'
  AND u.role = 'STUDENT';


INSERT INTO students (
    user_id,
    student_number,
    first_name,
    middle_name,
    last_name,
    program,
    year_level,
    section,
    email,
    phone,
    password_hash,
    enrollment_phase
)
SELECT
    u.user_id,
    '202400764',
    'Dennis',
    NULL,
    'Jorta',
    'BSIT',
    3,
    NULL,
    'dennis@example.com',
    NULL,
    u.password_hash,
    'COR'
FROM users u
WHERE u.account_number = '202400764'
  AND u.role = 'STUDENT';


INSERT INTO students (
    user_id,
    student_number,
    first_name,
    middle_name,
    last_name,
    program,
    year_level,
    section,
    email,
    phone,
    password_hash,
    enrollment_phase
)
SELECT
    u.user_id,
    '202400924',
    'Ronald',
    NULL,
    'Pineda',
    'BSIT',
    3,
    NULL,
    'ronald@example.com',
    NULL,
    u.password_hash,
    'ENROLLED'
FROM users u
WHERE u.account_number = '202400924'
  AND u.role = 'STUDENT';