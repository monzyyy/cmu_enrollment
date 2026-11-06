<?php

require_role('STUDENT');

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 3) . '/backend/models/student.php';
require_once dirname(__DIR__, 3) . '/backend/helpers/evaluation_completion.php';

$studentId = $_SESSION['student_id'] ?? null;

if (!$studentId) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// Recover completed evaluations from before the phase update was fixed.
syncCompletedEvaluationPhase($conn, (int) $studentId);
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
    'SELECT
        enrollment_status,
        semester,
        school_year,
        start_date,
        end_date
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);

$settingsStmt->execute();

$settings = $settingsStmt->get_result()->fetch_assoc();

$settingsStmt->close();

$enrollmentStatus = $settings['enrollment_status'] ?? 'CLOSED';
$enrollmentPhase = $student['enrollment_phase'];

$semester = $settings['semester'] ?? '';
$schoolYear = $settings['school_year'] ?? '';
$startDate = $settings['start_date'] ?? null;
$endDate = $settings['end_date'] ?? null;

// Use the same course offering and schedule tables as My Schedule.
// PHP timezone is explicit so "today" follows Philippine campus time.
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayDayName = $today->format('l');
$todaySchedules = [];

$todayStmt = $conn->prepare(
    'SELECT c.course_code, c.course_name, co.instructor_name,
            cos.start_time, cos.end_time, cos.room
     FROM course_offerings co
     INNER JOIN courses c ON c.course_id = co.course_id
     INNER JOIN course_offering_schedules cos ON cos.offering_id = co.offering_id
     WHERE co.program = ?
       AND co.year_level = ?
       AND co.section = ?
       AND co.semester = ?
       AND REPLACE(co.school_year, " ", "") = REPLACE(?, " ", "")
       AND LOWER(TRIM(cos.day_of_week)) = LOWER(?)
     ORDER BY cos.start_time ASC, c.course_code ASC'
);
$todayStmt->bind_param(
    'sissss',
    $student['program'],
    $student['year_level'],
    $student['section'],
    $semester,
    $schoolYear,
    $todayDayName
);
$todayStmt->execute();
$todayResult = $todayStmt->get_result();
while ($todayRow = $todayResult->fetch_assoc()) {
    $todaySchedules[] = $todayRow;
}
$todayStmt->close();


$latestCorSubmission = null;
$corStatus = 'NOT_SUBMITTED';

$corStmt = $conn->prepare(
    'SELECT
        status,
        rejection_reason
     FROM cor_submissions
     WHERE student_id = ?
     ORDER BY cor_submission_id DESC
     LIMIT 1'
);

$corStmt->bind_param('i', $studentId);
$corStmt->execute();

$latestCorSubmission = $corStmt->get_result()->fetch_assoc();

$corStmt->close();

$corStatus = $latestCorSubmission['status'] ?? 'NOT_SUBMITTED';

$enrollmentPeriod = '';

