
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- cmuenrollment_db.sql
CREATE DATABASE IF NOT EXISTS cmuenrollment_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cmuenrollment_db;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2026 at 06:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cmuenrollment_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cor_submissions`
--

CREATE TABLE `cor_submissions` (
  `cor_submission_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cor_submissions`
--

INSERT INTO `cor_submissions` (`cor_submission_id`, `student_id`, `file_name`, `file_path`, `submitted_at`, `status`, `reviewed_at`, `reviewed_by`, `rejection_reason`, `created_at`) VALUES
(2, 3, 'jpeg (1).jpg', 'uploads/cor/COR_3_1791785959_3a0a038344.jpg', '2026-10-12 14:19:19', 'APPROVED', '2026-10-12 14:20:19', 8, NULL, '2026-10-12 14:19:19');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(10) UNSIGNED NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 3,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `units`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'IT 301', 'Database System', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(2, 'IT 302', 'Web Development', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(3, 'IT 303', 'System Analysis and Design', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(4, 'IT 304', 'Networking 1', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(5, 'IT 305', 'System Integration and Architecture', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(6, 'IT 306', 'Integrative Programming', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(7, 'GE 101', 'Purposive Communication', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(8, 'PE 102', 'Physical Fitness 1', 3, 1, '2026-10-08 20:36:10', '2026-10-08 20:36:10'),
(9, 'IT 308', 'GRAPHICS DESIGN', 5, 1, '2026-10-08 20:39:30', '2026-10-08 20:40:19');

-- --------------------------------------------------------

--
-- Table structure for table `course_offerings`
--

CREATE TABLE `course_offerings` (
  `offering_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `instructor_name` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_offerings`
--

INSERT INTO `course_offerings` (`offering_id`, `course_id`, `program`, `year_level`, `section`, `semester`, `school_year`, `instructor_name`, `created_at`, `updated_at`) VALUES
(1, 7, 'BS Information Technology', 3, 'BSIT 3D', '1st Semester', '2026 - 2027', 'Prof. JIAR LASINGERO', '2026-10-08 20:41:27', '2026-10-08 20:43:23'),
(2, 7, 'BS Information Technology', 2, 'BSIT 2D', '1st Semester', '2026 - 2027', NULL, '2026-10-08 20:56:25', '2026-10-08 20:56:25'),
(3, 1, 'BS Information Technology', 2, 'BSIT 2D', '1st Semester', '2026 - 2027', NULL, '2026-10-08 20:58:51', '2026-10-08 20:58:51'),
(4, 2, 'BS Information Technology', 3, 'BSIT 3D', '1st Semester', '2026 - 2027', 'Doc. Sam', '2026-10-08 21:14:38', '2026-10-26 22:29:40'),
(5, 8, 'BS Information Technology', 3, 'BSIT 3D', '1st Semester', '2026 - 2027', NULL, '2026-10-08 21:15:24', '2026-10-08 21:15:24'),
(6, 9, 'BS Information Technology', 2, 'BSIT 2D', '1st Semester', '2026 - 2027', NULL, '2026-10-12 14:17:59', '2026-10-12 14:17:59'),
(7, 6, 'BS Information Technology', 3, 'BSIT 3D', '1st Semester', '2026 - 2027', NULL, '2026-10-20 19:16:06', '2026-10-20 19:16:06');

-- --------------------------------------------------------

--
-- Table structure for table `course_offering_schedules`
--

CREATE TABLE `course_offering_schedules` (
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `offering_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_offering_schedules`
--

INSERT INTO `course_offering_schedules` (`schedule_id`, `offering_id`, `day_of_week`, `start_time`, `end_time`, `room`, `created_at`) VALUES
(1, 1, 'Thursday', '20:45:00', '20:49:00', '202', '2026-10-08 20:44:13');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_status_history`
--

