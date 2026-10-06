-- course_offerings.sql
USE cmuenrollment_db;

-- =========================================================
-- COURSE CATALOG
-- =========================================================

CREATE TABLE IF NOT EXISTS courses (
    course_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_code     VARCHAR(50) NOT NULL,
    course_name     VARCHAR(150) NOT NULL,
    units           INT NOT NULL DEFAULT 3,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_courses_code (course_code)
) ENGINE=InnoDB;


-- =========================================================
-- COURSE OFFERINGS
-- =========================================================

CREATE TABLE IF NOT EXISTS course_offerings (
    offering_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id       INT UNSIGNED NOT NULL,
    program         VARCHAR(100) NOT NULL,
    year_level      INT NOT NULL,
    section         VARCHAR(50) NOT NULL,
    semester        VARCHAR(50) NOT NULL,
    school_year     VARCHAR(20) NOT NULL,
    instructor_name VARCHAR(150) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_offering_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    UNIQUE KEY uq_course_offering (
        course_id,
        program,
        year_level,
        section,
        semester,
        school_year
    )
) ENGINE=InnoDB;


-- =========================================================
-- COURSE OFFERING SCHEDULES
-- =========================================================

CREATE TABLE IF NOT EXISTS course_offering_schedules (
    schedule_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id     INT UNSIGNED NOT NULL,
    day_of_week     VARCHAR(20) NULL,
    start_time      TIME NULL,
    end_time        TIME NULL,
    room            VARCHAR(100) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_schedule_offering
        FOREIGN KEY (offering_id)
        REFERENCES course_offerings(offering_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- SAMPLE COURSE CATALOG
-- =========================================================

INSERT INTO courses (
    course_code,
    course_name,
    units,
    is_active
)
VALUES
(
    'IT 301',
    'Database System',
    3,
    1
),
(
    'IT 302',
    'Web Development',
    3,
    1
),
(
    'IT 303',
    'System Analysis and Design',
    3,
    1
),
(
    'IT 304',
    'Networking 1',
    3,
    1
),
(
    'IT 305',
    'System Integration and Architecture',
    3,
    1
),
(
    'IT 306',
    'Integrative Programming',
    3,
    1
),
(
    'GE 101',
    'Purposive Communication',
    3,
    1
),
(
    'PE 102',
    'Physical Fitness 1',
    3,
    1
);