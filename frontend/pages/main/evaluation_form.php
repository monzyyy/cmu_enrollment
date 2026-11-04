
<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 3) . '/backend/helpers/evaluation_completion.php';
require_role('STUDENT');

$studentId = $_SESSION['student_id'] ?? null;

if (!$studentId) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

function evalEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function evalRedirect() {
    header('Location: ' . BASE_URL . '?page=faculty_evaluation');
    exit;
}

$studentStmt = $conn->prepare(
    "SELECT * FROM students WHERE student_id = ?"
);
$studentStmt->bind_param("i", $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$student) {
    evalRedirect();
}

$offeringId = filter_input(
    INPUT_GET,
    'course',
    FILTER_VALIDATE_INT
);

if (!$offeringId || $offeringId < 1) {
    evalRedirect();
}

// Find the current enrollment term.
$settings = $conn->query(
    "SELECT semester, school_year, enrollment_status
     FROM system_settings
     WHERE setting_id = 1"
)->fetch_assoc();

if (!$settings) {
    http_response_code(503);
    exit('Enrollment settings are unavailable.');
}

try {
    [$previousSemester, $previousSchoolYear] = evaluationPreviousTerm(
        $settings['semester'], $settings['school_year']
    );
} catch (RuntimeException $e) {
    http_response_code(503);
    exit('The enrollment term is not configured correctly.');
}


// Verify that this offering belongs to the student.
$offeringSql = "
    SELECT
        co.offering_id,
        co.instructor_name,
        co.program,
        co.year_level,
        co.section,
        c.course_code,
        c.course_name

    FROM student_course_enrollments sce

    INNER JOIN course_offerings co
        ON sce.offering_id = co.offering_id

    INNER JOIN courses c
        ON co.course_id = c.course_id

    WHERE sce.student_id = ?
      AND co.offering_id = ?
      AND co.semester = ?
      AND co.school_year = ?
    LIMIT 1
";


$offeringStmt = $conn->prepare($offeringSql);

$offeringStmt->bind_param(
    "iiss",
    $studentId,
    $offeringId,
    $previousSemester,
    $previousSchoolYear
);

$offeringStmt->execute();
$offering = $offeringStmt->get_result()->fetch_assoc();
$offeringStmt->close();

if (!$offering) {
    http_response_code(403);
    exit('This subject is not assigned to your section.');
}

if (trim($offering['instructor_name'] ?? '') === '') {
    http_response_code(403);
    exit('This subject is awaiting faculty assignment.');
}

// Check whether already evaluated.
$checkStmt = $conn->prepare(
    "SELECT evaluation_id
     FROM professor_evaluations
     WHERE student_id = ? AND offering_id = ?
     LIMIT 1"
);

$checkStmt->bind_param("ii", $studentId, $offeringId);
$checkStmt->execute();
$alreadyEvaluated = $checkStmt->get_result()->num_rows > 0;
$checkStmt->close();

// Load questionnaire.
$questionResult = $conn->query(
    "SELECT question_id, section_number,
            section_title, question_order,
            question_text, is_temporary
     FROM evaluation_questions
     WHERE is_active = 1
     ORDER BY section_number, question_order"
);

$sections = [];
$questionIds = [];

while ($question = $questionResult->fetch_assoc()) {
    $sectionNumber = (int)$question['section_number'];

    if (!isset($sections[$sectionNumber])) {
        $sections[$sectionNumber] = [
            'title' => $question['section_title'],
            'questions' => []
        ];
    }

    $sections[$sectionNumber]['questions'][] = $question;
    $questionIds[] = (int)$question['question_id'];
}

$error = '';
$submittedRatings = [];


