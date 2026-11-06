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
    SELECT semester, school_year, enrollment_status
    FROM system_settings
    WHERE setting_id = 1
    LIMIT 1
");

$settings = $settingsQuery->fetch_assoc();

$semester = $settings['semester'] ?? '';
$schoolYear = $settings['school_year'] ?? '';
$evaluationOpen = (($settings['enrollment_status'] ?? '') === 'OPEN');
try {
    [$previousSemester, $previousSchoolYear] = evaluationPreviousTerm($semester, $schoolYear);
} catch (RuntimeException $e) {
    $previousSemester = '';
    $previousSchoolYear = '';
}



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

    FROM student_course_enrollments sce

    INNER JOIN course_offerings co
        ON sce.offering_id = co.offering_id

    INNER JOIN courses c
        ON co.course_id = c.course_id

    LEFT JOIN professor_evaluations pe
        ON pe.offering_id = co.offering_id
        AND pe.student_id = sce.student_id

    WHERE sce.student_id = ?
      AND co.semester = ?
      AND co.school_year = ?

    ORDER BY c.course_code ASC
";


$stmt = $conn->prepare($sql);

$stmt->bind_param("iss", $studentId, $previousSemester, $previousSchoolYear);

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
                    Evaluate your professors from the previous semester. Complete all required evaluations to unlock the Clearance process.
                </p>
            </section>


            <?php if (!$evaluationOpen): ?>
                <div role="status" style="padding: 14px 18px; margin-bottom: 18px; background: #fff4e5; border: 1px solid #f1d5a7; border-radius: 10px; color: #754c14;">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <strong>Faculty Evaluation Unavailable.</strong>
                    The enrollment period is currently closed. You can review your subjects and evaluation progress, but new evaluations are unavailable until the Registrar opens enrollment.
                </div>
            <?php endif; ?>

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
                                            <td class="col-num" data-label="No."><?= $i + 1 ?></td>
                                            <td data-label="Course ID"><?= e($subject['code']) ?></td>
                                            <td data-label="Course Name"><?= e($subject['name']) ?></td>
                                            <td data-label="Instructor"><?= e($subject['instructor']) ?></td>
                                            <td class="col-status" data-label="Status">
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
                                            
                                        <td class="col-action" data-label="Action">

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

                                            <?php elseif (!$evaluationOpen): ?>

                                                <span class="eval-button" aria-disabled="true"
                                                      title="Enrollment is currently closed"
                                                      style="opacity: 0.5; cursor: not-allowed;">
                                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                                    Enrollment Closed
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