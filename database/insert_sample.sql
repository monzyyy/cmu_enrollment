USE cmuenrollment_db;

-- Clear existing student records
DELETE FROM students;

-- Reset AUTO_INCREMENT
ALTER TABLE students AUTO_INCREMENT = 1;

-- Insert sample student accounts
INSERT INTO students
(
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
    enrollment_status
)
VALUES

(
    '202400745',
    'Jonathan',
    NULL,
    'Pasa',
    'BS Information Technology',
    3,
    'BSIT 3D',
    'pasajonathan4@gmail.com',
    '09216992548',
    '$2y$12$lm2MxPm56vuPy2K34LTXFul8TkQ.EXKuB0nOYYk2DE3WevD6lmiqq',
    'Closed'
),

(
    '202401207',
    'Jhosua',
    NULL,
    'Alfaro',
    'BS Information Technology',
    3,
    'BSIT 3D',
    'jhosuaalfaro28@gmail.com',
    '09753876340',
    '$2y$12$a4xpiRKGvEuoUO0AKeqg6O.7XeFf1IV3noypuTm6bXDnolobmNM4q',
    'Evaluation'
),

(
    '202400847',
    'John Romar',
    NULL,
    'Saptang',
    'BS Information Technology',
    3,
    'BSIT 3D',
    'Johnromarsaptang02@gmail.com',
    '09515295684',
    '$2y$12$BssqryuGxbzUZ90yyFpgZeXQ3eVR1S4auspwrB.o6r0kLWrU0X6Ci',
    'Clearance'
),

(
    '202400764',
    'Dennis',
    NULL,
    'Jorta',
    'BS Information Technology',
    3,
    'BSIT 3D',
    'dennissantiagojorta@gmail.com',
    '09318866752',
    '$2y$12$SwbK3T2kVm4snbYB2owD6euLERtNAOItGOtRkq260COlhFNGRNkoO',
    'COR'
),

(
    '202400924',
    'Ronald',
    NULL,
    'Pineda',
    'BS Information Technology',
    3,
    'BSIT 3D',
    'ronaldpineda642@gmail.com',
    '09122241752',
    '$2y$12$aBy8VT4RBUDlru.HbOp6uOjrO4ynuQe6Ssh2sLkWDVBwbV0NeWWdS',
    'Enrolled'
);