if ($startDate && $endDate) {

    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);

    if (date('Y-m', $startTimestamp) === date('Y-m', $endTimestamp)) {

        $enrollmentPeriod =
            date('F j', $startTimestamp) .
            '–' .
            date('j', $endTimestamp);

    } else {

        $enrollmentPeriod =
            date('F j', $startTimestamp) .
            '–' .
            date('F j', $endTimestamp);
    }
}

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
<style>
/* Home dashboard: compact, responsive enrollment status panel */
.home-panel .enrollment-status-panel {
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    gap: 12px;
    min-width: 0;
    height: auto;
    font-size: 13px;
    line-height: 1.5;
    color: #334155;
}
.enrollment-status-heading { display: flex; align-items: flex-start; gap: 11px; min-width: 0; }
.enrollment-status-icon {
    display: grid; place-items: center; flex: 0 0 32px;
    width: 32px; height: 32px; border-radius: 10px;
    background: #e7f7ef; color: #15803d; font-size: 15px;
}
.enrollment-status-panel.is-closed .enrollment-status-icon { background: #fff3df; color: #a16207; }
.enrollment-status-panel.is-unconfigured .enrollment-status-icon { background: #eaf2ff; color: #2563eb; }
.enrollment-status-heading > div { min-width: 0; }
.enrollment-status-heading strong { display: block; font-size: 14px; color: #123457; line-height: 1.4; }
.enrollment-status-heading p { margin: 3px 0 0; color: #64748b; font-size: 12px; overflow-wrap: anywhere; }
.enrollment-status-details {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 6px 12px; padding: 11px 12px;
    background: #f5f8fc; border: 1px solid #e4ebf4; border-radius: 9px;
}
.enrollment-status-detail-label { display: inline-flex; align-items: center; gap: 7px; color: #64748b; font-size: 12px; }
.enrollment-status-details strong { color: #1d385b; font-size: 12px; }
.enrollment-status-message { margin: 0; color: #52637a; font-size: 12px; line-height: 1.55; }
.enrollment-status-link {
    display: inline-flex; align-items: center; justify-content: center; gap: 9px;
    align-self: flex-start; max-width: 100%; padding: 9px 12px;
    border-radius: 8px; background: #e8f0ff; color: #1d57bd;
    font-size: 12px; font-weight: 700; text-decoration: none;
    transition: background .2s ease;
}
.enrollment-status-link:hover { background: #d7e6ff; }
@media (max-width: 768px) {
    .home-panel .enrollment-status-panel { padding: 14px; gap: 11px; }
    .enrollment-status-link { align-self: stretch; }
}
</style>
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

            <div class="home-hero-layout">

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
                            <?= e(($semester ?: 'Semester not set') . ', S.Y. ' . ($schoolYear ?: 'Not set')) ?>
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

                        <a href="<?= BASE_URL ?>?page=faculty_evaluation" class="hero-button">
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

                        <a href="<?= BASE_URL ?>?page=clearance_main" class="hero-button">
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

                        <?php if ($corStatus === 'NOT_SUBMITTED'): ?>

                            <div class="status-badge status-open">
                                <i class="fa-solid fa-file-circle-plus"></i>
                                COR Not Yet Submitted
                            </div>

                            <h1>Submit Your COR</h1>

                            <p class="hero-description">
                                Your signed COR has not been submitted yet.
                                Please upload it to continue your enrollment.
                            </p>

                            <a href="<?= BASE_URL ?>?page=cor" class="hero-button">
                                Submit COR
                                <i class="fa-solid fa-file-arrow-up"></i>
                            </a>

                        <?php elseif ($corStatus === 'REJECTED'): ?>

                            <div class="status-badge status-closed">
                                <i class="fa-solid fa-circle-xmark"></i>
                                COR Rejected
                            </div>

                            <h1>COR Needs Correction</h1>

                            <p class="hero-description">
                                Your COR was rejected.
                                Please review the reason and submit a corrected copy.
                            </p>

                            <a href="<?= BASE_URL ?>?page=cor" class="hero-button">
                                View COR
                                <i class="fa-solid fa-file-lines"></i>
                            </a>

                        <?php else: ?>

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

                        <?php endif; ?>

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
                <aside class="enrollment-info-card">

                    <div class="enrollment-info-header">

                        <div>
                            <span class="enrollment-info-label">
                                Enrollment
                            </span>

                            <h2>Enrollment Information</h2>
                        </div>

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>


                    <div class="enrollment-info-details">

                        <div class="enrollment-info-item">
                            <span>Semester</span>

                            <strong>
                                <?= e($semester ?: 'Not set') ?>
                            </strong>
                        </div>


                        <div class="enrollment-info-item">
                            <span>School Year</span>

                            <strong>
                                <?= e($schoolYear ?: 'Not set') ?>
                            </strong>
                        </div>


                        <div class="enrollment-info-item">
                            <span>Enrollment Period</span>

                            <strong>
                                <?= e($enrollmentPeriod ?: 'Not set') ?>
                            </strong>
                        </div>

                    </div>


                    <div class="enrollment-info-status
                        <?= $enrollmentStatus === 'OPEN'
                            ? 'is-open'
                            : 'is-closed' ?>">

                        <i class="fa-solid
                            <?= $enrollmentStatus === 'OPEN'
                                ? 'fa-circle-check'
                                : 'fa-circle-xmark' ?>"></i>

                        <span>
                            Enrollment is
                            <?= $enrollmentStatus === 'OPEN'
                                ? 'Open'
                                : 'Closed' ?>
                        </span>

                    </div>

                </aside>

            </div>


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
                    <?= $enrollmentPhase === 'ENROLLED'
                        ? 'completed'
                        : (in_array($enrollmentPhase, ['CLEARANCE', 'COR'])
                            ? 'current'
                            : '') ?>">

                    <div class="progress-number">
                        <?php if ($enrollmentPhase === 'ENROLLED'): ?>
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

                        <h2><?= e($enrollmentPeriod ?: 'Not set') ?></h2>

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

                <!-- ENROLLMENT STATUS (from system_settings) -->
                <div class="home-panel">
                    <div class="panel-header">
                        <h2>Enrollment Status</h2>
                    </div>

                    <div class="enrollment-status-panel <?= (!$settings || !$startDate || !$endDate) ? 'is-unconfigured' : (strtoupper(trim($enrollmentStatus)) === 'OPEN' ? 'is-open' : 'is-closed') ?>" role="status">
                        <?php if (!$settings || !$startDate || !$endDate): ?>
                            <div class="enrollment-status-heading">
                                <span class="enrollment-status-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
                                <div><strong>Enrollment Period Not Available</strong><p>The Registrar has not configured the enrollment schedule yet.</p></div>
                            </div>
                        <?php else: ?>
                            <div class="enrollment-status-heading">
                                <span class="enrollment-status-icon"><i class="fa-solid <?= strtoupper(trim($enrollmentStatus)) === 'OPEN' ? 'fa-circle-check' : 'fa-lock' ?>" aria-hidden="true"></i></span>
                                <div>
                                    <strong>Enrollment is <?= strtoupper(trim($enrollmentStatus)) === 'OPEN' ? 'Open' : 'Closed' ?></strong>
                                    <p><?= e($semester) ?>, S.Y. <?= e($schoolYear) ?></p>
                                </div>
                            </div>
                            <div class="enrollment-status-details">
                                <span class="enrollment-status-detail-label"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Enrollment period</span>
                                <strong><?= e($enrollmentPeriod) ?></strong>
                            </div>
                            <p class="enrollment-status-message">
                                <?= strtoupper(trim($enrollmentStatus)) === 'OPEN'
                                    ? 'Complete your faculty evaluations to begin the enrollment process.'
                                    : 'Enrollment activities are currently unavailable. Please wait for the Registrar to open enrollment.' ?>
                            </p>
                            <?php if (strtoupper(trim($enrollmentStatus)) === 'OPEN'): ?>
                                <a class="enrollment-status-link" href="<?= BASE_URL ?>?page=faculty_evaluation">
                                    Go to Faculty Evaluation <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- TODAY'S SCHEDULE -->

                <div class="home-panel">
                    <div class="panel-header">
                        <h2>Today's Schedule <span class="today-day-label"><?= e($todayDayName) ?></span></h2>
                        <a href="<?= BASE_URL ?>?page=my_schedule">
                            View all
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <?php if (empty($todaySchedules)): ?>
                        <div class="schedule-empty">
                            <i class="fa-regular fa-calendar-check"></i>
                            <p>No classes scheduled for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaySchedules as $todayClass): ?>
                            <div class="schedule-item">
                                <i class="fa-solid fa-book"></i>
                                <div>
                                    <p><?= e(date('g:i A', strtotime($todayClass['start_time']))) ?> – <?= e(date('g:i A', strtotime($todayClass['end_time']))) ?></p>
                                    <span><?= e($todayClass['course_code']) ?> – <?= e($todayClass['course_name']) ?></span>
                                    <?php if (!empty($todayClass['room'])): ?>
                                        <span>Room: <?= e($todayClass['room']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($todayClass['instructor_name'])): ?>
                                        <span><?= e($todayClass['instructor_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>
</html>