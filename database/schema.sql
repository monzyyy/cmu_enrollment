-- schema.sql
CREATE DATABASE IF NOT EXISTS cmuenrollment_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cmuenrollment_db;

CREATE TABLE IF NOT EXISTS users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('STUDENT', 'ADMIN', 'INSTRUCTOR') NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_account_number (account_number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'CLOSED',
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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

-- Default university enrollment status
INSERT INTO system_settings (setting_id, enrollment_status)
VALUES (1, 'OPEN')
ON DUPLICATE KEY UPDATE setting_id = setting_id;