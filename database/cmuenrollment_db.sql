-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 08:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cmuenrollment_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `clearance_submissions`
--

CREATE TABLE `clearance_submissions` (
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cor_submissions`
--

CREATE TABLE `cor_submissions` (
  `cor_submission_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(9, 'IT 308', 'Graphics Design', 5, 1, '2026-10-08 20:39:30', '2026-11-05 11:40:25'),
(10, 'GE6', 'ART APPRECIATION', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(11, 'GE7', 'SCIENCE, TECHNOLOGY & SOCIETY', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(12, 'GE8', 'ETHICS', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(13, 'IM101', 'ADVANCED DATABASE SYSTEM', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(14, 'IPT101', 'INTEGRATIVE PROGRAMMING AND TECHNOLOGIES 1', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(15, 'MS102', 'QUANTITATIVE METHODS', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(16, 'NET102', 'NETWORKING 2', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34'),
(17, 'SIA101', 'SYSTEM INTEGRATION AND ARCHITECTURE 1', 3, 1, '2026-11-05 12:35:34', '2026-11-05 12:35:34');

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
(12, 10, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'Prof. Arsenio Ranay', '2026-11-05 12:35:34', '2026-11-05 23:26:19'),
(13, 11, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'Prof.  Rich Caranguian', '2026-11-05 12:35:34', '2026-11-05 23:26:28'),
(14, 12, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'Prof. Greg Roslin', '2026-11-05 12:35:34', '2026-11-05 23:24:52'),
(15, 13, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'CERWIN JOHN ZAULDA', '2026-11-05 12:35:34', '2026-11-05 20:48:36'),
(16, 14, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'Gian Carlo Cruz', '2026-11-05 12:35:34', '2026-11-05 20:48:36'),
(17, 15, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'Ronaldo Jaime', '2026-11-05 12:35:34', '2026-11-05 20:48:36'),
(18, 16, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'JED CONCEPCION', '2026-11-05 12:35:34', '2026-11-05 20:48:36'),
(19, 17, 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026 - 2027', 'CERWIN JOHN ZAULDA', '2026-11-05 12:35:34', '2026-11-05 20:48:36'),
(20, 1, 'Bachelor of Science in Information Technology', 3, 'D', '2nd Semester', '2026 - 2027', 'Prof. Ronald Pinedaks', '2026-11-06 00:01:27', '2026-11-06 00:01:27'),
(21, 2, 'Bachelor of Science in Information Technology', 3, 'D', '2nd Semester', '2026 - 2027', 'Prof. Jhosua Pasa', '2026-11-06 00:06:02', '2026-11-06 00:06:02');

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
(3, 20, 'Monday', '10:00:00', '13:00:00', 'Room 400', '2026-11-06 00:01:27'),
(4, 21, 'Friday', '07:00:00', '10:00:00', 'Room 300', '2026-11-06 00:06:02');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_periods`
--

CREATE TABLE `enrollment_periods` (
  `period_id` int(10) UNSIGNED NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('OPEN','CLOSED') NOT NULL DEFAULT 'CLOSED',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_periods`
--

INSERT INTO `enrollment_periods` (`period_id`, `semester`, `school_year`, `start_date`, `end_date`, `status`, `created_at`, `closed_at`) VALUES
(1, '1st Semester', '2026 - 2027', '2026-09-01', '2026-10-05', 'CLOSED', '2026-11-05 00:13:28', '2026-11-05 01:02:21'),
(2, '2nd Semester', '2026 - 2027', '2026-11-05', '2026-11-15', 'CLOSED', '2026-11-05 01:16:45', '2026-11-06 14:21:44');

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
(23, 'CLOSED', '2026-11-05 11:54:36', 8),
(24, 'OPENED', '2026-11-05 12:34:32', 8),
(25, 'CLOSED', '2026-11-05 12:34:48', 8),
(26, 'OPENED', '2026-11-05 23:30:36', 8),
(27, 'CLOSED', '2026-11-05 23:31:11', 8),
(28, 'OPENED', '2026-11-06 00:07:47', 8),
(29, 'CLOSED', '2026-11-06 01:02:29', 8),
(30, 'OPENED', '2026-11-06 01:07:43', 8),
(31, 'CLOSED', '2026-11-06 14:21:44', 8);

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

--
-- Dumping data for table `professor_evaluations`
--

INSERT INTO `professor_evaluations` (`evaluation_id`, `student_id`, `offering_id`, `instructor_name_at_submission`, `submitted_at`) VALUES
(21, 17, 12, 'Prof. Arsenio Ranay', '2026-11-06 11:51:39'),
(22, 17, 13, 'Prof.  Rich Caranguian', '2026-11-06 11:52:23'),
(23, 17, 14, 'Prof. Greg Roslin', '2026-11-06 11:53:09'),
(24, 17, 15, 'CERWIN JOHN ZAULDA', '2026-11-06 11:54:18'),
(25, 17, 16, 'Gian Carlo Cruz', '2026-11-06 11:55:08'),
(26, 17, 17, 'Ronaldo Jaime', '2026-11-06 11:55:55'),
(27, 17, 18, 'JED CONCEPCION', '2026-11-06 11:56:45'),
(28, 17, 19, 'CERWIN JOHN ZAULDA', '2026-11-06 11:57:45');

-- --------------------------------------------------------

--
-- Table structure for table `professor_evaluation_answers`
--

CREATE TABLE `professor_evaluation_answers` (
  `answer_id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_evaluation_answers`
--

INSERT INTO `professor_evaluation_answers` (`answer_id`, `evaluation_id`, `question_id`, `rating`) VALUES
(401, 21, 1, 5),
(402, 21, 2, 5),
(403, 21, 3, 5),
(404, 21, 4, 5),
(405, 21, 5, 5),
(406, 21, 6, 5),
(407, 21, 7, 5),
(408, 21, 8, 5),
(409, 21, 9, 5),
(410, 21, 10, 5),
(411, 21, 11, 5),
(412, 21, 12, 5),
(413, 21, 13, 5),
(414, 21, 14, 5),
(415, 21, 15, 5),
(416, 21, 16, 5),
(417, 21, 17, 5),
(418, 21, 18, 5),
(419, 21, 19, 5),
(420, 21, 20, 5),
(421, 22, 1, 5),
(422, 22, 2, 5),
(423, 22, 3, 5),
(424, 22, 4, 5),
(425, 22, 5, 5),
(426, 22, 6, 5),
(427, 22, 7, 5),
(428, 22, 8, 5),
(429, 22, 9, 5),
(430, 22, 10, 5),
(431, 22, 11, 5),
(432, 22, 12, 5),
(433, 22, 13, 5),
(434, 22, 14, 5),
(435, 22, 15, 5),
(436, 22, 16, 5),
(437, 22, 17, 5),
(438, 22, 18, 5),
(439, 22, 19, 5),
(440, 22, 20, 5),
(441, 23, 1, 5),
(442, 23, 2, 5),
(443, 23, 3, 5),
(444, 23, 4, 5),
(445, 23, 5, 5),
(446, 23, 6, 5),
(447, 23, 7, 5),
(448, 23, 8, 5),
(449, 23, 9, 5),
(450, 23, 10, 5),
(451, 23, 11, 5),
(452, 23, 12, 5),
(453, 23, 13, 5),
(454, 23, 14, 5),
(455, 23, 15, 5),
(456, 23, 16, 5),
(457, 23, 17, 5),
(458, 23, 18, 5),
(459, 23, 19, 5),
(460, 23, 20, 5),
(461, 24, 1, 5),
(462, 24, 2, 5),
(463, 24, 3, 5),
(464, 24, 4, 5),
(465, 24, 5, 5),
(466, 24, 6, 5),
(467, 24, 7, 5),
(468, 24, 8, 5),
(469, 24, 9, 5),
(470, 24, 10, 5),
(471, 24, 11, 5),
(472, 24, 12, 5),
(473, 24, 13, 5),
(474, 24, 14, 5),
(475, 24, 15, 5),
(476, 24, 16, 5),
(477, 24, 17, 5),
(478, 24, 18, 5),
(479, 24, 19, 5),
(480, 24, 20, 5),
(481, 25, 1, 5),
(482, 25, 2, 5),
(483, 25, 3, 5),
(484, 25, 4, 5),
(485, 25, 5, 5),
(486, 25, 6, 5),
(487, 25, 7, 5),
(488, 25, 8, 5),
(489, 25, 9, 5),
(490, 25, 10, 5),
(491, 25, 11, 5),
(492, 25, 12, 5),
(493, 25, 13, 5),
(494, 25, 14, 5),
(495, 25, 15, 5),
(496, 25, 16, 5),
(497, 25, 17, 5),
(498, 25, 18, 5),
(499, 25, 19, 5),
(500, 25, 20, 5),
(501, 26, 1, 5),
(502, 26, 2, 5),
(503, 26, 3, 5),
(504, 26, 4, 5),
(505, 26, 5, 5),
(506, 26, 6, 5),
(507, 26, 7, 5),
(508, 26, 8, 5),
(509, 26, 9, 5),
(510, 26, 10, 5),
(511, 26, 11, 5),
(512, 26, 12, 5),
(513, 26, 13, 5),
(514, 26, 14, 5),
(515, 26, 15, 5),
(516, 26, 16, 5),
(517, 26, 17, 5),
(518, 26, 18, 5),
(519, 26, 19, 5),
(520, 26, 20, 5),
(521, 27, 1, 5),
(522, 27, 2, 5),
(523, 27, 3, 5),
(524, 27, 4, 5),
(525, 27, 5, 5),
(526, 27, 6, 5),
(527, 27, 7, 5),
(528, 27, 8, 5),
(529, 27, 9, 5),
(530, 27, 10, 5),
(531, 27, 11, 5),
(532, 27, 12, 5),
(533, 27, 13, 5),
(534, 27, 14, 5),
(535, 27, 15, 5),
(536, 27, 16, 5),
(537, 27, 17, 5),
(538, 27, 18, 5),
(539, 27, 19, 5),
(540, 27, 20, 5),
(541, 28, 1, 5),
(542, 28, 2, 5),
(543, 28, 3, 5),
(544, 28, 4, 5),
(545, 28, 5, 5),
(546, 28, 6, 5),
(547, 28, 7, 5),
(548, 28, 8, 5),
(549, 28, 9, 5),
(550, 28, 10, 5),
(551, 28, 11, 5),
(552, 28, 12, 5),
(553, 28, 13, 5),
(554, 28, 14, 5),
(555, 28, 15, 5),
(556, 28, 16, 5),
(557, 28, 17, 5),
(558, 28, 18, 5),
(559, 28, 19, 5),
(560, 28, 20, 5);

-- --------------------------------------------------------

--
-- Table structure for table `registration_verifications`
--

CREATE TABLE `registration_verifications` (
  `verification_id` bigint(20) UNSIGNED NOT NULL,
  `masterlist_id` int(10) UNSIGNED NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `send_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `last_sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(14, 20, '20240529', 'Richmond', 'Aquino', 'Quizon', 'Bachelor of Science in Information Technology', 3, 'D', 'richmondquizon0948@gmail.com', '09683365853', '$2y$10$3O8ZxThF/cq1kA.hBgqh/.fDuQ2NnY5EwRIO295x/kqK8Z0BcrQXa', 'NOT_STARTED', 'Closed', '2026-11-05 11:51:38'),
(15, 21, '202400764', 'Dennis', '', 'Jorta', 'Bachelor of Science in Information Technology', 3, 'D', 'dennissantiagojorta@gmail.com', '09318866752', '$2y$10$8x2VLMRovE1nPr9ofcXT4ubxW0xxxC38QPLWp4dZimGiD/2wiGkAS', 'NOT_STARTED', 'Closed', '2026-11-05 12:38:02'),
(16, 22, '202400847', 'John Romar', '', 'Saptang', 'Bachelor of Science in Information Technology', 3, 'D', 'johnromarsaptang02@gmail.com', '09515295684', '$2y$10$Ru5HH.euldk2ddYuE0buK./dELSwrsewaaLRQoBAVkuld3KvapjB2', 'NOT_STARTED', 'Closed', '2026-11-05 12:38:47'),
(17, 23, '202401207', 'Jhosua', '', 'Alfaro', 'Bachelor of Science in Information Technology', 3, 'D', 'jhosuaalfaro28@gmail.com', '09753876340', '$2y$10$oOfIFHlickuf0M2.22VX1eHq4x0Sd//CzdrBmCgOIkbLddhrYNENS', 'EVALUATION', 'Evaluation', '2026-11-05 12:39:20'),
(18, 24, '202400745', 'Jonathan', '', 'Pasa', 'Bachelor of Science in Information Technology', 3, 'D', 'pasajonathan4@gmail.com', '09216992548', '$2y$10$EWnxlM.lQwKxAisabM/8dOOTr.AEByxRaFlxug2a5P0GSb.myLKJm', 'NOT_STARTED', 'Closed', '2026-11-05 12:40:09'),
(19, 25, '202400924', 'Ronald', '', 'Pineda', 'Bachelor of Science in Information Technology', 3, 'D', 'ronaldpineda642@gmail.com', '09122241752', '$2y$10$DT1P4apIl0lnzILeSDLyuenTuGXngWES.efyAC5PqZyhl5wgdpJoy', 'NOT_STARTED', 'Closed', '2026-11-05 12:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `student_course_enrollments`
--

CREATE TABLE `student_course_enrollments` (
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `offering_id` int(10) UNSIGNED NOT NULL,
  `enrollment_type` enum('REGULAR','IRREGULAR') NOT NULL DEFAULT 'REGULAR',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_course_enrollments`
--

INSERT INTO `student_course_enrollments` (`enrollment_id`, `student_id`, `offering_id`, `enrollment_type`, `created_at`) VALUES
(59, 14, 12, 'REGULAR', '2026-11-05 12:35:34'),
(60, 14, 13, 'REGULAR', '2026-11-05 12:35:34'),
(61, 14, 14, 'REGULAR', '2026-11-05 12:35:34'),
(62, 14, 15, 'REGULAR', '2026-11-05 12:35:34'),
(63, 14, 16, 'REGULAR', '2026-11-05 12:35:34'),
(64, 14, 17, 'REGULAR', '2026-11-05 12:35:34'),
(65, 14, 18, 'REGULAR', '2026-11-05 12:35:34'),
(66, 14, 19, 'REGULAR', '2026-11-05 12:35:34'),
(67, 15, 12, 'REGULAR', '2026-11-05 12:59:07'),
(68, 16, 12, 'REGULAR', '2026-11-05 12:59:07'),
(69, 17, 12, 'REGULAR', '2026-11-05 12:59:07'),
(70, 18, 12, 'REGULAR', '2026-11-05 12:59:07'),
(71, 19, 12, 'REGULAR', '2026-11-05 12:59:07'),
(72, 15, 13, 'REGULAR', '2026-11-05 12:59:07'),
(73, 16, 13, 'REGULAR', '2026-11-05 12:59:07'),
(74, 17, 13, 'REGULAR', '2026-11-05 12:59:07'),
(75, 18, 13, 'REGULAR', '2026-11-05 12:59:07'),
(76, 19, 13, 'REGULAR', '2026-11-05 12:59:07'),
(77, 15, 14, 'REGULAR', '2026-11-05 12:59:07'),
(78, 16, 14, 'REGULAR', '2026-11-05 12:59:07'),
(79, 17, 14, 'REGULAR', '2026-11-05 12:59:07'),
(80, 18, 14, 'REGULAR', '2026-11-05 12:59:07'),
(81, 19, 14, 'REGULAR', '2026-11-05 12:59:07'),
(82, 15, 15, 'REGULAR', '2026-11-05 12:59:07'),
(83, 16, 15, 'REGULAR', '2026-11-05 12:59:07'),
(84, 17, 15, 'REGULAR', '2026-11-05 12:59:07'),
(85, 18, 15, 'REGULAR', '2026-11-05 12:59:07'),
(86, 19, 15, 'REGULAR', '2026-11-05 12:59:07'),
(87, 15, 16, 'REGULAR', '2026-11-05 12:59:07'),
(88, 16, 16, 'REGULAR', '2026-11-05 12:59:07'),
(89, 17, 16, 'REGULAR', '2026-11-05 12:59:07'),
(90, 18, 16, 'REGULAR', '2026-11-05 12:59:07'),
(91, 19, 16, 'REGULAR', '2026-11-05 12:59:07'),
(92, 15, 17, 'REGULAR', '2026-11-05 12:59:07'),
(93, 16, 17, 'REGULAR', '2026-11-05 12:59:07'),
(94, 17, 17, 'REGULAR', '2026-11-05 12:59:07'),
(95, 18, 17, 'REGULAR', '2026-11-05 12:59:07'),
(96, 19, 17, 'REGULAR', '2026-11-05 12:59:07'),
(97, 15, 18, 'REGULAR', '2026-11-05 12:59:07'),
(98, 16, 18, 'REGULAR', '2026-11-05 12:59:07'),
(99, 17, 18, 'REGULAR', '2026-11-05 12:59:07'),
(100, 18, 18, 'REGULAR', '2026-11-05 12:59:07'),
(101, 19, 18, 'REGULAR', '2026-11-05 12:59:07'),
(102, 15, 19, 'REGULAR', '2026-11-05 12:59:07'),
(103, 16, 19, 'REGULAR', '2026-11-05 12:59:07'),
(104, 17, 19, 'REGULAR', '2026-11-05 12:59:07'),
(105, 18, 19, 'REGULAR', '2026-11-05 12:59:07'),
(106, 19, 19, 'REGULAR', '2026-11-05 12:59:07');

-- --------------------------------------------------------

--
-- Table structure for table `student_masterlist`
--

CREATE TABLE `student_masterlist` (
  `masterlist_id` int(10) UNSIGNED NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `previous_program` varchar(100) DEFAULT NULL,
  `previous_year_level` int(11) DEFAULT NULL,
  `previous_section` varchar(50) DEFAULT NULL,
  `previous_semester` varchar(50) DEFAULT NULL,
  `previous_school_year` varchar(20) DEFAULT NULL,
  `registration_status` enum('UNREGISTERED','REGISTERED','REVIEW_REQUIRED') NOT NULL DEFAULT 'UNREGISTERED',
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_masterlist`
--

INSERT INTO `student_masterlist` (`masterlist_id`, `student_number`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `email`, `program`, `year_level`, `section`, `previous_program`, `previous_year_level`, `previous_section`, `previous_semester`, `previous_school_year`, `registration_status`, `user_id`, `imported_at`, `updated_at`) VALUES
(1, '2024001111', 'Juan', 'Dela', 'Cruz', '2006-04-02', 'monzylog@gmail.com', 'Bachelor of Science in Information Technology', 3, 'D', 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026-2027', 'UNREGISTERED', NULL, '2026-11-05 06:51:03', '2026-11-05 17:27:12'),
(2, '2024001112', 'Mark', 'V.', 'Vigo', '2006-02-27', 'quizonrichmond0@gmail.com', 'Bachelor of Science in Information Technology', 3, 'D', 'Bachelor of Science in Information Technology', 3, 'D', '1st Semester', '2026-2027', 'UNREGISTERED', NULL, '2026-11-05 11:51:02', '2026-11-05 17:27:12'),
(3, '2024999001', 'Daniel', NULL, 'Reyes', '2006-04-02', 'ra.quizon.bsit2024@gmail.com', 'Bachelor of Science in Information Technology', 3, 'D', 'Bachelor of Science in Information Technology', 3, 'A', '1st Semester', '2026-2027', 'UNREGISTERED', NULL, '2026-11-05 15:18:59', '2026-11-05 15:18:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_registration_requests`
--

CREATE TABLE `student_registration_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `masterlist_id` int(10) UNSIGNED NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_registration_requests`
--

INSERT INTO `student_registration_requests` (`request_id`, `masterlist_id`, `password_hash`, `status`, `requested_at`, `reviewed_by`, `reviewed_at`, `rejection_reason`) VALUES
(5, 3, '$2y$10$FgZOvDN3nIvQ8k9xREtSmOpxKJRlVz79dODooaGLCg6h7QKkve6c6', 'PENDING', '2026-11-06 01:34:58', NULL, NULL, NULL);

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
(1, 'CLOSED', '2nd Semester', '2026 - 2027', '2026-11-05', '2026-11-15', '2026-11-06 14:21:44');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `account_number`, `password_hash`, `role`, `is_active`, `created_at`, `must_change_password`) VALUES
(8, 'ADMIN001', '$2y$12$lW4XC5INw7dcSYGuI0r4XOBuCxFFgB2inPzAocdUD9dUNhmXtSCRa', 'ADMIN', 1, '2026-10-05 14:41:27', 0),
(20, '20240529', '$2y$10$3O8ZxThF/cq1kA.hBgqh/.fDuQ2NnY5EwRIO295x/kqK8Z0BcrQXa', 'STUDENT', 1, '2026-11-05 11:51:38', 0),
(21, '202400764', '$2y$10$8x2VLMRovE1nPr9ofcXT4ubxW0xxxC38QPLWp4dZimGiD/2wiGkAS', 'STUDENT', 1, '2026-11-05 12:38:02', 0),
(22, '202400847', '$2y$10$Ru5HH.euldk2ddYuE0buK./dELSwrsewaaLRQoBAVkuld3KvapjB2', 'STUDENT', 1, '2026-11-05 12:38:47', 0),
(23, '202401207', '$2y$10$oOfIFHlickuf0M2.22VX1eHq4x0Sd//CzdrBmCgOIkbLddhrYNENS', 'STUDENT', 1, '2026-11-05 12:39:20', 0),
(24, '202400745', '$2y$10$EWnxlM.lQwKxAisabM/8dOOTr.AEByxRaFlxug2a5P0GSb.myLKJm', 'STUDENT', 1, '2026-11-05 12:40:09', 0),
(25, '202400924', '$2y$10$DT1P4apIl0lnzILeSDLyuenTuGXngWES.efyAC5PqZyhl5wgdpJoy', 'STUDENT', 1, '2026-11-05 12:50:17', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clearance_submissions`
--
ALTER TABLE `clearance_submissions`
  ADD PRIMARY KEY (`clearance_id`),
  ADD KEY `idx_clearance_student_period` (`student_id`,`semester`,`school_year`),
  ADD KEY `idx_clearance_status` (`status`);

--
-- Indexes for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  ADD PRIMARY KEY (`cor_submission_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_cor_student_period_status` (`student_id`,`period_id`,`status`),
  ADD KEY `fk_cor_submission_period` (`period_id`);

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
-- Indexes for table `enrollment_periods`
--
ALTER TABLE `enrollment_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `uq_enrollment_period` (`semester`,`school_year`);

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
-- Indexes for table `registration_verifications`
--
ALTER TABLE `registration_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD UNIQUE KEY `uq_registration_masterlist` (`masterlist_id`),
  ADD KEY `idx_registration_expires` (`expires_at`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_students_number` (`student_number`),
  ADD UNIQUE KEY `uq_students_email` (`email`);

--
-- Indexes for table `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `uq_student_offering` (`student_id`,`offering_id`),
  ADD KEY `fk_sce_offering` (`offering_id`);

--
-- Indexes for table `student_masterlist`
--
ALTER TABLE `student_masterlist`
  ADD PRIMARY KEY (`masterlist_id`),
  ADD UNIQUE KEY `uq_masterlist_student_number` (`student_number`),
  ADD UNIQUE KEY `uq_masterlist_email` (`email`),
  ADD KEY `idx_masterlist_status` (`registration_status`),
  ADD KEY `fk_masterlist_user` (`user_id`);

--
-- Indexes for table `student_registration_requests`
--
ALTER TABLE `student_registration_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_registration_masterlist` (`masterlist_id`),
  ADD KEY `idx_registration_status` (`status`),
  ADD KEY `idx_registration_reviewer` (`reviewed_by`);

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
-- AUTO_INCREMENT for table `clearance_submissions`
--
ALTER TABLE `clearance_submissions`
  MODIFY `clearance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  MODIFY `cor_submission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `course_offerings`
--
ALTER TABLE `course_offerings`
  MODIFY `offering_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `course_offering_schedules`
--
ALTER TABLE `course_offering_schedules`
  MODIFY `schedule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollment_periods`
--
ALTER TABLE `enrollment_periods`
  MODIFY `period_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollment_status_history`
--
ALTER TABLE `enrollment_status_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  MODIFY `question_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `faculty_assignment_finalizations`
--
ALTER TABLE `faculty_assignment_finalizations`
  MODIFY `finalization_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `professor_evaluations`
--
ALTER TABLE `professor_evaluations`
  MODIFY `evaluation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `professor_evaluation_answers`
--
ALTER TABLE `professor_evaluation_answers`
  MODIFY `answer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=561;

--
-- AUTO_INCREMENT for table `registration_verifications`
--
ALTER TABLE `registration_verifications`
  MODIFY `verification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  MODIFY `enrollment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `student_masterlist`
--
ALTER TABLE `student_masterlist`
  MODIFY `masterlist_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_registration_requests`
--
ALTER TABLE `student_registration_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clearance_submissions`
--
ALTER TABLE `clearance_submissions`
  ADD CONSTRAINT `fk_clearance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `cor_submissions`
--
ALTER TABLE `cor_submissions`
  ADD CONSTRAINT `cor_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cor_submissions_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cor_submission_period` FOREIGN KEY (`period_id`) REFERENCES `enrollment_periods` (`period_id`);

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

--
-- Constraints for table `registration_verifications`
--
ALTER TABLE `registration_verifications`
  ADD CONSTRAINT `fk_registration_masterlist` FOREIGN KEY (`masterlist_id`) REFERENCES `student_masterlist` (`masterlist_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  ADD CONSTRAINT `fk_sce_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sce_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_masterlist`
--
ALTER TABLE `student_masterlist`
  ADD CONSTRAINT `fk_masterlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_registration_requests`
--
ALTER TABLE `student_registration_requests`
  ADD CONSTRAINT `fk_student_registration_masterlist` FOREIGN KEY (`masterlist_id`) REFERENCES `student_masterlist` (`masterlist_id`),
  ADD CONSTRAINT `fk_student_registration_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
