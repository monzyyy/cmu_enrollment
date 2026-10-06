USE cmuenrollment_db;

INSERT INTO users (
    account_number,
    password_hash,
    role
)
VALUES (
    '202402222',
    '$2y$10$NyivLFe5NbqkyURcCStEz.AQI5hZwz4geZbQi4aRCxbqn0E23XlDW',
    'STUDENT'
);

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
    user_id,
    '202402222',
    'Monzy',
    NULL,
    'Hayna',
    'BSIT',
    3,
    '3D',
    'monzyhayna@gmail.com',
    '09123456789',
    password_hash,
    'NOT_STARTED'
FROM users
WHERE account_number = '202402222'
  AND role = 'STUDENT';