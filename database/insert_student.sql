USE cmuenrollment_db;

INSERT INTO users (
    account_number,
    password_hash,
    role
)
VALUES (
    '202401117',
    '$2y$10$Q9mR/o2Lc7PJ8UEG.23Pw.DUMbNb/iPVQgc6c0C712nHwqC2/JEAu',
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
    '202401117',
    'gabriel',
    NULL,
    'milagro',
    'BSIT',
    3,
    '3D',
    'gabriel.milagro@gmail.com',
    '09123456789',
    password_hash,
    'NOT_STARTED'
FROM users
WHERE account_number = '202401117'
  AND role = 'STUDENT';