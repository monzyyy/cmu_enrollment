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
$semester = '';
$schoolYear = '';

$settingsResult = $conn->query(
    'SELECT semester, school_year
     FROM system_settings
     LIMIT 1'
);

if ($settingsResult && $settingsResult->num_rows > 0) {
    $settings = $settingsResult->fetch_assoc();

    $semester = $settings['semester'];
    $schoolYear = $settings['school_year'];
}

$semesterLabel = $semester . ', S.Y. ' . $schoolYear;

$programName = $student['program'];

$yearLevelNumber = (int) $student['year_level'];

if ($yearLevelNumber === 1) {
    $yearLevelLabel = '1st Year';
} elseif ($yearLevelNumber === 2) {
    $yearLevelLabel = '2nd Year';
} elseif ($yearLevelNumber === 3) {
    $yearLevelLabel = '3rd Year';
} elseif ($yearLevelNumber === 4) {
    $yearLevelLabel = '4th Year';
} else {
    $yearLevelLabel = $yearLevelNumber . 'th Year';
}

$sectionName = $student['section'];

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
$courses = [];

$stmt = $conn->prepare(
    'SELECT
        co.offering_id,
        c.course_code,
        c.course_name,
        c.units,
        co.instructor_name,
        GROUP_CONCAT(
            CONCAT(
                COALESCE(cos.day_of_week, ""),
                " ",
                IF(
                    cos.start_time IS NOT NULL
                    AND cos.end_time IS NOT NULL,
                    CONCAT(
                        DATE_FORMAT(cos.start_time, "%h:%i %p"),
                        " - ",
                        DATE_FORMAT(cos.end_time, "%h:%i %p")
                    ),
                    ""
                ),
                IF(
                    cos.room IS NOT NULL
                    AND cos.room <> "",
                    CONCAT(" · ", cos.room),
                    ""
                )
            )
            ORDER BY cos.schedule_id
            SEPARATOR "||"
        ) AS schedules
     FROM course_offerings co

     INNER JOIN courses c
        ON c.course_id = co.course_id

     LEFT JOIN course_offering_schedules cos
        ON cos.offering_id = co.offering_id

     WHERE co.program = ?
       AND co.year_level = ?
       AND co.section = ?
       AND co.semester = ?
       AND co.school_year = ?

     GROUP BY
        co.offering_id,
        c.course_code,
        c.course_name,
        c.units,
        co.instructor_name

     ORDER BY c.course_code ASC'
);

$stmt->bind_param(
    'sisss',
    $student['program'],
    $student['year_level'],
    $student['section'],
    $semester,
    $schoolYear
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $row['schedules'] = $row['schedules'] !== null
        ? explode('||', $row['schedules'])
        : [];

    $courses[] = $row;
}

$stmt->close();

$totalCourses = count($courses);
$totalUnits   = array_sum(array_column($courses, 'units'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | My Schedule</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* My Schedule only: retain the original desktop/tablet table. */
        .co-mobile-courses { display: none; }
        @media (max-width: 768px) {
            .co-table-wrap { display: none !important; }
            .co-mobile-courses { display: grid; gap: 12px; min-width: 0; margin: 14px 0; }
            .co-mobile-course, .co-mobile-empty {
                min-width: 0; padding: 15px; border: 1px solid #dce5f2;
                border-radius: 12px; background: #fff;
            }
            .co-mobile-course-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e6ecf5; }
            .co-mobile-course-title { flex: 1; min-width: 0; }
            .co-mobile-course-code { display: block; color: #3766a9; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
            .co-mobile-course-title h3 { margin: 0; font-size: 15px; line-height: 1.45; color: #142b4d; overflow-wrap: anywhere; }
            .co-mobile-units { flex: none; padding: 5px 8px; border-radius: 7px; background: #e9f1ff; color: #24569c; font-size: 11px; font-weight: 700; white-space: nowrap; }
            .co-mobile-course-detail { padding-top: 12px; min-width: 0; }
            .co-mobile-label { display: flex; align-items: center; gap: 7px; color: #667a99; font-size: 12px; margin-bottom: 5px; }
            .co-mobile-value { color: #142b4d; font-size: 13px; font-weight: 600; line-height: 1.6; overflow-wrap: anywhere; }
            .co-mobile-value .co-schedule-item { margin-bottom: 4px; white-space: normal; overflow-wrap: anywhere; }
            .co-mobile-empty { color: #667a99; text-align: center; }
        }
    </style>
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
                <h1>My Schedule</h1>
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
                            <strong><?= e($yearLevelLabel) ?></strong>
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

                                        <td class="col-num">
                                            <?= $i + 1 ?>
                                        </td>

                                        <td>
                                            <?= e($course['course_code']) ?>
                                        </td>

                                        <td>
                                            <?= e($course['course_name']) ?>
                                        </td>

                                        <td>

                                            <?php if (!empty($course['schedules'])): ?>

                                                <?php foreach ($course['schedules'] as $schedule): ?>

                                                    <div class="co-schedule-item">
                                                        <i class="fa-regular fa-clock"></i>
                                                        <?= e($schedule) ?>
                                                    </div>

                                                <?php endforeach; ?>

                                            <?php else: ?>

                                                <span class="co-not-assigned">
                                                    Not finalized
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if (!empty($course['instructor_name'])): ?>

                                                <?= e($course['instructor_name']) ?>

                                            <?php else: ?>

                                                <span class="co-not-assigned">
                                                    Not assigned
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td class="col-units">
                                            <?= e($course['units']) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- MOBILE COURSE CARDS: use the same dynamic course records as the desktop table. -->
                <div class="co-mobile-courses" aria-label="Assigned courses">
                    <?php if (empty($courses)): ?>
                        <div class="co-mobile-empty">No courses have been assigned to your section yet.</div>
                    <?php else: ?>
                        <?php foreach ($courses as $i => $course): ?>
                            <article class="co-mobile-course">
                                <div class="co-mobile-course-head">
                                    <div class="co-mobile-course-title">
                                        <span class="co-mobile-course-code"><?= e($course['course_code']) ?></span>
                                        <h3><?= e($course['course_name']) ?></h3>
                                    </div>
                                    <span class="co-mobile-units"><?= e($course['units']) ?> Units</span>
                                </div>
                                <div class="co-mobile-course-detail">
                                    <span class="co-mobile-label"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i> Schedule</span>
                                    <div class="co-mobile-value">
                                        <?php if (!empty($course['schedules'])): ?>
                                            <?php foreach ($course['schedules'] as $schedule): ?>
                                                <div class="co-schedule-item"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= e($schedule) ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="co-not-assigned">Not finalized</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="co-mobile-course-detail">
                                    <span class="co-mobile-label"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> Instructor</span>
                                    <div class="co-mobile-value">
                                        <?php if (!empty($course['instructor_name'])): ?>
                                            <?= e($course['instructor_name']) ?>
                                        <?php else: ?>
                                            <span class="co-not-assigned">Not assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
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