-- Phase 1: Faculty evaluation tables. Import once into cmuenrollment_db.
-- Existing tables and data are not modified.
CREATE TABLE IF NOT EXISTS evaluation_questions (
 question_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 section_number TINYINT UNSIGNED NOT NULL,
 section_title VARCHAR(150) NOT NULL,
 question_order TINYINT UNSIGNED NOT NULL,
 question_text TEXT NOT NULL,
 is_temporary TINYINT(1) NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 UNIQUE KEY uq_evaluation_question_position (section_number, question_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professor_evaluations (
 evaluation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 offering_id INT UNSIGNED NOT NULL,
 instructor_name_at_submission VARCHAR(150) NOT NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_student_offering_evaluation (student_id, offering_id),
 CONSTRAINT fk_eval_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
 CONSTRAINT fk_eval_offering FOREIGN KEY (offering_id) REFERENCES course_offerings(offering_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professor_evaluation_answers (
 answer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 evaluation_id INT UNSIGNED NOT NULL,
 question_id INT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_evaluation_answer (evaluation_id, question_id),
 CONSTRAINT fk_answer_evaluation FOREIGN KEY (evaluation_id) REFERENCES professor_evaluations(evaluation_id) ON DELETE CASCADE,
 CONSTRAINT fk_answer_question FOREIGN KEY (question_id) REFERENCES evaluation_questions(question_id) ON DELETE RESTRICT,
 CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin explicitly finalizes professor assignments for a section and term.
-- Any later changes to faculty assignments must invalidate finalization in the application.
CREATE TABLE IF NOT EXISTS faculty_assignment_finalizations (
 finalization_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 program VARCHAR(100) NOT NULL,
 year_level INT NOT NULL,
 section VARCHAR(50) NOT NULL,
 semester VARCHAR(50) NOT NULL,
 school_year VARCHAR(20) NOT NULL,
 finalized_by INT UNSIGNED DEFAULT NULL,
 finalized_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_finalized_section_term (program, year_level, section, semester, school_year),
 CONSTRAINT fk_finalized_by FOREIGN KEY (finalized_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Phase 1B: Evaluation questionnaire seed. Import after Phase 1 schema.
-- Sections I-II are temporary. Sections III-VII transcribed from provided screenshots.
-- Re-running this file updates the same question positions rather than duplicating them.
INSERT INTO evaluation_questions (section_number, section_title, question_order, question_text, is_temporary, is_active) VALUES
(1,'Professionalism and Conduct',1,'Demonstrates professionalism and respect toward students.',1,1),
(1,'Professionalism and Conduct',2,'Maintains punctuality and regular attendance in scheduled classes.',1,1),
(1,'Professionalism and Conduct',3,'Treats students fairly and promotes a positive learning environment.',1,1),
(2,'Teaching Performance',1,'Demonstrates sufficient knowledge and mastery of the subject matter.',1,1),
(2,'Teaching Performance',2,'Explains lessons clearly and provides understandable examples.',1,1),
(2,'Teaching Performance',3,'Encourages student participation and critical thinking during discussions.',1,1),
(3,'Course Content',1,'Relevance and currency of course materials',0,1),
(3,'Course Content',2,'Alignment with course objectives and goals',0,1),
(3,'Course Content',3,'Integration of diverse perspectives and real-world examples',0,1),
(4,'Instructional Methods',1,'Clarity and effectiveness of presentations',0,1),
(4,'Instructional Methods',2,'Use of engaging and active learning strategies',0,1),
(4,'Instructional Methods',3,'Responsiveness to diverse learning styles and needs',0,1),
(5,'Assessment and Feedback',1,'Use of varied assessment methods (e.g., exams, papers, presentations, group work)',0,1),
(5,'Assessment and Feedback',2,'Timeliness and quality of feedback on assignments',0,1),
(5,'Assessment and Feedback',3,'Fairness and consistency in grading',0,1),
(6,'Course Organization and Management',1,'Clear and well-organized course structure (e.g., syllabus, schedule, materials)',0,1),
(6,'Course Organization and Management',2,'Effective use of class time and course resources',0,1),
(6,'Course Organization and Management',3,'Promptness and consistency in communication and announcements',0,1),
(7,'Faculty Accessibility and Support',1,'Availability and responsiveness to student inquiries (e.g., office hours, email)',0,1),
(7,'Faculty Accessibility and Support',2,'Quality of guidance and support provided to students',0,1)
ON DUPLICATE KEY UPDATE section_title=VALUES(section_title), question_text=VALUES(question_text), is_temporary=VALUES(is_temporary), is_active=VALUES(is_active);
