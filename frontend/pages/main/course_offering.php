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

$currentPage = 'course_offering';

/* ---------------------------------------------------------
   STUDENT INFO
   TODO: replace the fallbacks with your real column names.
--------------------------------------------------------- */
$semesterLabel = '2nd Semester, AY 2026-2027';
$programName   = $student['program']    ?? 'BS Information Technology';
$yearLevel     = $student['year_level'] ?? '3rd Year';
$sectionName   = $student['section']    ?? 'BSIT - 3D';

/* ---------------------------------------------------------
   COURSES
   TODO: replace this sample array with a query on the
   student's block section, e.g.

   $stmt = $conn->prepare(
       'SELECT course_code, course_name, day, time_start, time_end,
               room, instructor, units
        FROM course_offerings
        WHERE section_id = ?
        ORDER BY course_code'
   );
--------------------------------------------------------- */
$courses = [
    ['code' => 'IT 301',  'name' => 'Database System',                      'schedule' => 'Mon 8:00 AM - 11:00 AM',   'room' => 'Room 301',  'instructor' => 'Prof. Juan Dela Cruz', 'units' => 3],
    ['code' => 'IT 302',  'name' => 'Web Development',                      'schedule' => 'Tues 8:00 AM - 11:00 AM',  'room' => 'Room 302',  'instructor' => 'Prof. Ronald Pineda',  'units' => 3],
    ['code' => 'IT 303',  'name' => 'System Analysis and Design',           'schedule' => 'Wed 8:00 AM - 11:00 AM',   'room' => 'Room 303',  'instructor' => 'Prof. Sanggre Alena',  'units' => 3],
    ['code' => 'IT 304',  'name' => 'Networking 1',                         'schedule' => 'Thurs 8:00 AM - 11:00 AM', 'room' => 'Room 304',  'instructor' => 'Prof. Fhukerat',       'units' => 3],
    ['code' => 'IT 305',  'name' => 'System Integration and Architecture',  'schedule' => 'Fri 8:00 AM - 11:00 AM',   'room' => 'Room 305',  'instructor' => 'Doc. Ryzza Mae Digong', 'units' => 3],
    ['code' => 'IT 306',  'name' => 'Integrative Programming',              'schedule' => 'Sat 8:00 AM - 11:00 AM',   'room' => 'Room 306',  'instructor' => 'Atty. Pepsi Paloma',   'units' => 3],
    ['code' => 'GE 101',  'name' => 'Purposive Communication',              'schedule' => 'Sun 8:00 AM - 11:00 AM',   'room' => 'Room 307',  'instructor' => 'Prof. Uncle Dags',     'units' => 3],
    ['code' => 'PE 102',  'name' => 'Physical Fitness 1',                   'schedule' => 'Mon 12:00 PM - 3:00 PM',   'room' => 'Gymnasium', 'instructor' => 'Prof. Maria Hiwaga',   'units' => 3],
];

$totalCourses = count($courses);
$totalUnits   = array_sum(array_column($courses, 'units'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Course Offering</title>

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
                 PAGE HEADING
            ========================== -->
            <section class="page-heading">
                <h1>Course Offering</h1>
                <p>
                    View your pre-assigned courses for the current semester.
                    These subjects are based on your block section and cannot be modified.
                </p>
            </section>


            <!-- =========================
                 OFFERING CARD
            ========================== -->
            <section class="co-card">

                <!-- STUDENT INFO -->
                <div class="co-info">

                    <div class="co-info-item is-highlight">
                        <span class="co-icon"><i class="fa-regular fa-calendar"></i></span>
                        <div>
                            <small>Current Semester</small>
                            <strong><?= e($semesterLabel) ?></strong>
                        </div>
                    </div>

                    <div class="co-info-item">
                        <span class="co-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                        <div>
                            <small>Program</small>
                            <strong><?= e($programName) ?></strong>
                        </div>
                    </div>

                    <div class="co-info-item">
                        <span class="co-icon"><i class="fa-regular fa-id-badge"></i></span>
                        <div>
                            <small>Year Level</small>
                            <strong><?= e($yearLevel) ?></strong>
                        </div>
                    </div>

                    <div class="co-info-item">
                        <span class="co-icon"><i class="fa-solid fa-users"></i></span>
                        <div>
                            <small>Section</small>
                            <strong><?= e($sectionName) ?></strong>
                        </div>
                    </div>

                </div>


                <!-- TOTALS + ACTION -->
                <div class="co-totals">

                    <div class="co-total-box">
                        <span class="co-icon"><i class="fa-solid fa-book"></i></span>
                        <div>
                            <small>Total Courses</small>
                            <strong><?= e($totalCourses) ?></strong>
                        </div>
                    </div>

                    <div class="co-total-box">
                        <span class="co-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                        <div>
                            <small>Total Units</small>
                            <strong><?= e($totalUnits) ?></strong>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>?page=my_schedule" class="co-button">
                        View Schedule
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>

                </div>


                <!-- COURSE TABLE -->
                <div class="co-table-wrap">

                    <table class="co-table">

                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <th>Course ID</th>
                                <th>Course Name</th>
                                <th>Schedule</th>
                                <th>Instructor</th>
                                <th class="col-units">Units</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($courses)): ?>

                                <tr>
                                    <td colspan="6" class="co-empty">
                                        No courses have been assigned to your section yet.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($courses as $i => $course): ?>

                                    <tr>
                                        <td class="col-num"><?= $i + 1 ?></td>
                                        <td><?= e($course['code']) ?></td>
                                        <td><?= e($course['name']) ?></td>
                                        <td>
                                            <?= e($course['schedule']) ?><br>
                                            <?= e($course['room']) ?>
                                        </td>
                                        <td><?= e($course['instructor']) ?></td>
                                        <td class="col-units"><?= e($course['units']) ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- NOTE -->
                <div class="co-note">
                    <span class="co-note-icon"><i class="fa-solid fa-circle-info"></i></span>
                    Courses are pre-assigned based on your section and cannot be modified
                </div>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>
</html>