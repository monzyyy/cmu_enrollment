
<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
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
    FROM course_offerings co
    INNER JOIN courses c ON c.course_id = co.course_id
    WHERE co.offering_id = ?
      AND co.program = ?
      AND co.year_level = ?
      AND co.section = ?
      AND co.semester = ?
      AND co.school_year = ?
      AND c.is_active = 1
    LIMIT 1
";

$offeringStmt = $conn->prepare($offeringSql);

$offeringStmt->bind_param(
    "isisss",
    $offeringId,
    $student['program'],
    $student['year_level'],
    $student['section'],
    $settings['semester'],
    $settings['school_year']
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
                     FOR UPDATE"
                );
                $lockStmt->bind_param("i", $offeringId);
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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>CMU | Faculty Evaluation Form</title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .evaluation-container {
            max-width: 950px;
            margin: 30px auto;
            padding: 0 20px 50px;
            font-family: Arial, sans-serif;
        }

        .evaluation-header,
        .evaluation-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .evaluation-header h2 {
            margin-bottom: 15px;
        }

        .faculty-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .faculty-info p {
            margin: 5px 0;
        }

        .rating-guide {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            line-height: 1.8;
        }

        .evaluation-section h3 {
            margin-bottom: 20px;
            color: #183c30;
        }

        .evaluation-question {
            padding: 18px 0;
            border-bottom: 1px solid #eee;
        }

        .evaluation-question:last-child {
            border-bottom: none;
        }

        .evaluation-question p {
            font-weight: 600;
            margin-bottom: 14px;
        }

        .rating-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .rating-options label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .rating-options input {
            accent-color: #166534;
        }

        .evaluation-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
        }

        .evaluation-actions a,
        .evaluation-actions button {
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .back-button {
            background: #e5e7eb;
            color: #111827;
        }

        .submit-button {
            background: #166534;
            color: white;
            border: none;
        }

        .submit-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .evaluation-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .faculty-info {
                grid-template-columns: 1fr;
            }

            .rating-options {
                gap: 12px;
            }

            .evaluation-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<div class="layout">

    <div class="imglogo">
        <span>CMU</span>
    </div>

    <?php require FRONTEND_PATH . 'includes/sidebar.php'; ?>

    <div class="overlay" id="overlay"></div>

    <div class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>

        <div class="evaluation-container">

            <div class="evaluation-header">
                <h2>Faculty Evaluation Form</h2>

                <p>
                    Please evaluate your professor based on
                    your learning experience.
                </p>

                <div class="faculty-info">
                    <div>
                        <strong>Subject Code</strong>
                        <p><?= evalEscape($offering['course_code']) ?></p>
                    </div>

                    <div>
                        <strong>Subject Title</strong>
                        <p><?= evalEscape($offering['course_name']) ?></p>
                    </div>

                    <div>
                        <strong>Professor</strong>
                        <p><?= evalEscape($offering['instructor_name']) ?></p>
                    </div>

                    <div>
                        <strong>Section</strong>
                        <p><?= evalEscape($offering['section']) ?></p>
                    </div>
                </div>

                <div class="rating-guide">
                    <strong>Rating Scale</strong>
                    <div>5 — Outstanding</div>
                    <div>4 — Very Satisfactory</div>
                    <div>3 — Satisfactory</div>
                    <div>2 — Unsatisfactory</div>
                    <div>1 — Very Unsatisfactory</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="evaluation-error">
                    <?= evalEscape($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($alreadyEvaluated): ?>

                <div class="evaluation-section">
                    <h3>Evaluation Completed</h3>
                    <p>
                        You have already submitted your evaluation
                        for this subject.
                    </p>

                    <a href="<?= BASE_URL ?>?page=faculty_evaluation">
                        Return to Faculty Evaluation
                    </a>
                </div>

            <?php else: ?>

                <form method="POST" action="<?= BASE_URL ?>?page=evaluation_form&amp;course=<?= (int)$offeringId ?>">
                    <input type="hidden" name="csrf_token" value="<?= evalEscape($_SESSION['evaluation_csrf']) ?>" >


                    <?php foreach ($sections as $sectionNumber => $section): ?>

                        <div class="evaluation-section">

                            <h3>
                                Section <?= (int)$sectionNumber ?>:
                                <?= evalEscape($section['title']) ?>
                            </h3>

                            <?php foreach ($section['questions'] as $index => $question): ?>

                                <?php
                                    $questionId = (int)$question['question_id'];
                                ?>

                                <div class="evaluation-question">

                                    <p>
                                        <?= $index + 1 ?>.
                                        <?= evalEscape($question['question_text']) ?>
                                    </p>

                                    <div class="rating-options">

                                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>

                                            <label>
                                                <input
                                                    type="radio"
                                                    name="ratings[<?= $questionId ?>]"
                                                    value="<?= $rating ?>"
                                                    required
                                                    <?= (
                                                        (string)($submittedRatings[$questionId] ?? '')
                                                        === (string)$rating
                                                    ) ? 'checked' : '' ?>
                                                >
                                                <?= $rating ?>
                                            </label>

                                        <?php endfor; ?>

                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endforeach; ?>

                    <div class="evaluation-actions">

                        <a class="back-button"
                           href="<?= BASE_URL ?>?page=faculty_evaluation">
                            Back
                        </a>

                        <button class="submit-button"
                                type="submit"
                                <?= (
                                    $settings['enrollment_status'] !== 'OPEN' ||
                                    $student['enrollment_phase'] !== 'NOT_STARTED' ||
                                    count($questionIds) !== 20
                                ) ? 'disabled' : '' ?>>
                            Submit Evaluation
                        </button>

                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
