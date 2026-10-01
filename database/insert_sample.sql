-- =========================================================
-- CoolFreeze Airconditioning Management System
-- Sample data for `customers`
-- Run this AFTER customers_from_form.sql has created the table.
--
-- Note: the password_hash values below are all a real bcrypt
-- hash of the plain-text password "Password123" — verified to
-- work with PHP's password_verify(), useful for testing your
-- login form locally.
-- =========================================================

USE cmuenrollment_db;

INSERT INTO students (
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
VALUES ('202400924','Ronald',NULL,'Pineda','BSIT',3,'D','ronaldpineda642@gmail.com','09122241752','ronaldzz122','Enrolled'),
('202400764','Dennis',NULL,'Jorta','BSIT',3,'D','dennissantiagojorta@gmail.com','09318866752','Iamironman','Enrolled'),
('202401207','Jhosua',NULL,'Alfaro','BSIT',3,'D','jhosuaalfaro28@gmail.com','09753876340','owa0710','Enrolled');