USE cmuenrollment_db;

INSERT INTO users (
    account_number,
    password_hash,
    role
)
VALUES (
<<<<<<< HEAD
    '202401117',
    '$2y$10$Q9mR/o2Lc7PJ8UEG.23Pw.DUMbNb/iPVQgc6c0C712nHwqC2/JEAu',
=======
    '202402222',
    '$2y$10$NyivLFe5NbqkyURcCStEz.AQI5hZwz4geZbQi4aRCxbqn0E23XlDW',
>>>>>>> 5c73036a3bbae1840659e935aea14c28ff4e5147
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
<<<<<<< HEAD
    '202401117',
    'gabriel',
    NULL,
    'milagro',
    'BSIT',
    3,
    '3D',
    'gabriel.milagro@gmail.com',
=======
    '202402222',
    'Monzy',
    NULL,
    'Hayna',
    'BSIT',
    3,
    '3D',
    'monzyhayna@gmail.com',
>>>>>>> 5c73036a3bbae1840659e935aea14c28ff4e5147
    '09123456789',
    password_hash,
    'NOT_STARTED'
FROM users
<<<<<<< HEAD
WHERE account_number = '202401117'
=======
WHERE account_number = '202402222'
>>>>>>> 5c73036a3bbae1840659e935aea14c28ff4e5147
  AND role = 'STUDENT';