<?php

require_role('STUDENT');

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

$settingsStmt = $conn->prepare(
    'SELECT enrollment_status
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);

$settingsStmt->execute();

$settings = $settingsStmt->get_result()->fetch_assoc();

$settingsStmt->close();

$enrollmentStatus = $settings['enrollment_status'] ?? 'CLOSED';
$enrollmentPhase = $student['enrollment_phase'];

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
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">

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
            <?php if ($enrollmentStatus === 'CLOSED'): ?>

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
                            Enrollment is currently unavailable. Please wait for the enrollment period to open.
                        </p>

                        <a href="<?= BASE_URL ?>?page=profile"
                        class="hero-button">
                            View Profile
                            <i class="fa-regular fa-user"></i>
                        </a>

                    </div>

                </section>

            <?php elseif ($enrollmentPhase === 'NOT_STARTED'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                         <div class="status-badge status-open">
                            <i class="fa-solid fa-circle-check"></i>
                            Enrollment is now Open
                        </div>

                        <h1>Start Your Enrollment</h1>

                        <p class="hero-description">
                            Begin your enrollment by completing your professor evaluation.
                        </p>

                        <a href="<?= BASE_URL ?>?page=evaluation" class="hero-button">
                            Start Evaluation
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 EVALUATION
            ========================== -->
            <?php elseif ($enrollmentPhase === 'EVALUATION'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-open">
                            <i class="fa-solid fa-circle-check"></i>
                            Evaluation Completed
                        </div>

                        <h1>Evaluation Completed</h1>

                        <p class="hero-description">
                            Your professor evaluation is complete. Continue by completing your clearance.
                        </p>

                        <a href="<?= BASE_URL ?>?page=clearance" class="hero-button">
                            Start Clearance
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 CLEARANCE
            ========================== -->
            <?php elseif ($enrollmentPhase === 'CLEARANCE'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-open">
                            <i class="fa-solid fa-circle-check"></i>
                            Clearance Completed
                        </div>

                        <h1>Clearance Completed</h1>

                        <p class="hero-description">
                            Your clearance has been completed. You may now print and submit your COR.
                        </p>

                        <a href="<?= BASE_URL ?>?page=cor" class="hero-button">
                            View COR
                            <i class="fa-solid fa-file-lines"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 COR
            ========================== -->
            <?php elseif ($enrollmentPhase === 'COR'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                        <div class="status-badge status-open">
                            <i class="fa-solid fa-clock"></i>
                            Pending Registrar Review
                        </div>

                        <h1>COR Submitted</h1>

                        <p class="hero-description">
                            Your COR has been submitted and is waiting for Registrar review.
                        </p>

                        <a href="<?= BASE_URL ?>?page=cor" class="hero-button">
                            View COR Status
                            <i class="fa-solid fa-file-lines"></i>
                        </a>

                    </div>

                </section>


            <!-- =========================
                 ENROLLED
            ========================== -->
            <?php elseif ($enrollmentPhase === 'ENROLLED'): ?>

                <section class="home-hero">

                    <div class="home-hero-content">

                         <div class="status-badge status-open">
                            <i class="fa-solid fa-circle-check"></i>
                            Enrollment Completed
                        </div>

                        <h1>You're Officially Enrolled!</h1>

                        <p class="hero-description">
                            Your enrollment has been successfully processed.
                        </p>

                        <a href="<?= BASE_URL ?>?page=my_enrollment" class="hero-button">
                            View My Enrollment
                            <i class="fa-solid fa-id-card"></i>
                        </a>

                    </div>

                </section>

            <?php endif; ?>


            <!-- =========================
                 PROGRESS
            ========================== -->

            <section class="enrollment-progress">

                <div class="progress-item
                    <?= in_array($enrollmentPhase, ['EVALUATION', 'CLEARANCE', 'COR', 'ENROLLED'])
                        ? 'completed'
                        : ($enrollmentPhase === 'NOT_STARTED' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if (in_array($enrollmentPhase, ['EVALUATION', 'CLEARANCE', 'COR', 'ENROLLED'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    </div>

                    <span>Evaluation</span>
                </div>


                <div class="progress-line"></div>


                <div class="progress-item
                    <?= in_array($enrollmentPhase, ['CLEARANCE', 'COR', 'ENROLLED'])
                        ? 'completed'
                        : ($enrollmentPhase === 'EVALUATION' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if (in_array($enrollmentPhase, ['CLEARANCE', 'COR', 'ENROLLED'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            2
                        <?php endif; ?>
                    </div>

                    <span>Clearance</span>
                </div>


                <div class="progress-line"></div>


                <div class="progress-item
                    <?= in_array($enrollmentPhase, ['COR', 'ENROLLED'])
                        ? 'completed'
                        : ($enrollmentPhase === 'CLEARANCE' ? 'current' : '') ?>">

                    <div class="progress-number">
                        <?php if (in_array($enrollmentPhase, ['COR', 'ENROLLED'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            3
                        <?php endif; ?>
                    </div>

                    <span>COR</span>
                </div>


                <div class="progress-line"></div>


                <div class="progress-item
                    <?= $enrollmentPhase === 'ENROLLED' ? 'completed' : '' ?>">

                    <div class="progress-number">
                        <?php if ($enrollmentPhase === 'ENROLLED'): ?>
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

                        <p>Enrollment progress</p>

                        <h2>
                            <?php
                            $phaseLabels = [
                                'NOT_STARTED' => 'Not Started',
                                'EVALUATION'  => 'Evaluation Completed',
                                'CLEARANCE'   => 'Clearance Completed',
                                'COR'         => 'COR Submitted',
                                'ENROLLED'    => 'Enrolled'
                            ];

                            echo e($phaseLabels[$enrollmentPhase] ?? 'Unknown');
                            ?>
                        </h2>

                        <span>
                            <?php
                            $phaseProgress = [
                                'NOT_STARTED' => '0 out of 4',
                                'EVALUATION'  => '1 out of 4',
                                'CLEARANCE'   => '2 out of 4',
                                'COR'         => '3 out of 4',
                                'ENROLLED'    => '4 out of 4'
                            ];

                            echo $phaseProgress[$enrollmentPhase] ?? '0 out of 4';
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