CREATE TABLE `enrollment_status_history` (
  `history_id` int(10) UNSIGNED NOT NULL,
  `status` enum('OPENED','CLOSED') NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `changed_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_status_history`
--

INSERT INTO `enrollment_status_history` (`history_id`, `status`, `changed_at`, `changed_by`) VALUES
(1, 'CLOSED', '2026-10-05 14:43:05', 8),
(2, 'OPENED', '2026-10-05 14:43:12', 8),
(3, 'CLOSED', '2026-10-05 14:43:15', 8),
(4, 'OPENED', '2026-10-05 14:43:18', 8),
(5, 'CLOSED', '2026-10-05 14:43:21', 8),
(6, 'OPENED', '2026-10-05 14:43:26', 8),
(7, 'CLOSED', '2026-10-05 14:43:30', 8),
(8, 'OPENED', '2026-10-05 14:43:46', 8),
(9, 'CLOSED', '2026-10-05 14:45:31', 8),
(10, 'OPENED', '2026-10-08 20:37:54', 8),
(11, 'CLOSED', '2026-10-12 14:14:12', 8),
(12, 'OPENED', '2026-10-12 14:14:19', 8),
(13, 'CLOSED', '2026-10-12 14:14:25', 8),
(14, 'OPENED', '2026-10-12 14:24:01', 8),
(15, 'CLOSED', '2026-10-12 14:24:06', 8),
(16, 'OPENED', '2026-10-26 15:10:54', 8),
(17, 'CLOSED', '2026-10-26 15:11:09', 8),
(18, 'OPENED', '2026-10-26 15:11:14', 8);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_questions`
--

CREATE TABLE `evaluation_questions` (
  `question_id` int(10) UNSIGNED NOT NULL,
  `section_number` tinyint(3) UNSIGNED NOT NULL,
  `section_title` varchar(150) NOT NULL,
  `question_order` tinyint(3) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `is_temporary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_questions`
--

INSERT INTO `evaluation_questions` (`question_id`, `section_number`, `section_title`, `question_order`, `question_text`, `is_temporary`, `is_active`) VALUES
(1, 1, 'Professionalism and Conduct', 1, 'Demonstrates professionalism and respect toward students.', 1, 1),
(2, 1, 'Professionalism and Conduct', 2, 'Maintains punctuality and regular attendance in scheduled classes.', 1, 1),
(3, 1, 'Professionalism and Conduct', 3, 'Treats students fairly and promotes a positive learning environment.', 1, 1),
(4, 2, 'Teaching Performance', 1, 'Demonstrates sufficient knowledge and mastery of the subject matter.', 1, 1),
(5, 2, 'Teaching Performance', 2, 'Explains lessons clearly and provides understandable examples.', 1, 1),
(6, 2, 'Teaching Performance', 3, 'Encourages student participation and critical thinking during discussions.', 1, 1),
(7, 3, 'Course Content', 1, 'Relevance and currency of course materials', 0, 1),
(8, 3, 'Course Content', 2, 'Alignment with course objectives and goals', 0, 1),
(9, 3, 'Course Content', 3, 'Integration of diverse perspectives and real-world examples', 0, 1),
(10, 4, 'Instructional Methods', 1, 'Clarity and effectiveness of presentations', 0, 1),
(11, 4, 'Instructional Methods', 2, 'Use of engaging and active learning strategies', 0, 1),
(12, 4, 'Instructional Methods', 3, 'Responsiveness to diverse learning styles and needs', 0, 1),
(13, 5, 'Assessment and Feedback', 1, 'Use of varied assessment methods (e.g., exams, papers, presentations, group work)', 0, 1),
(14, 5, 'Assessment and Feedback', 2, 'Timeliness and quality of feedback on assignments', 0, 1),
(15, 5, 'Assessment and Feedback', 3, 'Fairness and consistency in grading', 0, 1),
(16, 6, 'Course Organization and Management', 1, 'Clear and well-organized course structure (e.g., syllabus, schedule, materials)', 0, 1),
(17, 6, 'Course Organization and Management', 2, 'Effective use of class time and course resources', 0, 1),
(18, 6, 'Course Organization and Management', 3, 'Promptness and consistency in communication and announcements', 0, 1),
(19, 7, 'Faculty Accessibility and Support', 1, 'Availability and responsiveness to student inquiries (e.g., office hours, email)', 0, 1),
(20, 7, 'Faculty Accessibility and Support', 2, 'Quality of guidance and support provided to students', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_assignment_finalizations`
--

CREATE TABLE `faculty_assignment_finalizations` (
  `finalization_id` int(10) UNSIGNED NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `finalized_by` int(10) UNSIGNED DEFAULT NULL,
  `finalized_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor_evaluations`
--

CREATE TABLE `professor_evaluations` (
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `offering_id` int(10) UNSIGNED NOT NULL,
  `instructor_name_at_submission` varchar(150) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor_evaluation_answers`
--

CREATE TABLE `professor_evaluation_answers` (
  `answer_id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `enrollment_phase` enum('NOT_STARTED','EVALUATION','CLEARANCE','COR','ENROLLED') NOT NULL DEFAULT 'NOT_STARTED',
  `enrollment_status` varchar(50) NOT NULL DEFAULT 'Closed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `student_number`, `first_name`, `middle_name`, `last_name`, `program`, `year_level`, `section`, `email`, `phone`, `password_hash`, `enrollment_phase`, `enrollment_status`, `created_at`) VALUES
(1, 1, '202400745', 'Jonathan', NULL, 'Pasa', 'BS Information Technology', 3, 'BSIT 3D', 'pasajonathan4@gmail.com', '09216992548', '$2y$12$lm2MxPm56vuPy2K34LTXFul8TkQ.EXKuB0nOYYk2DE3WevD6lmiqq', 'NOT_STARTED', 'Closed', '2026-10-02 09:57:26'),
(2, 2, '202401207', 'Jhosua', '', 'Alfaro', 'BS Information Technology', 3, 'BSIT 3D', 'jhosuaalfaro28@gmail.com', '09753876340', '$2y$12$a4xpiRKGvEuoUO0AKeqg6O.7XeFf1IV3noypuTm6bXDnolobmNM4q', 'EVALUATION', 'Evaluation', '2026-10-02 09:57:26'),
(3, 3, '202400847', 'John Romar', NULL, 'Saptang', 'BS Information Technology', 3, 'BSIT 3D', 'Johnromarsaptang02@gmail.com', '09515295684', '$2y$12$BssqryuGxbzUZ90yyFpgZeXQ3eVR1S4auspwrB.o6r0kLWrU0X6Ci', 'CLEARANCE', 'Clearance', '2026-10-02 09:57:26'),
(4, 4, '202400764', 'Dennis', NULL, 'Jorta', 'BS Information Technology', 3, 'BSIT 3D', 'dennissantiagojorta@gmail.com', '09318866752', '$2y$12$SwbK3T2kVm4snbYB2owD6euLERtNAOItGOtRkq260COlhFNGRNkoO', 'COR', 'COR', '2026-10-02 09:57:26'),
(5, 5, '202400924', 'Ronald', '', 'Pineda', 'BS Information Technology', 3, 'BSIT 3D', 'ronaldpineda642@gmail.com', '09122241752', '$2y$10$dfZdEBlIA3pDL3tK8B6fieDSamg6xD5jE5z6VFJ.mKRa6dLiJbOTy', 'ENROLLED', 'Enrolled', '2026-10-02 09:57:26'),
(10, 15, '202401117', 'gabriel', NULL, 'milagro', 'BS Information Technology', 3, 'BSIT 3D', 'gabriel.milagro@gmail.com', '09123456789', '$2y$10$Q9mR/o2Lc7PJ8UEG.23Pw.DUMbNb/iPVQgc6c0C712nHwqC2/JEAu', 'NOT_STARTED', 'Closed', '2026-10-05 18:02:39'),
(12, 18, '20240529', 'Richmond', 'Aquino', 'Quizon', 'BS Information Technology', 3, 'BSIT 3D', 'richmondquizon0948@gmail.com', '09683365853', '$2y$10$QwPXttyiGvwLe0J6jkzhzOQjdTTHyBxlWy9zNs5PAv/69zStXc1ES', 'NOT_STARTED', 'Closed', '2026-10-20 20:26:42');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(10) UNSIGNED NOT NULL,
  `enrollment_status` enum('OPEN','CLOSED') NOT NULL DEFAULT 'CLOSED',
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `enrollment_status`, `semester`, `school_year`, `start_date`, `end_date`, `updated_at`) VALUES
(1, 'OPEN', '1st Semester', '2026 - 2027', '2026-09-01', '2026-10-05', '2026-10-26 15:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('STUDENT','ADMIN','INSTRUCTOR') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `account_number`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, '202400745', '$2y$12$lm2MxPm56vuPy2K34LTXFul8TkQ.EXKuB0nOYYk2DE3WevD6lmiqq', 'STUDENT', 1, '2026-10-05 14:37:29'),
(2, '202401207', '$2y$12$a4xpiRKGvEuoUO0AKeqg6O.7XeFf1IV3noypuTm6bXDnolobmNM4q', 'STUDENT', 1, '2026-10-05 14:37:29'),
(3, '202400847', '$2y$12$BssqryuGxbzUZ90yyFpgZeXQ3eVR1S4auspwrB.o6r0kLWrU0X6Ci', 'STUDENT', 1, '2026-10-05 14:37:29'),
(4, '202400764', '$2y$12$SwbK3T2kVm4snbYB2owD6euLERtNAOItGOtRkq260COlhFNGRNkoO', 'STUDENT', 1, '2026-10-05 14:37:29'),
(5, '202400924', '$2y$10$dfZdEBlIA3pDL3tK8B6fieDSamg6xD5jE5z6VFJ.mKRa6dLiJbOTy', 'STUDENT', 1, '2026-10-05 14:37:29'),
(8, 'ADMIN001', '$2y$12$lW4XC5INw7dcSYGuI0r4XOBuCxFFgB2inPzAocdUD9dUNhmXtSCRa', 'ADMIN', 1, '2026-10-05 14:41:27'),
(9, '202401112', '$2y$10$sQJBR/O/85EZbGPnKFQMq.fKza.Wkrb74PlKCUlDLBA9n3hiTYzPO', 'STUDENT', 1, '2026-10-05 17:46:22'),
(11, '202401113', '$2y$10$7VXusXb3bIs.Cl2DiUKKhOtpFgeLa3t96SLoiFp4nQwSCg1jAhATS', 'STUDENT', 1, '2026-10-05 17:50:03'),
(13, '202401115', '$2y$10$7VXusXb3bIs.Cl2DiUKKhOtpFgeLa3t96SLoiFp4nQwSCg1jAhATS', 'STUDENT', 1, '2026-10-05 17:50:49'),
(14, '202401116', '$2y$10$9WQcmunil/owg90wWY.EKuY6z83zRCS3pVzdOugc7QBYD0Uw.z/B6', 'STUDENT', 1, '2026-10-05 17:55:47'),
(17, '20242025', '$2y$10$FUWCvLCwnqbsliSbSEfyouSwTdfZkxLsx4TJKY81t9FUCoazjRzhS', 'STUDENT', 1, '2026-10-08 20:46:38'),
(18, '20240529', '$2y$10$QwPXttyiGvwLe0J6jkzhzOQjdTTHyBxlWy9zNs5PAv/69zStXc1ES', 'STUDENT', 1, '2026-10-20 20:26:42'),
(19, '202400907', '$2y$10$OvbXpaP/QJlQEVEI4l0Ws.cZyOm5FUyr7fz5mUsDNIvftLsJJjcSm', 'STUDENT', 1, '2026-10-20 21:42:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  ADD PRIMARY KEY (`cor_submission_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `uq_courses_code` (`course_code`);

--
-- Indexes for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD PRIMARY KEY (`offering_id`),
  ADD UNIQUE KEY `uq_course_offering` (`course_id`,`program`,`year_level`,`section`,`semester`,`school_year`);

--
-- Indexes for table `course_offering_schedules`
--
ALTER TABLE `course_offering_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_schedule_offering` (`offering_id`);

--
-- Indexes for table `enrollment_status_history`
--
ALTER TABLE `enrollment_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD UNIQUE KEY `uq_evaluation_question_position` (`section_number`,`question_order`);

--
-- Indexes for table `faculty_assignment_finalizations`
--
ALTER TABLE `faculty_assignment_finalizations`
  ADD PRIMARY KEY (`finalization_id`),
  ADD UNIQUE KEY `uq_finalized_section_term` (`program`,`year_level`,`section`,`semester`,`school_year`),
  ADD KEY `fk_finalized_by` (`finalized_by`);

--
-- Indexes for table `professor_evaluations`
--
ALTER TABLE `professor_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `uq_student_offering_evaluation` (`student_id`,`offering_id`),
  ADD KEY `fk_eval_offering` (`offering_id`);

--
-- Indexes for table `professor_evaluation_answers`
--
ALTER TABLE `professor_evaluation_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `uq_evaluation_answer` (`evaluation_id`,`question_id`),
  ADD KEY `fk_answer_question` (`question_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_students_number` (`student_number`),
  ADD UNIQUE KEY `uq_students_email` (`email`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_account_number` (`account_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  MODIFY `cor_submission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `course_offerings`
--
ALTER TABLE `course_offerings`
  MODIFY `offering_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course_offering_schedules`
--
ALTER TABLE `course_offering_schedules`
  MODIFY `schedule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrollment_status_history`
--
ALTER TABLE `enrollment_status_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  MODIFY `question_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `faculty_assignment_finalizations`
--
ALTER TABLE `faculty_assignment_finalizations`
  MODIFY `finalization_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professor_evaluations`
--
ALTER TABLE `professor_evaluations`
  MODIFY `evaluation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professor_evaluation_answers`
--
ALTER TABLE `professor_evaluation_answers`
  MODIFY `answer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  ADD CONSTRAINT `cor_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cor_submissions_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD CONSTRAINT `fk_offering_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_offering_schedules`
--
ALTER TABLE `course_offering_schedules`
  ADD CONSTRAINT `fk_schedule_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `enrollment_status_history`
--
ALTER TABLE `enrollment_status_history`
  ADD CONSTRAINT `enrollment_status_history_ibfk_1` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `faculty_assignment_finalizations`
--
ALTER TABLE `faculty_assignment_finalizations`
  ADD CONSTRAINT `fk_finalized_by` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `professor_evaluations`
--
ALTER TABLE `professor_evaluations`
  ADD CONSTRAINT `fk_eval_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`),
  ADD CONSTRAINT `fk_eval_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `professor_evaluation_answers`
--
ALTER TABLE `professor_evaluation_answers`
  ADD CONSTRAINT `fk_answer_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `professor_evaluations` (`evaluation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `evaluation_questions` (`question_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


CREATE TABLE IF NOT EXISTS student_course_enrollments (
    enrollment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    student_id INT UNSIGNED NOT NULL,
    offering_id INT UNSIGNED NOT NULL,

    enrollment_type ENUM('REGULAR', 'IRREGULAR')
        NOT NULL DEFAULT 'REGULAR',

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_student_offering (
        student_id,
        offering_id
    ),

    CONSTRAINT fk_sce_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_sce_offering
        FOREIGN KEY (offering_id)
        REFERENCES course_offerings(offering_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
