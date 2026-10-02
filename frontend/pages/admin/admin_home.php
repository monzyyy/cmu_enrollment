<?php

require_role('ADMIN');

$currentPage = 'admin_home';


$stmt = $conn->prepare(
    'SELECT enrollment_status
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);

$stmt->execute();

$result = $stmt->get_result();

$settings = $result->fetch_assoc();

$stmt->close();


$enrollmentStatus = $settings['enrollment_status'] ?? 'CLOSED';

$isEnrollmentOpen = $enrollmentStatus === 'OPEN';

$statusClass = $isEnrollmentOpen
    ? 'admin-status-open'
    : 'admin-status-closed';

$statusMessage = $isEnrollmentOpen
    ? 'Enrollment is currently open'
    : 'Enrollment is currently closed';


$totalStudents = 0;
$notStartedStudents = 0;
$corStudents = 0;
$enrolledStudents = 0;


$result = $conn->query(
    "SELECT
        COUNT(*) AS total_students,
        SUM(enrollment_phase = 'NOT_STARTED') AS not_started_students,
        SUM(enrollment_phase = 'COR') AS cor_students,
        SUM(enrollment_phase = 'ENROLLED') AS enrolled_students
     FROM students"
);

$studentCounts = $result->fetch_assoc();


$totalStudents = (int) $studentCounts['total_students'];
$notStartedStudents = (int) $studentCounts['not_started_students'];
$corStudents = (int) $studentCounts['cor_students'];
$enrolledStudents = (int) $studentCounts['enrolled_students'];

$students = [];

$result = $conn->query(
    "SELECT
        student_number,
        first_name,
        middle_name,
        last_name,
        enrollment_phase
     FROM students
     ORDER BY student_id ASC"
);

while ($student = $result->fetch_assoc()) {
    $students[] = $student;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>


<body>

<div class="layout">


    <!-- LOGO -->

    <div class="imglogo">

        <!--
        <img src="<?= BASE_URL ?>frontend/assets/images/cmu-logo.png"
             alt="City of Malabon University">
        -->

        <span>CMU</span>

    </div>


    <!-- ADMIN SIDEBAR -->

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>


    <!-- MOBILE OVERLAY -->

    <div class="overlay" id="overlay"></div>


    <!-- MAIN -->

    <div class="main">


        <!-- TOPBAR -->

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>


        <!-- CONTENT -->

        <div class="content">

            <!-- =========================
                ENROLLMENT STATUS
            ========================== -->

            <section class="admin-enrollment-card">

                <div class="admin-enrollment-left">

                    <div class="admin-status-row">

                        <span class="admin-section-label">
                            Enrollment Status
                        </span>

                        <span class="admin-status-badge <?= $statusClass ?>">
                            <span class="admin-status-dot"></span>
                            <?= htmlspecialchars($enrollmentStatus, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </div>

                    <h1>
                        <?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?>
                    </h1>

                    <p>
                        Students can now enroll to City of Malabon University
                    </p>

                    <a href="<?= BASE_URL ?>?page=enrollment_period"
                    class="admin-period-button">

                        <i class="fa-regular fa-calendar"></i>

                        Enrollment Period

                    </a>

                </div>


                <div class="admin-enrollment-info">

                    <div class="admin-info-row">

                        <span>SEMESTER:</span>

                        <strong>1st Semester</strong>

                    </div>

                    <div class="admin-info-row">

                        <span>SCHOOL YEAR:</span>

                        <strong>2026 - 2027</strong>

                    </div>

                    <div class="admin-info-row">

                        <span>START DATE:</span>

                        <strong>June 1, 2026</strong>

                    </div>

                    <div class="admin-info-row">

                        <span>END DATE:</span>

                        <strong>June 15, 2026</strong>

                    </div>

                </div>

            </section>


            <!-- =========================
                SUMMARY CARDS
            ========================== -->

            <section class="admin-stat-grid">


                <!-- Students -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div class="admin-stat-content">

                        <h3>Students</h3>

                        <strong><?= $totalStudents ?></strong>

                        <p>
                            Overall Students of CMU
                        </p>

                    </div>

                </div>


                <!-- Pending -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-regular fa-hourglass-half"></i>
                    </div>

                    <div class="admin-stat-content">

                        <h3>Not Started</h3>

                        <strong><?= $notStartedStudents ?></strong>

                        <p class="admin-stat-green">
                            <?= $notStartedStudents ?> students haven't started
                        </p>

                    </div>

                </div>


                <!-- COR -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>

                    <div class="admin-stat-content">

                        <h3>COR</h3>

                        <strong><?= $corStudents ?></strong>

                        <p>
                            students are awaiting enrollment confirmation
                        </p>

                    </div>

                </div>


                <!-- Enrolled -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>

                    <div class="admin-stat-content">

                        <h3>Enrolled</h3>

                        <strong><?= $enrolledStudents ?></strong>

                        <p class="admin-enrolled-count">
                            <i class="fa-regular fa-clock"></i>
                            <?= $enrolledStudents ?> students have completed enrollment
                        </p>

                    </div>

                </div>


            </section>


            <!-- =========================
                STUDENT ENROLLMENT TABLE
            ========================== -->

            <section class="admin-student-table">

                <div class="admin-table-header">

                    <span>#</span>
                    <span>STUDENT NO.</span>
                    <span>STUDENT NAME</span>
                    <span>PROGRESS</span>
                    <span>STATUS</span>

                </div>


                <?php foreach ($students as $index => $student): ?>

                    <?php

                    $studentName = trim(
                        $student['first_name'] . ' ' .
                        ($student['middle_name']
                            ? $student['middle_name'] . ' '
                            : '') .
                        $student['last_name']
                    );

                    $progress = ucwords(
                        strtolower(
                            str_replace('_', ' ', $student['enrollment_phase'])
                        )
                    );

                    ?>

                    <div class="admin-table-row">

                        <span>
                            <?= $index + 1 ?>
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $student['student_number'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $studentName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $progress,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <span>
                            <span class="admin-table-status admin-status-neutral">
                                —
                            </span>
                        </span>

                    </div>

                <?php endforeach; ?>

            </section>

        </div>


    </div>

</div>


<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>

</html>