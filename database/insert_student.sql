USE cmuenrollment_db;

INSERT INTO users (
    account_number,
    password_hash,
    role
)
VALUES (
    '202401111',
    '$2y$10$WK/mr46L21eixD6974RXqeb6mQaTBwhqK6FJwV8Fj597bGlBKx4mm',
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
    '202401111',
    'Richmond',
    NULL,
    'Quizon',
    'BSIT',
    3,
    '3D',
    'richmond@gmail.com',
    '09123456789',
    password_hash,
    'NOT_STARTED'
FROM users
WHERE account_number = '202401111'
  AND role = 'STUDENT';