if (empty($_SESSION['evaluation_csrf'])) {
    $_SESSION['evaluation_csrf'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedRatings = $_POST['ratings'] ?? [];

    
    if (
        !hash_equals(
            $_SESSION['evaluation_csrf'],
            (string)($_POST['csrf_token'] ?? '')
        )
    ) {
        $error = 'Invalid request. Please refresh the page.';
    } elseif ($alreadyEvaluated) {
        $error = 'You have already evaluated this subject.';
    } elseif ($settings['enrollment_status'] !== 'OPEN') {
        $error = 'The enrollment period is currently closed.';
    } elseif ($student['enrollment_phase'] !== 'NOT_STARTED') {
        $error = 'Faculty evaluation is not available in your current enrollment phase.';
    } elseif (count($questionIds) !== 20) {
        $error = 'The evaluation questionnaire is incomplete.';
    } elseif (!is_array($submittedRatings)) {
        $error = 'Invalid evaluation submission.';
        $submittedRatings = [];
    } else {
        // Validate every rating on the server.
        foreach ($questionIds as $questionId) {
            $value = $submittedRatings[$questionId] ?? null;

            if (
                !is_string($value) ||
                !in_array($value, ['1', '2', '3', '4', '5'], true)
            ) {
                $error = 'Please answer every question using ratings 1 to 5.';
                break;
            }
        }

        // Reject unexpected question IDs.
        if (!$error) {
            $expected = array_map('strval', $questionIds);
            $received = array_map('strval', array_keys($submittedRatings));

            sort($expected);
            sort($received);

            if ($expected !== $received) {
                $error = 'Invalid questionnaire answers.';
            }
        }

        if (!$error) {
            try {
                $conn->begin_transaction();

                // Recheck the professor and enrollment
                // state inside the transaction.
                $lockStmt = $conn->prepare(
                    "SELECT co.instructor_name
                     FROM course_offerings co
                     WHERE co.offering_id = ?
                       AND co.semester = ?
                       AND co.school_year = ?
                     FOR UPDATE"
                );
                $lockStmt->bind_param("iss", $offeringId, $previousSemester, $previousSchoolYear);
                $lockStmt->execute();
                $lockedOffering = $lockStmt->get_result()->fetch_assoc();
                $lockStmt->close();

                if (
                    !$lockedOffering ||
                    trim($lockedOffering['instructor_name'] ?? '') === ''
                ) {
                    throw new RuntimeException(
                        'Faculty assignment is no longer available.'
                    );
                }

                $professor = $lockedOffering['instructor_name'];

                $insertEval = $conn->prepare(
                    "INSERT INTO professor_evaluations
                     (student_id, offering_id,
                      instructor_name_at_submission)
                     VALUES (?, ?, ?)"
                );

                $insertEval->bind_param(
                    "iis",
                    $studentId,
                    $offeringId,
                    $professor
                );

                $insertEval->execute();
                $evaluationId = $conn->insert_id;
                $insertEval->close();

                $insertAnswer = $conn->prepare(
                    "INSERT INTO professor_evaluation_answers
                     (evaluation_id, question_id, rating)
                     VALUES (?, ?, ?)"
                );

                foreach ($questionIds as $questionId) {
                    $rating = (int)$submittedRatings[$questionId];

                    $insertAnswer->bind_param(
                        "iii",
                        $evaluationId,
                        $questionId,
                        $rating
                    );

                    $insertAnswer->execute();
                }

                
                    $insertAnswer->close();


                    // Lock the student record before phase progression.
                    $phaseStmt = $conn->prepare("
                        SELECT enrollment_phase
                        FROM students
                        WHERE student_id = ?
                        FOR UPDATE
                    ");

                    $phaseStmt->bind_param("i", $studentId);
                    $phaseStmt->execute();
                    $phaseRow = $phaseStmt->get_result()->fetch_assoc();
                    $phaseStmt->close();

                    if (!$phaseRow) {
                        throw new RuntimeException('Student record not found.');
                    }

                    $progress = checkEvaluationCompletion(
                        $conn,
                        $studentId,
                        $previousSemester,
                        $previousSchoolYear
                    );

                    if (
                        $phaseRow['enrollment_phase'] === 'NOT_STARTED' &&
                        $progress['all_evaluated']
                    ) {
                        $updateStmt = $conn->prepare("
                            UPDATE students
                            SET enrollment_phase = 'EVALUATION',
                                enrollment_status = 'Evaluation'
                            WHERE student_id = ?
                            AND enrollment_phase = 'NOT_STARTED'
                        ");

                        $updateStmt->bind_param("i", $studentId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }

                    $conn->commit();


                header(
                    'Location: ' . BASE_URL .
                    '?page=faculty_evaluation&submitted=1'
                );
                exit;

            } catch (Throwable $exception) {
                $conn->rollback();
                error_log($exception->getMessage());
                $error = 'Unable to save the evaluation. Please try again.';
            }
        }
    }
}


$userName = trim(
    $student['first_name'] . ' ' .
    (!empty($student['middle_name'])
        ? $student['middle_name'] . ' '
        : '') .
    $student['last_name']
);

$currentPage = 'faculty_evaluation';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMU | Faculty Evaluation Form</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Scoped to this page: shared main.css and other screens are unchanged. */
        /* Keep desktop navigation available while the long form scrolls. */
        @media (min-width: 769px) {
            .layout > .sidebar {
                position: fixed;
                top: 90px;
                left: 16px;
                width: var(--sidebar-w, 240px);
                height: calc(100vh - 106px);
                max-height: calc(100vh - 106px);
                overflow-y: auto;
                z-index: 30;
            }
        }
        .evaluation-container {
            width: 100%;
            min-width: 0;
            padding: clamp(18px, 2.3vw, 32px);
            border: 1px solid var(--border, #e3e9f2);
            border-radius: 10px;
            background: #fff;
            color: var(--text, #24344d);
            font-family: "DM Sans", sans-serif;
        }
        .evaluation-container * { box-sizing: border-box; }
        .evaluation-container h1, .evaluation-container h2, .evaluation-container h3,
        .evaluation-container p { margin-top: 0; }
        .eval-page-heading {
            display: flex; align-items: flex-start; justify-content: space-between;
            flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
        }
        .eval-eyebrow { color: #6680a3; font-size: 11px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; margin-bottom: 7px; }
        .eval-page-heading h1 { font-size: clamp(22px, 2vw, 27px); letter-spacing: -.045em; font-weight: 800; line-height: 1.25; margin-bottom: 7px; color: #1b3555; }
        .eval-page-heading p { color: #71829b; font-size: 13px; line-height: 1.65; margin-bottom: 0; }
        .eval-back-link { display: inline-flex; align-items: center; gap: 9px; padding: 10px 13px; border: 1px solid #dce5f1; border-radius: 9px; color: #48617f; background: #fff; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .eval-back-link:hover { color: #2f6fed; background: #f6f9ff; border-color: #c9daf8; }
        .eval-summary { border: 1px solid #e3eaf5; background: #fbfdff; border-radius: 14px; padding: clamp(17px, 2vw, 25px); margin-bottom: 22px; }
        .eval-summary-top { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .eval-summary-icon { display: grid; place-items: center; flex-shrink: 0; width: 45px; height: 45px; border-radius: 12px; background: #e8f0ff; color: #3972d7; font-size: 19px; }
        .eval-summary-top h2 { color: #213a5b; font-size: 16px; font-weight: 800; margin: 0 0 4px; }
        .eval-summary-top p { color: #8290a5; font-size: 12px; margin: 0; }
        .eval-term { margin-left: auto; border: 1px solid #d8e6fb; background: #eef5ff; border-radius: 8px; padding: 8px 11px; color: #3263a7; font-size: 11px; font-weight: 700; text-align: right; }
        .eval-term span { display: block; color: #7c94b5; font-size: 10px; font-weight: 600; margin-bottom: 2px; }
        .eval-faculty-info { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .eval-info-tile { padding: 14px 15px; border: 1px solid #e7edf6; border-radius: 10px; background: #fff; min-width: 0; }
        .eval-info-tile span { display: block; color: #8795aa; font-size: 11px; margin-bottom: 6px; }
        .eval-info-tile strong { display: block; font-size: 13px; color: #253d5e; overflow-wrap: anywhere; font-weight: 750; }
        .eval-instructions { display: flex; align-items: flex-start; gap: 12px; padding: 15px 17px; background: #eff6ff; border: 1px solid #e0ecfd; border-radius: 10px; margin-top: 15px; }
        .eval-instructions > i { color: #4c82d6; font-size: 16px; margin-top: 2px; }
        .eval-instructions strong { color: #2a4b77; font-size: 12px; }
        .eval-instructions p { font-size: 12px; color: #627995; line-height: 1.65; margin: 4px 0 0; }
        .eval-scale { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 11px; }
        .eval-scale span { padding: 6px 9px; background: #fff; border: 1px solid #dce8f8; border-radius: 7px; color: #587394; font-size: 11px; }
        .eval-scale b { color: #2f6fed; }
        .eval-progress { margin-bottom: 22px; padding: 15px 18px; background: #fff; border: 1px solid #e5ebf4; border-radius: 11px; }
        .eval-progress-top { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 10px; font-size: 12px; font-weight: 700; color: #3c5677; }
        .eval-progress-count { color: #2f6fed; font-variant-numeric: tabular-nums; }
        .eval-progress-track { height: 7px; border-radius: 99px; background: #eaf0f8; overflow: hidden; }
        .eval-progress-fill { height: 100%; width: 0; background: #4384ed; border-radius: inherit; transition: width .2s ease; }
        .eval-section { border: 1px solid #e4ebf5; background: #fff; border-radius: 13px; overflow: hidden; margin-bottom: 20px; }
        .eval-section-head { display: flex; align-items: center; gap: 13px; padding: 18px 21px; background: #f7faff; border-bottom: 1px solid #e6edf7; }
        .eval-section-number { width: 36px; height: 36px; border-radius: 10px; background: #e7f0ff; color: #2f6fed; display: grid; place-items: center; font-size: 13px; font-weight: 800; flex-shrink: 0; }
        .eval-section-head h3 { font-size: 15px; font-weight: 800; color: #253f61; margin: 0 0 4px; }
        .eval-section-head p { color: #8a9ab0; font-size: 11px; margin: 0; }
        .eval-section-count { margin-left: auto; color: #6480a2; font-size: 11px; white-space: nowrap; background: #fff; border: 1px solid #e3eaf4; border-radius: 7px; padding: 6px 9px; }
        .eval-question { padding: 21px; border-bottom: 1px solid #edf1f7; }
        .eval-question:last-child { border-bottom: 0; }
        .eval-question-heading { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px; }
        .eval-question-number { display: inline-grid; place-items: center; min-width: 27px; height: 27px; border-radius: 7px; background: #f0f5fc; color: #5d7b9f; font-size: 11px; font-weight: 800; }
        .eval-question-heading p { color: #263c59; font-size: 13px; line-height: 1.65; font-weight: 700; margin: 3px 0 0; }
        .eval-rating-options { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 9px; }
        .eval-rating-choice { position: relative; cursor: pointer; min-width: 0; }
        .eval-rating-choice input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .eval-rating-choice-content { display: flex; align-items: center; justify-content: center; flex-direction: column; min-height: 70px; padding: 11px 6px; gap: 5px; background: #fbfcff; border: 1px solid #e2eaf5; border-radius: 9px; transition: background .15s, border-color .15s, box-shadow .15s; text-align: center; }
        .eval-rating-choice-content strong { font-size: 16px; color: #45627f; line-height: 1; }
        .eval-rating-choice-content small { color: #8999ad; font-size: 10px; line-height: 1.35; }
        .eval-rating-choice:hover .eval-rating-choice-content { border-color: #adc9f6; background: #f4f8ff; }
        .eval-rating-choice input:checked + .eval-rating-choice-content { background: #eaf2ff; border-color: #4c87eb; box-shadow: inset 0 0 0 1px #4c87eb; }
        .eval-rating-choice input:checked + .eval-rating-choice-content strong,
        .eval-rating-choice input:checked + .eval-rating-choice-content small { color: #2f65c2; }
        .eval-rating-choice input:focus-visible + .eval-rating-choice-content { outline: 3px solid #a8c8ff; outline-offset: 2px; }
        .eval-actions { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-top: 1px solid #e9eef6; padding-top: 21px; }
        .eval-actions-note { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #8393a8; line-height: 1.5; }
        .eval-submit { border: 0; background: #2f6fed; color: #fff; border-radius: 9px; padding: 12px 19px; font-size: 12px; font-weight: 800; cursor: pointer; display: inline-flex; gap: 9px; align-items: center; justify-content: center; }
        .eval-submit:hover:not(:disabled) { background: #245dd1; }
        .eval-submit:disabled { background: #aebcd1; cursor: not-allowed; }
        .eval-error { display: flex; align-items: flex-start; gap: 10px; background: #fff2f2; color: #9f3b3b; border: 1px solid #ffd8d8; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; font-size: 13px; }
        .eval-completed { padding: 28px; background: #f5fbf8; border: 1px solid #dcefe5; border-radius: 13px; text-align: center; }
        .eval-completed i { font-size: 27px; color: #2fb872; margin-bottom: 13px; }
        .eval-completed h2 { color: #244d3b; font-size: 18px; margin-bottom: 8px; }
        .eval-completed p { color: #658275; font-size: 13px; }
        .eval-completed a { display: inline-block; padding: 11px 16px; background: #fff; border: 1px solid #cce6d7; border-radius: 8px; font-size: 12px; font-weight: 700; color: #2c7251; }
        @media (max-width: 1050px) { .eval-faculty-info { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 680px) {
            .evaluation-container { padding: 16px; }
            .eval-faculty-info { grid-template-columns: 1fr 1fr; gap: 8px; }
            .eval-info-tile { padding: 11px; }
            .eval-summary-top { flex-wrap: wrap; }
            .eval-term { margin-left: 0; }
            .eval-section-head { padding: 15px; }
            .eval-question { padding: 16px 14px; }
            .eval-rating-options { gap: 5px; }
            .eval-rating-choice-content { padding: 9px 3px; min-height: 64px; }
            .eval-rating-choice-content small { font-size: 9px; }
            .eval-actions { align-items: stretch; }
            .eval-submit { width: 100%; }
        }
        @media (max-width: 400px) {
            .eval-faculty-info { grid-template-columns: 1fr; }
            .eval-rating-choice-content small { font-size: 8px; }
            .eval-section-count { display: none; }
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="imglogo"><span>CMU</span></div>
    <?php require FRONTEND_PATH . 'includes/sidebar.php'; ?>
    <div class="overlay" id="overlay"></div>
    <div class="main">
        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>
        <main class="evaluation-container">
            <div class="eval-page-heading">
                <div>
                    <div class="eval-eyebrow">Enrollment / Faculty Evaluation</div>
                    <h1>Faculty Evaluation Form</h1>
                    <p>Rate your instructor based on your learning experience during the previous semester.</p>
                </div>
                <a class="eval-back-link" href="<?= BASE_URL ?>?page=faculty_evaluation"><i class="fa-solid fa-arrow-left"></i> Back to Evaluation</a>
            </div>
            <section class="eval-summary" aria-label="Subject and professor details">
                <div class="eval-summary-top">
                    <div class="eval-summary-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div>
                        <h2>Subject Information</h2>
                        <p>Review the instructor and subject details before answering.</p>
                    </div>
                    <div class="eval-term"><span>Previous academic term</span><?= evalEscape($previousSemester) ?> · <?= evalEscape($previousSchoolYear) ?></div>
                </div>
                <div class="eval-faculty-info">
                    <div class="eval-info-tile"><span>Subject Code</span><strong><?= evalEscape($offering['course_code']) ?></strong></div>
                    <div class="eval-info-tile"><span>Subject Title</span><strong><?= evalEscape($offering['course_name']) ?></strong></div>
                    <div class="eval-info-tile"><span>Instructor</span><strong><?= evalEscape($offering['instructor_name']) ?></strong></div>
                    <div class="eval-info-tile"><span>Section</span><strong><?= evalEscape($offering['section']) ?></strong></div>
                </div>
                <div class="eval-instructions">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <div>
                        <strong>How to answer</strong>
                        <p>Choose one rating for each statement. Please answer all questions before submitting your evaluation.</p>
                        <div class="eval-scale">
                            <span><b>5</b> Outstanding</span>
                            <span><b>4</b> Very Satisfactory</span>
                            <span><b>3</b> Satisfactory</span>
                            <span><b>2</b> Unsatisfactory</span>
                            <span><b>1</b> Very Unsatisfactory</span>
                        </div>
                    </div>
                </div>
            </section>
            <?php if ($error): ?>
                <div class="eval-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= evalEscape($error) ?></div>
            <?php endif; ?>
            <?php if ($alreadyEvaluated): ?>
                <div class="eval-completed">
                    <i class="fa-solid fa-circle-check"></i>
                    <h2>Evaluation Completed</h2>
                    <p>You have already submitted your evaluation for this subject.</p>
                    <a href="<?= BASE_URL ?>?page=faculty_evaluation">Return to Faculty Evaluation</a>
                </div>
            <?php else: ?>
                <form method="POST" action="<?= BASE_URL ?>?page=evaluation_form&amp;course=<?= (int)$offeringId ?>" id="facultyEvaluationForm">
                    <input type="hidden" name="csrf_token" value="<?= evalEscape($_SESSION['evaluation_csrf']) ?>">
                    <div class="eval-progress" aria-live="polite">
                        <div class="eval-progress-top"><span><i class="fa-solid fa-list-check"></i> Evaluation Progress</span><span class="eval-progress-count"><span id="evalAnswered">0</span> of <?= count($questionIds) ?> answered</span></div>
                        <div class="eval-progress-track" role="progressbar" aria-label="Questions answered" aria-valuemin="0" aria-valuemax="<?= count($questionIds) ?>" aria-valuenow="0" id="evalProgressBar"><div class="eval-progress-fill" id="evalProgressFill"></div></div>
                    </div>
                    <?php foreach ($sections as $sectionNumber => $section): ?>
                        <section class="eval-section">
                            <div class="eval-section-head">
                                <div class="eval-section-number"><?= (int)$sectionNumber ?></div>
                                <div><h3><?= evalEscape($section['title']) ?></h3><p>Choose the rating that best reflects your experience.</p></div>
                                <span class="eval-section-count"><?= count($section['questions']) ?> questions</span>
                            </div>
                            <?php foreach ($section['questions'] as $index => $question): ?>
                                <?php $questionId = (int)$question['question_id']; ?>
                                <div class="eval-question">
                                    <div class="eval-question-heading">
                                        <span class="eval-question-number"><?= (int)($index + 1) ?></span>
                                        <p id="eval-question-<?= $questionId ?>"><?= evalEscape($question['question_text']) ?></p>
                                    </div>
                                    <div class="eval-rating-options" role="radiogroup" aria-labelledby="eval-question-<?= $questionId ?>">
                                        <?php
                                        $ratingLabels = [5 => 'Outstanding', 4 => 'Very Satisfactory', 3 => 'Satisfactory', 2 => 'Unsatisfactory', 1 => 'Very Unsatisfactory'];
                                        for ($rating = 5; $rating >= 1; $rating--):
                                        ?>
                                            <label class="eval-rating-choice">
                                                <input type="radio" name="ratings[<?= $questionId ?>]" value="<?= $rating ?>" required
                                                    <?= ((string)($submittedRatings[$questionId] ?? '') === (string)$rating) ? 'checked' : '' ?>
                                                    aria-label="<?= $rating ?> — <?= evalEscape($ratingLabels[$rating]) ?>">
                                                <span class="eval-rating-choice-content"><strong><?= $rating ?></strong><small><?= evalEscape($ratingLabels[$rating]) ?></small></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                    <div class="eval-actions">
                        <span class="eval-actions-note"><i class="fa-solid fa-shield-halved"></i> Your responses will be saved when you submit.</span>
                        <button class="eval-submit" type="submit"
                            <?= ($settings['enrollment_status'] !== 'OPEN' || $student['enrollment_phase'] !== 'NOT_STARTED' || count($questionIds) !== 20) ? 'disabled' : '' ?>>
                            Submit Evaluation <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>
<script>
(function () {
    const form = document.getElementById('facultyEvaluationForm');
    if (!form) return;
    const total = <?= count($questionIds) ?>;
    const answered = document.getElementById('evalAnswered');
    const fill = document.getElementById('evalProgressFill');
    const bar = document.getElementById('evalProgressBar');
    function updateProgress() {
        const selected = form.querySelectorAll('.eval-rating-choice input:checked').length;
        answered.textContent = selected;
        fill.style.width = (total ? selected / total * 100 : 0) + '%';
        bar.setAttribute('aria-valuenow', String(selected));
    }
    form.addEventListener('change', updateProgress);
    updateProgress();
})();
</script>
</body>
</html>
