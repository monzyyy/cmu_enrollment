<?php

require_role('ADMIN');

$currentPage = 'student_enrollment';

/* STUDENT COUNTS */

$totalStudents = 0;
$notStartedStudents = 0;
$evaluationStudents = 0;
$clearanceStudents = 0;
$corStudents = 0;
$enrolledStudents = 0;

$result = $conn->query(
    "SELECT
        COUNT(*) AS total_students,
        SUM(enrollment_phase = 'NOT_STARTED') AS not_started_students,
        SUM(enrollment_phase = 'EVALUATION') AS evaluation_students,
        SUM(enrollment_phase = 'CLEARANCE') AS clearance_students,
        SUM(enrollment_phase = 'COR') AS cor_students,
        SUM(enrollment_phase = 'ENROLLED') AS enrolled_students
     FROM students"
);

if ($result) {
    $counts = $result->fetch_assoc();

    $totalStudents = (int) ($counts['total_students'] ?? 0);
    $notStartedStudents = (int) ($counts['not_started_students'] ?? 0);
    $evaluationStudents = (int) ($counts['evaluation_students'] ?? 0);
    $clearanceStudents = (int) ($counts['clearance_students'] ?? 0);
    $corStudents = (int) ($counts['cor_students'] ?? 0);
    $enrolledStudents = (int) ($counts['enrolled_students'] ?? 0);
}


/* STUDENT LIST */

/* SEARCH + PAGINATION */

$search = trim($_GET['search'] ?? '');

$studentsPerPage = 10;

$currentStudentPage = max(
    1,
    (int) ($_GET['student_page'] ?? 1)
);


/* COUNT STUDENTS */

if ($search !== '') {

    $countStmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM students
         WHERE
            student_number LIKE ?
            OR first_name LIKE ?
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR program LIKE ?
            OR section LIKE ?'
    );

    $searchValue = '%' . $search . '%';

    $countStmt->bind_param(
        'ssssss',
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    );

} else {

    $countStmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM students'
    );
}

$countStmt->execute();

$countResult = $countStmt->get_result()->fetch_assoc();

$totalFilteredStudents = (int) ($countResult['total'] ?? 0);

$countStmt->close();


/* PAGINATION */

$totalStudentPages = max(
    1,
    (int) ceil($totalFilteredStudents / $studentsPerPage)
);

$currentStudentPage = min(
    $currentStudentPage,
    $totalStudentPages
);

$studentOffset = (
    $currentStudentPage - 1
) * $studentsPerPage;


/* GET STUDENTS */

