<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 3) . '/backend/models/student.php';

$studentId = $_SESSION['student_id'] ?? null;

if (!$studentId) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$student = student_find_by_id($conn, (int) $studentId);

if (!$student) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$userName = trim(
    $student['first_name'] . ' ' .
    ($student['middle_name'] ? $student['middle_name'] . ' ' : '') .
    $student['last_name']
);

$currentPage = 'home_main';

$enrollmentStatus = $student['enrollment_status'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="layout">

    <!-- LOGO -->
    <div class="imglogo">
        <!-- <img src="<?= BASE_URL ?>frontend/assets/images/cmu-logo.png"
             alt="City of Malabon University"> -->
             <span>CMU</span>
    </div>

    <!-- SIDEBAR -->
    <?php require FRONTEND_PATH . 'includes/sidebar.php'; ?>

    <!-- MOBILE OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="content">

            <!-- =========================
                 CLOSED
            ========================== -->
            <?php if ($enrollmentStatus === 'Closed'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-closed">
                            <i class="fa-solid fa-circle-info"></i>
                            Enrollment is currently Closed
                        </div>

                        <p class="semester-label">
                            1st Semester, S.Y. 2026–2027
                        </p>

                        <h1>
                            Welcome Back, <?= e($student['first_name']) ?>!
                        </h1>

                        <p class="hero-description">
                            Enrollment opens June 1. Get your requirements ready.
                        </p>

                        <a href="<?= BASE_URL ?>?page=profile"
                           class="hero-button">
                            Prepare your requirements
                            <i class="fa-regular fa-clipboard"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 EVALUATION
            ========================== -->
            <?php elseif ($enrollmentStatus === 'Evaluation'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-open">
                            <i class="fa-solid fa-circle-check"></i>
                            Enrollment is now Open
                        </div>

                        <p class="semester-label">
                            1st Semester, S.Y. 2026–2027
                        </p>

                        <h1>
                            Welcome Back, <?= e($student['first_name']) ?>!
                        </h1>

                        <p class="hero-description">
                            Complete your professor evaluation to continue your enrollment.
                        </p>

                        <a href="<?= BASE_URL ?>?page=evaluation"
                           class="hero-button">
                            Start Evaluation
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 CLEARANCE
            ========================== -->
            <?php elseif ($enrollmentStatus === 'Clearance'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-clearance">
                            <i class="fa-solid fa-circle-check"></i>
                            Professor Evaluation Completed
                        </div>

                        <p class="semester-label">
                            1st Semester, S.Y. 2026–2027
                        </p>

                        <h1>
                            Complete your Clearance
                        </h1>

                        <p class="hero-description">
                            Get the required signatures to continue your enrollment.
                        </p>

                        <a href="<?= BASE_URL ?>?page=clearance"
                           class="hero-button">
                            View Clearance
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 COR
            ========================== -->
            <?php elseif ($enrollmentStatus === 'COR'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-cor">
                            <i class="fa-solid fa-file-circle-check"></i>
                            Your COR is Ready
                        </div>

                        <p class="semester-label">
                            1st Semester, S.Y. 2026–2027
                        </p>

                        <h1>
                            Your COR is ready, <?= e($student['first_name']) ?>!
                        </h1>

                        <p class="hero-description">
                            Download, print, sign, and submit your Certificate of Registration.
                        </p>

                        <a href="<?= BASE_URL ?>?page=cor"
                           class="hero-button">
                            View COR Submission
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 ENROLLED
            ========================== -->
            <?php elseif ($enrollmentStatus === 'Enrolled'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-enrolled">
                            <i class="fa-solid fa-circle-check"></i>
                            Officially Enrolled
                        </div>

                        <p class="semester-label">
                            1st Semester, S.Y. 2026–2027
                        </p>

                        <h1>
                            You're officially enrolled!
                        </h1>

                        <p class="hero-description">
                            Your enrollment has been successfully processed.
                        </p>

                        <a href="<?= BASE_URL ?>?page=my_enrollment"
                           class="hero-button">
                            View My Enrollment
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>

            <?php endif; ?>


            <!-- =========================
                 PROGRESS
            ========================== -->

            <section class="enrollment-progress">

                <div class="progress-item <?= in_array($enrollmentStatus, ['Clearance', 'COR', 'Enrolled']) ? 'completed' : ($enrollmentStatus === 'Evaluation' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if (in_array($enrollmentStatus, ['Clearance', 'COR', 'Enrolled'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    </div>

                    <span>Evaluation</span>

                </div>


                <div class="progress-line"></div>


                <div class="progress-item <?= in_array($enrollmentStatus, ['COR', 'Enrolled']) ? 'completed' : ($enrollmentStatus === 'Clearance' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if (in_array($enrollmentStatus, ['COR', 'Enrolled'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            2
                        <?php endif; ?>
                    </div>

                    <span>Clearance</span>

                </div>


                <div class="progress-line"></div>


                <div class="progress-item <?= $enrollmentStatus === 'Enrolled' ? 'completed' : ($enrollmentStatus === 'COR' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if ($enrollmentStatus === 'Enrolled'): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            3
                        <?php endif; ?>
                    </div>

                    <span>COR</span>

                </div>


                <div class="progress-line"></div>


                <div class="progress-item <?= $enrollmentStatus === 'Enrolled' ? 'completed' : '' ?>">

                    <div class="progress-number">

                        <?php if ($enrollmentStatus === 'Enrolled'): ?>

                            <i class="fa-solid fa-check"></i>

                        <?php else: ?>

                            4

                        <?php endif; ?>

                    </div>

                    <span>Enrolled</span>

                </div>

            </section>


            <!-- =========================
                 STAT CARDS
            ========================== -->

            <section class="home-stats">

                <div class="home-stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>

                    <div class="stat-content">

                        <p>Courses taken</p>

                        <h2>16 out of 32</h2>

                        <div class="stat-progress">
                            <div class="stat-progress-fill"></div>
                        </div>

                        <span>Curriculum progress</span>

                    </div>

                </div>


                <div class="home-stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>

                    <div class="stat-content">

                        <p>This semester</p>

                        <h2>8 Courses</h2>

                        <span class="stat-highlight">
                            21 Units
                        </span>

                    </div>

                </div>


                <div class="home-stat-card">

                    <div class="stat-icon">
                        <i class="fa-regular fa-rectangle-list"></i>
                    </div>

                    <div class="stat-content">

                        <p>Enrollment status</p>

                        <h2><?= e($enrollmentStatus) ?></h2>

                        <span>
                            <?= $enrollmentStatus === 'Enrolled'
                                ? 'Enrollment completed'
                                : 'Step ' .
                                  (
                                      $enrollmentStatus === 'Closed' ? '0' :
                                      ($enrollmentStatus === 'Evaluation' ? '1' :
                                      ($enrollmentStatus === 'Clearance' ? '2' :
                                      ($enrollmentStatus === 'COR' ? '3' : '4')))
                                  ) .
                                  ' out of 4'
                            ?>
                        </span>

                    </div>

                </div>


                <div class="home-stat-card">

                    <div class="stat-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div class="stat-content">

                        <p>Enrollment period</p>

                        <h2>June 1–15</h2>

                        <span>
                            Enrollment schedule
                        </span>

                    </div>

                </div>

            </section>


            <!-- =========================
                 LOWER CONTENT
            ========================== -->

            <section class="home-lower">

                <!-- ANNOUNCEMENTS -->

                <div class="home-panel">

                    <div class="panel-header">

                        <h2>Recent announcement</h2>

                        <a href="<?= BASE_URL ?>?page=notifications">
                            View all
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>


                    <div class="announcement-item">

                        <i class="fa-solid fa-bullhorn"></i>

                        <div>
                            <p>Enrollment schedule for 1st semester</p>
                            <span>Important enrollment announcement</span>
                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </div>


                    <div class="announcement-item">

                        <i class="fa-solid fa-bullhorn"></i>

                        <div>
                            <p>Reminder: Complete your enrollment process</p>
                            <span>Enrollment reminder</span>
                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </div>


                    <div class="announcement-item">

                        <i class="fa-solid fa-bullhorn"></i>

                        <div>
                            <p>Check out your courses now!</p>
                            <span>Course offering information</span>
                        </div>

                        <i class="fa-solid fa-chevron-right"></i>

                    </div>

                </div>


                <!-- WEEKLY SCHEDULE -->

                <div class="home-panel">

                    <div class="panel-header">

                        <h2>Weekly Schedule</h2>

                        <a href="<?= BASE_URL ?>?page=my_schedule">
                            View all
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>


                    <div class="schedule-item">

                        <i class="fa-solid fa-book"></i>

                        <div>
                            <p>1:00 PM – 5:00 PM</p>
                            <span>IT101 – Integrative Programming</span>
                        </div>

                    </div>


                    <div class="schedule-item">

                        <i class="fa-solid fa-book"></i>

                        <div>
                            <p>1:00 PM – 5:00 PM</p>
                            <span>IT102 – Information Management</span>
                        </div>

                    </div>


                    <div class="schedule-item">

                        <i class="fa-solid fa-book"></i>

                        <div>
                            <p>1:00 PM – 5:00 PM</p>
                            <span>IT103 – Networking</span>
                        </div>

                    </div>

                </div>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>
</html>