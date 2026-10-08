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

/* ---------------------------------------------------------
   SUBJECTS TO EVALUATE
   TODO: replace this sample array with a query that joins the
   student's courses with their evaluation records, e.g.

   $stmt = $conn->prepare(
       'SELECT c.course_id, c.course_code, c.course_name, c.instructor,
              (e.evaluation_id IS NOT NULL) AS evaluated
        FROM course_offerings c
        LEFT JOIN evaluations e
               ON e.course_id = c.course_id AND e.student_id = ?
        WHERE c.section_id = ?
        ORDER BY c.course_code'
   );
--------------------------------------------------------- */
$subjects = [
    ['id' => 1, 'code' => 'IT 301', 'name' => 'Database System',                     'instructor' => 'Prof. Juan Dela Cruz',  'evaluated' => true],
    ['id' => 2, 'code' => 'IT 302', 'name' => 'Web Development',                     'instructor' => 'Prof. Ronald Pineda',   'evaluated' => true],
    ['id' => 3, 'code' => 'IT 303', 'name' => 'System Analysis and Design',          'instructor' => 'Prof. Sanggre Alena',   'evaluated' => true],
    ['id' => 4, 'code' => 'IT 304', 'name' => 'Networking 1',                        'instructor' => 'Prof. Fhukerat',        'evaluated' => true],
    ['id' => 5, 'code' => 'IT 305', 'name' => 'System Integration and Architecture', 'instructor' => 'Doc. Ryzza Mae Digong', 'evaluated' => true],
    ['id' => 6, 'code' => 'IT 306', 'name' => 'Integrative Programming',             'instructor' => 'Atty. Pepsi Paloma',    'evaluated' => true],
    ['id' => 7, 'code' => 'GE 101', 'name' => 'Purposive Communication',             'instructor' => 'Prof. Uncle Dags',      'evaluated' => true],
 ['id' => 8, 'code' => 'PE 102', 'name' => 'Physical Fitness 1',                  'instructor' => 'Prof. Maria Hiwaga',    'evaluated' => false],
];

$totalSubjects     = count($subjects);
$evaluatedCount    = count(array_filter($subjects, fn($s) => $s['evaluated']));
$progressPercent   = $totalSubjects > 0 ? round(($evaluatedCount / $totalSubjects) * 100) : 0;

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
                                                <?php if ($subject['evaluated']): ?>
                                                    <span class="eval-badge eval-done">Evaluated</span>
                                                <?php else: ?>
                                                    <span class="eval-badge eval-pending">
                                                        <i class="fa-solid fa-circle"></i>
                                                        Not yet evaluated
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="col-action">
                                                <a href="<?= BASE_URL ?>?page=evaluation_form&amp;course=<?= (int) $subject['id'] ?>"
                                                   class="eval-button">
                                                    <i class="fa-solid fa-pencil"></i>
                                                    Evaluate
                                                </a>
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