if ($search !== '') {

    $stmt = $conn->prepare(
        'SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            program,
            year_level,
            section,
            enrollment_phase
         FROM students
         WHERE
            student_number LIKE ?
            OR first_name LIKE ?
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR program LIKE ?
            OR section LIKE ?
         ORDER BY last_name, first_name
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param(
        'ssssssii',
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $studentsPerPage,
        $studentOffset
    );

} else {

    $stmt = $conn->prepare(
        'SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            program,
            year_level,
            section,
            enrollment_phase
         FROM students
         ORDER BY last_name, first_name
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param(
        'ii',
        $studentsPerPage,
        $studentOffset
    );
}

$stmt->execute();

$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/* PHASE LABEL */

function enrollment_phase_label(string $phase): string
{
    return match ($phase) {
        'NOT_STARTED' => 'Not Started',
        'EVALUATION' => 'Evaluation',
        'CLEARANCE' => 'Clearance',
        'COR' => 'COR',
        'ENROLLED' => 'Enrolled',
        default => 'Unknown',
    };
}


/* PHASE CLASS */

function enrollment_phase_class(string $phase): string
{
    return match ($phase) {
        'NOT_STARTED' => 'admin-phase-not-started',
        'EVALUATION' => 'admin-phase-evaluation',
        'CLEARANCE' => 'admin-phase-clearance',
        'COR' => 'admin-phase-cor',
        'ENROLLED' => 'admin-phase-enrolled',
        default => '',
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>CMU | Student Enrollment</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/admin.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="layout">

    <div class="imglogo">
        <span>CMU</span>
    </div>

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

    <div class="overlay" id="overlay"></div>

    <div class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>

        <div class="content">

            <!-- PAGE HEADER -->

            <section class="admin-student-enrollment-header">

                <div>
                    <h1>Student Enrollment</h1>

                    <p>
                        Monitor students' progress throughout the enrollment process.
                    </p>
                </div>

            </section>


            <!-- SUMMARY -->

            <section class="admin-enrollment-summary">

                <div class="admin-enrollment-summary-card">

                    <span>Total Students</span>

                    <strong>
                        <?= $totalStudents ?>
                    </strong>

                </div>


                <div class="admin-enrollment-summary-card">

                    <span>Not Started</span>

                    <strong>
                        <?= $notStartedStudents ?>
                    </strong>

                </div>


                <div class="admin-enrollment-summary-card">

                    <span>Evaluation</span>

                    <strong>
                        <?= $evaluationStudents ?>
                    </strong>

                </div>


                <div class="admin-enrollment-summary-card">

                    <span>Clearance</span>

                    <strong>
                        <?= $clearanceStudents ?>
                    </strong>

                </div>


                <div class="admin-enrollment-summary-card">

                    <span>COR</span>

                    <strong>
                        <?= $corStudents ?>
                    </strong>

                </div>


                <div class="admin-enrollment-summary-card">

                    <span>Enrolled</span>

                    <strong>
                        <?= $enrolledStudents ?>
                    </strong>

                </div>

            </section>


            <!-- STUDENT TABLE -->

            <section class="admin-student-enrollment-card">

                <div class="admin-student-enrollment-card-header">

                    <div>
                        <h2>Student Enrollment Progress</h2>

                        <p>
                            View the current enrollment phase of each student.
                        </p>
                    </div>

                    <form
                        method="GET"
                        action="<?= e(BASE_URL) ?>"
                        class="admin-student-enrollment-search"
                    >

                        <input
                            type="hidden"
                            name="page"
                            value="student_enrollment"
                        >

                        <div class="admin-student-enrollment-search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Search student..."
                                autocomplete="off"
                            >

                        </div>

                    </form>

                </div>

                <div class="admin-student-enrollment-table-info">

                    <?php if ($totalFilteredStudents > 0): ?>

                        <?php
                        $displayStart = $studentOffset + 1;
                        $displayEnd = min(
                            $studentOffset + $studentsPerPage,
                            $totalFilteredStudents
                        );
                        ?>

                        <span>
                            Showing <?= $displayStart ?>–<?= $displayEnd ?>
                            of <?= $totalFilteredStudents ?> students
                        </span>

                    <?php else: ?>

                        <span>
                            No students found
                        </span>

                    <?php endif; ?>

                </div>


                <div class="admin-student-enrollment-table-wrapper">

                    <table class="admin-student-enrollment-table">

                        <thead>

                            <tr>

                                <th>Student Number</th>

                                <th>Student Name</th>

                                <th>Program</th>

                                <th>Year</th>

                                <th>Section</th>

                                <th>Current Phase</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (empty($students)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="admin-student-enrollment-empty">

                                    No students found.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($students as $student): ?>

                                <?php

                                $fullName = trim(
                                    $student['first_name']
                                    . ' '
                                    . ($student['middle_name'] ?? '')
                                    . ' '
                                    . $student['last_name']
                                );

                                $phase = $student['enrollment_phase'];

                                ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $student['student_number'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $fullName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $student['program'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int) $student['year_level'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $student['section'] ?? '—',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="admin-enrollment-phase <?= enrollment_phase_class($phase) ?>">

                                            <?= enrollment_phase_label($phase) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if ($totalStudentPages > 1): ?>

                    <div class="admin-student-enrollment-pagination">

                        <a
                            href="<?= e(
                                BASE_URL
                                . '?page=student_enrollment'
                                . '&search=' . urlencode($search)
                                . '&student_page=' . ($currentStudentPage - 1)
                            ) ?>"
                            class="<?= $currentStudentPage <= 1 ? 'disabled' : '' ?>"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                            Previous
                        </a>


                        <div class="admin-student-enrollment-page-numbers">

                            <?php for ($pageNumber = 1; $pageNumber <= $totalStudentPages; $pageNumber++): ?>

                                <a
                                    href="<?= e(
                                        BASE_URL
                                        . '?page=student_enrollment'
                                        . '&search=' . urlencode($search)
                                        . '&student_page=' . $pageNumber
                                    ) ?>"
                                    class="<?= $pageNumber === $currentStudentPage ? 'active' : '' ?>"
                                >
                                    <?= $pageNumber ?>
                                </a>

                            <?php endfor; ?>

                        </div>


                        <a
                            href="<?= e(
                                BASE_URL
                                . '?page=student_enrollment'
                                . '&search=' . urlencode($search)
                                . '&student_page=' . ($currentStudentPage + 1)
                            ) ?>"
                            class="<?= $currentStudentPage >= $totalStudentPages ? 'disabled' : '' ?>"
                        >
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>

</html>