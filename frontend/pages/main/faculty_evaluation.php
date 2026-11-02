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

$currentPage = 'faculty_evaluation';


/* ------------------------------------------
   DYNAMIC FACULTY EVALUATION SUBJECTS
------------------------------------------ */

// Get current semester and school year
$settingsQuery = $conn->query("
    SELECT semester, school_year
    FROM system_settings
    WHERE setting_id = 1
    LIMIT 1
");

$settings = $settingsQuery->fetch_assoc();

$semester = $settings['semester'] ?? '';
$schoolYear = $settings['school_year'] ?? '';

// Get subjects for the logged-in student
$sql = "
    SELECT
        co.offering_id AS id,
        c.course_code AS code,
        c.course_name AS name,
        co.instructor_name AS instructor,

        CASE
            WHEN pe.evaluation_id IS NOT NULL
            THEN 1
            ELSE 0
        END AS evaluated

    FROM course_offerings co

    INNER JOIN courses c
        ON co.course_id = c.course_id

    LEFT JOIN professor_evaluations pe
        ON pe.offering_id = co.offering_id
        AND pe.student_id = ?

    WHERE co.program = ?
        AND co.year_level = ?
        AND co.section = ?
        AND co.semester = ?
        AND co.school_year = ?
        AND c.is_active = 1

    ORDER BY c.course_code ASC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isisss",
    $studentId,
    $student['program'],
    $student['year_level'],
    $student['section'],
    $semester,
    $schoolYear
);

$stmt->execute();

$result = $stmt->get_result();

$subjects = [];

while ($row = $result->fetch_assoc()) {

    $hasInstructor = !empty(
        trim($row['instructor'] ?? '')
    );

    $subjects[] = [
        'id' => (int) $row['id'],
        'code' => $row['code'],
        'name' => $row['name'],
        'instructor' => $hasInstructor
            ? $row['instructor']
            : 'Awaiting Faculty Assignment',
        'has_instructor' => $hasInstructor,
        'evaluated' => (bool) $row['evaluated']
    ];
}

$stmt->close();

// Calculate actual evaluation progress
$totalSubjects = count($subjects);

$evaluatedCount = count(
    array_filter(
        $subjects,
        fn($subject) => $subject['evaluated']
    )
);

$progressPercent = $totalSubjects > 0
    ? round(($evaluatedCount / $totalSubjects) * 100)
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Faculty Evaluation</title>

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
                <h1>Faculty Evaluation</h1>
                <p>
                    Rate your instructors for the current semester.
                    You need to evaluate every subject before you can move on to clearance.
                </p>
            </section>


            <!-- =========================
                 EVALUATION CARD
            ========================== -->
            <section class="co-card">

                <!-- SUMMARY ROW -->
                <div class="ev-summary">

                    <!-- Current phase + progress -->
                    <div class="ev-phase">

                        <div class="ev-phase-info">

                            <span class="co-icon"><i class="fa-regular fa-calendar"></i></span>

                            <div>
                                <small>Current Semester</small>
                                <h2>Professor Evaluation</h2>
                                <p>Please complete the evaluation forms for your enrolled subjects.</p>
                            </div>

                        </div>

                        <div class="ev-progress">
                            <strong><?= e($evaluatedCount) ?> of <?= e($totalSubjects) ?></strong>

                            <div class="ev-progress-bar"
                                 role="progressbar"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-valuenow="<?= e($progressPercent) ?>">
                                <div class="ev-progress-fill" style="width: <?= e($progressPercent) ?>%"></div>
                            </div>

                            <span>Evaluation Progress</span>
                        </div>

                    </div>

                    <!-- About -->
                    <div class="ev-about">

                        <h2>
                            <i class="fa-solid fa-graduation-cap"></i>
                            About Evaluation
                        </h2>

                        <p>
                            The professor evaluation allows your instructors to assess their
                            performance in their respective subjects. Make sure to complete all
                            evaluations before proceeding to the next phase.
                        </p>

                    </div>

                </div>


                <!-- SUBJECTS -->
                <div class="ev-subjects">

                    <div class="ev-subjects-head">
                        <h2>Your Subjects</h2>
                        <p>Complete the evaluation form for each subject below.</p>
                    </div>

                    <div class="co-table-wrap">

                        <table class="co-table ev-table">

                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th>Course ID</th>
                                    <th>Course Name</th>
                                    <th>Instructor</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-action">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (empty($subjects)): ?>

                                    <tr>
                                        <td colspan="6" class="co-empty">
                                            There are no subjects to evaluate yet.
                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($subjects as $i => $subject): ?>

                                        <tr>
                                            <td class="col-num"><?= $i + 1 ?></td>
                                            <td><?= e($subject['code']) ?></td>
                                            <td><?= e($subject['name']) ?></td>
                                            <td><?= e($subject['instructor']) ?></td>
                                            <td class="col-status">
                                                <?php if (!$subject['has_instructor']): ?>
                                                    <span class="eval-badge eval-pending">
                                                        <i class="fa-solid fa-clock"></i>
                                                        Awaiting Faculty Assignment
                                                    </span>
                                                <?php elseif ($subject['evaluated']): ?>
                                                    <span class="eval-badge eval-done">
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        Evaluated
                                                    </span>
                                                <?php else: ?>
                                                    <span class="eval-badge eval-pending">
                                                        <i class="fa-solid fa-circle"></i>
                                                        Not Yet Evaluated
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            
                                        <td class="col-action">

                                            <?php if (!$subject['has_instructor']): ?>

                                                <span class="eval-button"
                                                    style="opacity: 0.5; cursor: not-allowed;">
                                                    Awaiting Assignment
                                                </span>

                                            <?php elseif ($subject['evaluated']): ?>

                                                <span class="eval-button"
                                                    style="opacity: 0.5; cursor: not-allowed;">
                                                    <i class="fa-solid fa-check"></i>
                                                    Completed
                                                </span>

                                            <?php else: ?>

                                                <a
                                                    href="<?= BASE_URL ?>?page=evaluation_form&amp;course=<?= (int)$subject['id'] ?>"
                                                    class="eval-button"
                                                >
                                                    <i class="fa-solid fa-pencil"></i>
                                                    Evaluate
                                                </a>

                                            <?php endif; ?>

                                        </td>


                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>
</html>