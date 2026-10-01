CREATE DATABASE IF NOT EXISTS cmuenrollment_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cmuenrollment_db;

CREATE TABLE IF NOT EXISTS students (
    student_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_number   VARCHAR(50) NOT NULL,
    first_name       VARCHAR(100) NOT NULL,
    middle_name      VARCHAR(100) NULL,
    last_name        VARCHAR(100) NOT NULL,
    program          VARCHAR(100) NOT NULL,
    year_level       INT NOT NULL,
    section          VARCHAR(50) NULL,
    email            VARCHAR(100) NOT NULL,
    phone            VARCHAR(20) NULL,
    password_hash    VARCHAR(255) NOT NULL,
    enrollment_status VARCHAR(50) NOT NULL DEFAULT 'Closed',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uq_students_number (student_number),
    UNIQUE KEY uq_students_email (email)
) ENGINE=InnoDB;
