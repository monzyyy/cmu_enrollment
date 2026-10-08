<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

require_role('ADMIN');

$currentPage = 'course_offerings';


/*
|--------------------------------------------------------------------------
| Current Filters
|--------------------------------------------------------------------------
*/

$semester = $_GET['semester'] ?? '1st Semester';
$schoolYear = $_GET['school_year'] ?? '2026 - 2027';
$program = $_GET['program'] ?? '';
$yearLevel = $_GET['year_level'] ?? '';
$section = $_GET['section'] ?? '';


/*
|--------------------------------------------------------------------------
| Program Options
|--------------------------------------------------------------------------
*/

$programs = [];

$stmt = $conn->prepare(
    'SELECT DISTINCT program
     FROM students
     WHERE program IS NOT NULL
       AND program <> ""
     ORDER BY program ASC'
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $programs[] = $row['program'];
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Section Options
|--------------------------------------------------------------------------
*/

$sections = [];

if ($program !== '' && $yearLevel !== '') {

    $yearLevelValue = (int) $yearLevel;

    $stmt = $conn->prepare(
        'SELECT DISTINCT section
         FROM students
         WHERE program = ?
           AND year_level = ?
           AND section IS NOT NULL
           AND section <> ""
         ORDER BY section ASC'
    );

    $stmt->bind_param(
        'si',
        $program,
        $yearLevelValue
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['section'];
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Course Offerings
|--------------------------------------------------------------------------
*/

$courseOfferings = [];

$sql = '
    SELECT
        co.offering_id,
        co.program,
        co.year_level,
        co.section,
        co.semester,
        co.school_year,
        co.instructor_name,
        c.course_code,
        c.course_name,
        c.units,
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

    WHERE co.semester = ?
      AND co.school_year = ?
';

$params = [
    $semester,
    $schoolYear
];

$types = 'ss';


if ($program !== '') {

    $sql .= ' AND co.program = ?';

    $params[] = $program;

    $types .= 's';
}


if ($yearLevel !== '') {

    $sql .= ' AND co.year_level = ?';

    $params[] = (int) $yearLevel;

    $types .= 'i';
}


if ($section !== '') {

    $sql .= ' AND co.section = ?';

    $params[] = $section;

    $types .= 's';
}


$sql .= '
    GROUP BY
        co.offering_id,
        co.program,
        co.year_level,
        co.section,
        co.semester,
        co.school_year,
        co.instructor_name,
        c.course_code,
        c.course_name,
        c.units

    ORDER BY
        co.program ASC,
        co.year_level ASC,
        co.section ASC,
        c.course_code ASC
';


$stmt = $conn->prepare($sql);


$stmt->bind_param(
    $types,
    ...$params
);


$stmt->execute();


$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $row['schedules'] =
        $row['schedules'] !== null
            ? explode('||', $row['schedules'])
            : [];

    $courseOfferings[] = $row;

}


$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Course Offerings | CMU Enrollment
    </title>


    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/main.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/admin.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

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


            <!-- =====================================================
                 PAGE HEADER
            ====================================================== -->

            <div class="admin-student-enrollment-header">

                <div>

                    <h1>
                        Course Offerings
                    </h1>

                    <p>
                        Manage the courses assigned to each program,
                        year level, and section.
                    </p>

                </div>


                <button
                    type="button"
                    class="course-offering-header-button"
                    id="openAddCourseModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Course

                </button>

            </div>


            <!-- =====================================================
                 FILTER CARD
            ====================================================== -->

            <div class="admin-student-enrollment-card">


                <div class="admin-student-enrollment-card-header">

                    <div>

                        <h2>
                            Course Offering Filters
                        </h2>

                        <p>
                            Select the semester and section
                            you want to manage.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    action="<?= BASE_URL ?>"
                    class="course-offering-filters"
                >

                    <input
                        type="hidden"
                        name="page"
                        value="course_offerings"
                    >


                    <div class="course-offering-field">

                        <label for="semester">
                            Semester
                        </label>

                        <select
                            id="semester"
                            name="semester"
                        >

                            <option
                                value="1st Semester"
                                <?= $semester === '1st Semester'
                                    ? 'selected'
                                    : '' ?>
                            >
                                1st Semester
                            </option>

                            <option
                                value="2nd Semester"
                                <?= $semester === '2nd Semester'
                                    ? 'selected'
                                    : '' ?>
                            >
                                2nd Semester
                            </option>

                            <option
                                value="Summer"
                                <?= $semester === 'Summer'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Summer
                            </option>

                        </select>

                    </div>


                    <div class="course-offering-field">

                        <label for="school_year">
                            School Year
                        </label>

                        <input
                            type="text"
                            id="school_year"
                            name="school_year"
                            value="<?= e($schoolYear) ?>"
                            placeholder="2026 - 2027"
                        >

                    </div>


                    <div class="course-offering-field">

                        <label for="program">
                            Program
                        </label>

                        <select
                            id="program"
                            name="program"
                        >

                            <option value="">
                                All Programs
                            </option>

                            <?php foreach ($programs as $programOption): ?>

                                <option
                                    value="<?= e($programOption) ?>"
                                    <?= $program === $programOption
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($programOption) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="course-offering-field">

                        <label for="year_level">
                            Year Level
                        </label>

                        <select
                            id="year_level"
                            name="year_level"
                        >

                            <option value="">
                                All Year Levels
                            </option>

                            <option
                                value="1"
                                <?= $yearLevel === '1'
                                    ? 'selected'
                                    : '' ?>
                            >
                                1st Year
                            </option>

                            <option
                                value="2"
                                <?= $yearLevel === '2'
                                    ? 'selected'
                                    : '' ?>
                            >
                                2nd Year
                            </option>

                            <option
                                value="3"
                                <?= $yearLevel === '3'
                                    ? 'selected'
                                    : '' ?>
                            >
                                3rd Year
                            </option>

                            <option
                                value="4"
                                <?= $yearLevel === '4'
                                    ? 'selected'
                                    : '' ?>
                            >
                                4th Year
                            </option>

                        </select>

                    </div>


                    <div class="course-offering-field">

                        <label for="section">
                            Section
                        </label>

                        <select
                            id="section"
                            name="section"
                        >

                            <option value="">
                                All Sections
                            </option>

                            <?php foreach ($sections as $sectionOption): ?>

                                <option
                                    value="<?= e($sectionOption) ?>"
                                    <?= $section === $sectionOption
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($sectionOption) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="course-offering-filter-button">

                        <button
                            type="submit"
                        >

                            <i class="fa-solid fa-filter"></i>

                            Apply Filter

                        </button>

                    </div>

                </form>

            </div>


            <!-- =====================================================
                 COURSE OFFERINGS TABLE
            ====================================================== -->

            <div class="admin-student-enrollment-card">


                <div class="admin-student-enrollment-card-header">

                    <div>

                        <h2>
                            Assigned Courses
                        </h2>

                        <p>

                            <?php if (
                                $program !== '' &&
                                $yearLevel !== '' &&
                                $section !== ''
                            ): ?>

                                <?= e($program) ?>
                                ·
                                <?= e($yearLevel) ?>th Year
                                ·
                                <?= e($section) ?>

                            <?php else: ?>

                                <?= e($semester) ?>
                                ·
                                <?= e($schoolYear) ?>

                            <?php endif; ?>

                        </p>

                    </div>


                    <span class="course-offering-count">

                        <?= count($courseOfferings) ?>

                        <?= count($courseOfferings) === 1
                            ? 'Course'
                            : 'Courses' ?>

                    </span>

                </div>


                <?php if (empty($courseOfferings)): ?>


                    <div class="course-offering-empty">

                        <div class="course-offering-empty-icon">

                            <i class="fa-solid fa-book-open"></i>

                        </div>


                        <h3>
                            No Course Offerings Found
                        </h3>


                        <p>
                            No courses have been assigned to
                            the selected filters yet.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="admin-student-enrollment-table-wrapper">

                        <table class="admin-student-enrollment-table">

                            <thead>

                                <tr>

                                    <th>
                                        Course ID
                                    </th>

                                    <th>
                                        Course Name
                                    </th>

                                    <th>
                                        Units
                                    </th>

                                    <th>
                                        Instructor
                                    </th>

                                    <th>
                                        Schedule
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach (
                                $courseOfferings
                                as $offering
                            ): ?>

                                <tr>


                                    <td>

                                        <strong>
                                            <?= e(
                                                $offering['course_code']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $offering['course_name']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $offering['units']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $offering['instructor_name']
                                            )
                                        ): ?>

                                            <?= e(
                                                $offering[
                                                    'instructor_name'
                                                ]
                                            ) ?>

                                        <?php else: ?>

                                            <span class="course-offering-muted">
                                                Not assigned
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($offering['schedules'])): ?>

                                            <div class="course-offering-schedule-list">

                                                <?php foreach ($offering['schedules'] as $schedule): ?>

                                                    <div class="course-offering-schedule-item">

                                                        <i class="fa-regular fa-clock"></i>

                                                        <?= e($schedule) ?>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="course-offering-muted">
                                                Not finalized
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="course-offering-edit-button"
                                            data-offering-id="<?= e($offering['offering_id']) ?>"
                                        >

                                            <i class="fa-solid fa-pen"></i>

                                            Edit

                                        </button>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </div>

</div>

<!-- =====================================================
     ADD COURSE OFFERING MODAL
====================================================== -->

<div
    class="course-offering-modal"
    id="addCourseModal"
    aria-hidden="true"
>

    <div class="course-offering-modal-backdrop"></div>


    <div
        class="course-offering-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addCourseModalTitle"
    >

        <div class="course-offering-modal-header">

            <div>

                <h2 id="addCourseModalTitle">
                    Add Course Offering
                </h2>

                <p>
                    Assign a course to a specific section.
                </p>

            </div>


            <button
                type="button"
                class="course-offering-modal-close"
                id="closeAddCourseModal"
                aria-label="Close"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form id="addCourseOfferingForm">


            <!-- COURSE -->

            <div class="course-offering-modal-section">

                <div class="course-offering-modal-section-title">

                    <i class="fa-solid fa-book"></i>

                    Course Information

                </div>


                <div class="course-offering-modal-grid">


                    <div class="course-offering-modal-field full">

                        <label for="modal_course_id">
                            Course
                        </label>

                        <select
                            id="modal_course_id"
                            name="course_id"
                            required
                        >

                            <option value="">
                                Select a course
                            </option>

                            <?php

                            $activeCourses = [];

                            $stmt = $conn->prepare(
                                'SELECT
                                    course_id,
                                    course_code,
                                    course_name,
                                    units
                                 FROM courses
                                 WHERE is_active = 1
                                 ORDER BY course_code ASC'
                            );

                            $stmt->execute();

                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                $activeCourses[] = $row;
                            }

                            $stmt->close();

                            ?>


                            <?php foreach (
                                $activeCourses
                                as $course
                            ): ?>

                                <option
                                    value="<?= e(
                                        $course['course_id']
                                    ) ?>"
                                >

                                    <?= e(
                                        $course['course_code']
                                    ) ?>

                                    —
                                    <?= e(
                                        $course['course_name']
                                    ) ?>

                                    (<?= e(
                                        $course['units']
                                    ) ?> units)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                </div>

            </div>


            <!-- SECTION ASSIGNMENT -->

            <div class="course-offering-modal-section">

                <div class="course-offering-modal-section-title">

                    <i class="fa-solid fa-users"></i>

                    Section Assignment

                </div>


                <div class="course-offering-modal-grid">


                    <div class="course-offering-modal-field">

                        <label for="modal_program">
                            Program
                        </label>

                        <select
                            id="modal_program"
                            name="program"
                            required
                        >

                            <option value="">
                                Select program
                            </option>

                            <?php foreach (
                                $programs
                                as $programOption
                            ): ?>

                                <option
                                    value="<?= e(
                                        $programOption
                                    ) ?>"
                                >
                                    <?= e(
                                        $programOption
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="course-offering-modal-field">

                        <label for="modal_year_level">
                            Year Level
                        </label>

                        <select
                            id="modal_year_level"
                            name="year_level"
                            required
                        >

                            <option value="">
                                Select year level
                            </option>

                            <option value="1">
                                1st Year
                            </option>

                            <option value="2">
                                2nd Year
                            </option>

                            <option value="3">
                                3rd Year
                            </option>

                            <option value="4">
                                4th Year
                            </option>

                        </select>

                    </div>


                    <div class="course-offering-modal-field">

                        <label for="modal_section">
                            Section
                        </label>

                        <select
                            id="modal_section"
                            name="section"
                            required
                        >

                            <option value="">
                                Select section
                            </option>

                            <?php

                            $allSections = [];

                            $stmt = $conn->prepare(
                                'SELECT DISTINCT
                                    program,
                                    year_level,
                                    section
                                 FROM students
                                 WHERE section IS NOT NULL
                                   AND section <> ""
                                 ORDER BY
                                    program ASC,
                                    year_level ASC,
                                    section ASC'
                            );

                            $stmt->execute();

                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                $allSections[] = $row;
                            }

                            $stmt->close();

                            ?>


                            <?php foreach (
                                $allSections
                                as $sectionOption
                            ): ?>

                                <option
                                    value="<?= e(
                                        $sectionOption['section']
                                    ) ?>"
                                    data-program="<?= e(
                                        $sectionOption['program']
                                    ) ?>"
                                    data-year="<?= e(
                                        $sectionOption['year_level']
                                    ) ?>"
                                >

                                    <?= e(
                                        $sectionOption['section']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="course-offering-modal-field">

                        <label for="modal_instructor">
                            Instructor
                            <span>(Optional)</span>
                        </label>

                        <input
                            type="text"
                            id="modal_instructor"
                            name="instructor_name"
                            placeholder="e.g. Prof. Juan Dela Cruz"
                        >

                    </div>


                </div>

            </div>


            <!-- TERM -->

            <div class="course-offering-modal-section">

                <div class="course-offering-modal-section-title">

                    <i class="fa-solid fa-calendar"></i>

                    Academic Term

                </div>


                <div class="course-offering-modal-grid">


                    <div class="course-offering-modal-field">

                        <label for="modal_semester">
                            Semester
                        </label>

                        <select
                            id="modal_semester"
                            name="semester"
                            required
                        >

                            <option value="1st Semester">
                                1st Semester
                            </option>

                            <option value="2nd Semester">
                                2nd Semester
                            </option>

                            <option value="Summer">
                                Summer
                            </option>

                        </select>

                    </div>


                    <div class="course-offering-modal-field">

                        <label for="modal_school_year">
                            School Year
                        </label>

                        <input
                            type="text"
                            id="modal_school_year"
                            name="school_year"
                            value="<?= e($schoolYear) ?>"
                            required
                        >

                    </div>


                </div>

            </div>


            <!-- SCHEDULE -->

            <div class="course-offering-modal-section">

                <div class="course-offering-schedule-heading">

                    <div>

                        <div class="course-offering-modal-section-title">

                            <i class="fa-solid fa-clock"></i>

                            Schedule

                            <span>(Optional)</span>

                        </div>

                        <p>
                            Add one or more schedules if already finalized.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="course-offering-add-schedule"
                        id="addScheduleRow"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Schedule

                    </button>

                </div>


                <div id="scheduleRows"></div>

            </div>


            <!-- FOOTER -->

            <div class="course-offering-modal-footer">

                <button
                    type="button"
                    class="course-offering-cancel"
                    id="cancelAddCourseModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="course-offering-save"
                    id="saveCourseOffering"
                >

                    <i class="fa-solid fa-check"></i>

                    Save Course Offering

                </button>

            </div>


        </form>

    </div>
    

</div>

<!-- =====================================================
     EDIT COURSE OFFERING MODAL
====================================================== -->

<div
    class="course-offering-modal"
    id="editCourseModal"
    aria-hidden="true"
>

    <div class="course-offering-modal-backdrop"></div>


    <div
        class="course-offering-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editCourseModalTitle"
    >

        <div class="course-offering-modal-header">

            <div>

                <h2 id="editCourseModalTitle">
                    Edit Course Offering
                </h2>

                <p>
                    Update the instructor and schedule.
                </p>

            </div>


            <button
                type="button"
                class="course-offering-modal-close"
                id="closeEditCourseModal"
                aria-label="Close"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form id="editCourseOfferingForm">

            <!-- COURSE INFORMATION -->

            <div class="course-offering-modal-section">

                <div class="course-offering-modal-section-title">

                    <i class="fa-solid fa-book"></i>

                    Course Information

                </div>


                <div
                    class="course-offering-edit-summary"
                    id="editCourseSummary"
                >

                    Loading course information...

                </div>

            </div>


            <!-- INSTRUCTOR -->

            <div class="course-offering-modal-section">

                <div class="course-offering-modal-section-title">

                    <i class="fa-solid fa-user"></i>

                    Instructor

                </div>


                <div class="course-offering-modal-field">

                    <label for="edit_instructor">
                        Instructor
                        <span>(Optional)</span>
                    </label>

                    <input
                        type="text"
                        id="edit_instructor"
                        placeholder="e.g. Prof. Juan Dela Cruz"
                    >

                </div>

            </div>


            <!-- SCHEDULE -->

            <div class="course-offering-modal-section">

                <div class="course-offering-schedule-heading">

                    <div>

                        <div class="course-offering-modal-section-title">

                            <i class="fa-solid fa-clock"></i>

                            Schedule

                        </div>

                        <p>
                            Add one or more schedules.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="course-offering-add-schedule"
                        id="editAddScheduleRow"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Schedule

                    </button>

                </div>


                <div id="editScheduleRows"></div>

            </div>


            <!-- FOOTER -->

            <div class="course-offering-modal-footer">

                <button
                    type="button"
                    class="course-offering-cancel"
                    id="cancelEditCourseModal"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="course-offering-save"
                    id="updateCourseOffering"
                >

                    <i class="fa-solid fa-check"></i>

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {


    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    const sidebarToggle =
        document.querySelector('.sidebar-toggle');

    const sidebar =
        document.querySelector('.sidebar');

    const overlay =
        document.getElementById('overlay');


    if (sidebarToggle && sidebar && overlay) {

        sidebarToggle.addEventListener(
            'click',
            function () {

                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');

            }
        );


        overlay.addEventListener(
            'click',
            function () {

                sidebar.classList.remove('active');
                overlay.classList.remove('active');

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Add Course Modal
    |--------------------------------------------------------------------------
    */

    const modal =
        document.getElementById('addCourseModal');

    const openButton =
        document.getElementById('openAddCourseModal');

    const closeButton =
        document.getElementById('closeAddCourseModal');

    const cancelButton =
        document.getElementById('cancelAddCourseModal');

    const backdrop =
        modal.querySelector(
            '.course-offering-modal-backdrop'
        );


    function openModal() {

        modal.classList.add('active');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

    }


    function closeModal() {

        modal.classList.remove('active');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

    }


    openButton.addEventListener(
        'click',
        openModal
    );


    closeButton.addEventListener(
        'click',
        closeModal
    );


    cancelButton.addEventListener(
        'click',
        closeModal
    );


    backdrop.addEventListener(
        'click',
        closeModal
    );


    /*
    |--------------------------------------------------------------------------
    | Section Filtering
    |--------------------------------------------------------------------------
    */

    const programSelect =
        document.getElementById('modal_program');

    const yearSelect =
        document.getElementById('modal_year_level');

    const sectionSelect =
        document.getElementById('modal_section');


    function filterSections() {

        const selectedProgram =
            programSelect.value;

        const selectedYear =
            yearSelect.value;


        Array.from(
            sectionSelect.options
        ).forEach(function (option, index) {

            if (index === 0) {
                option.hidden = false;
                return;
            }


            const optionProgram =
                option.dataset.program;

            const optionYear =
                option.dataset.year;


            const matches =
                optionProgram === selectedProgram &&
                optionYear === selectedYear;


            option.hidden = !matches;

        });


        sectionSelect.value = '';

    }


    programSelect.addEventListener(
        'change',
        filterSections
    );


    yearSelect.addEventListener(
        'change',
        filterSections
    );


    /*
    |--------------------------------------------------------------------------
    | Schedule Rows
    |--------------------------------------------------------------------------
    */

    const scheduleRows =
        document.getElementById('scheduleRows');

    const addScheduleButton =
        document.getElementById('addScheduleRow');


    function createScheduleRow() {

        const row =
            document.createElement('div');

        row.className =
            'course-offering-schedule-row';


        row.innerHTML = `

            <div>

                <label>
                    Day
                </label>

                <select class="schedule-day">

                    <option value="">
                        Day
                    </option>

                    <option value="Monday">
                        Monday
                    </option>

                    <option value="Tuesday">
                        Tuesday
                    </option>

                    <option value="Wednesday">
                        Wednesday
                    </option>

                    <option value="Thursday">
                        Thursday
                    </option>

                    <option value="Friday">
                        Friday
                    </option>

                    <option value="Saturday">
                        Saturday
                    </option>

                    <option value="Sunday">
                        Sunday
                    </option>

                </select>

            </div>


            <div>

                <label>
                    Start Time
                </label>

                <input
                    type="time"
                    class="schedule-start"
                >

            </div>


            <div>

                <label>
                    End Time
                </label>

                <input
                    type="time"
                    class="schedule-end"
                >

            </div>


            <div>

                <label>
                    Room
                </label>

                <input
                    type="text"
                    class="schedule-room"
                    placeholder="Room 301"
                >

            </div>


            <button
                type="button"
                class="course-offering-remove-schedule"
                title="Remove schedule"
            >

                <i class="fa-solid fa-trash"></i>

            </button>

        `;


        row.querySelector(
            '.course-offering-remove-schedule'
        ).addEventListener(
            'click',
            function () {

                row.remove();

            }
        );


        scheduleRows.appendChild(row);

    }


    addScheduleButton.addEventListener(
        'click',
        createScheduleRow
    );


    /*
    |--------------------------------------------------------------------------
    | Save Course Offering
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            'addCourseOfferingForm'
        );


    form.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();


            const saveButton =
                document.getElementById(
                    'saveCourseOffering'
                );


            const schedules = [];


            document
                .querySelectorAll(
                    '.course-offering-schedule-row'
                )
                .forEach(function (row) {

                    schedules.push({

                        day:
                            row.querySelector(
                                '.schedule-day'
                            ).value,

                        start_time:
                            row.querySelector(
                                '.schedule-start'
                            ).value,

                        end_time:
                            row.querySelector(
                                '.schedule-end'
                            ).value,

                        room:
                            row.querySelector(
                                '.schedule-room'
                            ).value.trim()

                    });

                });


            const formData = {

                course_id:
                    document.getElementById(
                        'modal_course_id'
                    ).value,

                program:
                    programSelect.value,

                year_level:
                    yearSelect.value,

                section:
                    sectionSelect.value,

                semester:
                    document.getElementById(
                        'modal_semester'
                    ).value,

                school_year:
                    document.getElementById(
                        'modal_school_year'
                    ).value.trim(),

                instructor_name:
                    document.getElementById(
                        'modal_instructor'
                    ).value.trim(),

                schedules:
                    schedules

            };


            saveButton.disabled = true;

            saveButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Saving...
            `;


            try {

                const response =
                    await fetch(
                        '<?= BASE_URL ?>backend/api/add_course_offering.php',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type':
                                    'application/json'
                            },
                            body:
                                JSON.stringify(formData)
                        }
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    alert(result.message);

                    return;

                }


                alert(
                    'Course offering added successfully.'
                );


                window.location.href =
                    '<?= BASE_URL ?>?page=course_offerings'
                    + '&semester='
                    + encodeURIComponent(
                        formData.semester
                    )
                    + '&school_year='
                    + encodeURIComponent(
                        formData.school_year
                    )
                    + '&program='
                    + encodeURIComponent(
                        formData.program
                    )
                    + '&year_level='
                    + encodeURIComponent(
                        formData.year_level
                    )
                    + '&section='
                    + encodeURIComponent(
                        formData.section
                    );


            } catch (error) {

                alert(
                    'Something went wrong while saving the course offering.'
                );

            } finally {

                saveButton.disabled = false;

                saveButton.innerHTML = `
                    <i class="fa-solid fa-check"></i>
                    Save Course Offering
                `;

            }

        }
    );

/*
|--------------------------------------------------------------------------
| Edit Course Offering
|--------------------------------------------------------------------------
*/

const editModal =
    document.getElementById('editCourseModal');

const editCloseButton =
    document.getElementById('closeEditCourseModal');

const editCancelButton =
    document.getElementById('cancelEditCourseModal');

const editBackdrop =
    editModal
        ? editModal.querySelector(
            '.course-offering-modal-backdrop'
        )
        : null;

const editForm =
    document.getElementById(
        'editCourseOfferingForm'
    );

const editScheduleRows =
    document.getElementById(
        'editScheduleRows'
    );

const editAddScheduleButton =
    document.getElementById(
        'editAddScheduleRow'
    );

let currentEditingOfferingId = null;


function closeEditModal() {

    editModal.classList.remove('active');

    editModal.setAttribute(
        'aria-hidden',
        'true'
    );

    currentEditingOfferingId = null;

}


editCloseButton.addEventListener(
    'click',
    closeEditModal
);

editCancelButton.addEventListener(
    'click',
    closeEditModal
);

editBackdrop.addEventListener(
    'click',
    closeEditModal
);


/*
|--------------------------------------------------------------------------
| Create Edit Schedule Row
|--------------------------------------------------------------------------
*/

function createEditScheduleRow(schedule = {}) {

    const row =
        document.createElement('div');

    row.className =
        'course-offering-schedule-row';


    row.innerHTML = `

        <div>

            <label>
                Day
            </label>

            <select class="edit-schedule-day">

                <option value="">
                    Day
                </option>

                <option value="Monday">
                    Monday
                </option>

                <option value="Tuesday">
                    Tuesday
                </option>

                <option value="Wednesday">
                    Wednesday
                </option>

                <option value="Thursday">
                    Thursday
                </option>

                <option value="Friday">
                    Friday
                </option>

                <option value="Saturday">
                    Saturday
                </option>

                <option value="Sunday">
                    Sunday
                </option>

            </select>

        </div>


        <div>

            <label>
                Start Time
            </label>

            <input
                type="time"
                class="edit-schedule-start"
            >

        </div>


        <div>

            <label>
                End Time
            </label>

            <input
                type="time"
                class="edit-schedule-end"
            >

        </div>


        <div>

            <label>
                Room
            </label>

            <input
                type="text"
                class="edit-schedule-room"
                placeholder="Room 301"
            >

        </div>


        <button
            type="button"
            class="course-offering-remove-schedule"
            title="Remove schedule"
        >

            <i class="fa-solid fa-trash"></i>

        </button>

    `;


    row.querySelector(
        '.edit-schedule-day'
    ).value = schedule.day || '';


    row.querySelector(
        '.edit-schedule-start'
    ).value = schedule.start_time || '';


    row.querySelector(
        '.edit-schedule-end'
    ).value = schedule.end_time || '';


    row.querySelector(
        '.edit-schedule-room'
    ).value = schedule.room || '';


    row.querySelector(
        '.course-offering-remove-schedule'
    ).addEventListener(
        'click',
        function () {

            row.remove();

        }
    );


    editScheduleRows.appendChild(row);

}


editAddScheduleButton.addEventListener(
    'click',
    function () {

        createEditScheduleRow();

    }
);


/*
|--------------------------------------------------------------------------
| Open Edit Modal
|--------------------------------------------------------------------------
*/

document.querySelectorAll(
    '.course-offering-edit-button'
).forEach(function (button) {

    button.addEventListener(
        'click',
        async function () {

            const offeringId =
                button.dataset.offeringId;


            currentEditingOfferingId =
                offeringId;


            editModal.classList.add(
                'active'
            );

            editModal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.getElementById(
                'editCourseSummary'
            ).textContent =
                'Loading course information...';


            document.getElementById(
                'edit_instructor'
            ).value = '';


            editScheduleRows.innerHTML = '';


            try {

                const response =
                    await fetch(
                        '<?= BASE_URL ?>backend/api/get_course_offering.php'
                        + '?offering_id='
                        + encodeURIComponent(
                            offeringId
                        )
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    alert(result.message);

                    closeEditModal();

                    return;

                }


                const offering =
                    result.offering;


                document.getElementById(
                    'editCourseSummary'
                ).innerHTML = `

                    <strong>
                        ${offering.course_code}
                    </strong>
                    —
                    ${offering.course_name}

                    <br>

                    ${offering.program}
                    ·
                    ${offering.year_level}th Year
                    ·
                    ${offering.section}

                    <br>

                    ${offering.semester}
                    ·
                    ${offering.school_year}

                `;


                document.getElementById(
                    'edit_instructor'
                ).value =
                    offering.instructor_name || '';


                if (
                    result.schedules &&
                    result.schedules.length > 0
                ) {

                    result.schedules.forEach(
                        function (schedule) {

                            createEditScheduleRow(
                                schedule
                            );

                        }
                    );

                }

            } catch (error) {

                alert(
                    'Unable to load the course offering.'
                );

                closeEditModal();

            }

        }
    );

});


/*
|--------------------------------------------------------------------------
| Save Edit
|--------------------------------------------------------------------------
*/

editForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();


        const saveButton =
            document.getElementById(
                'updateCourseOffering'
            );


        const schedules = [];


        editScheduleRows
            .querySelectorAll(
                '.course-offering-schedule-row'
            )
            .forEach(function (row) {

                schedules.push({

                    day:
                        row.querySelector(
                            '.edit-schedule-day'
                        ).value,

                    start_time:
                        row.querySelector(
                            '.edit-schedule-start'
                        ).value,

                    end_time:
                        row.querySelector(
                            '.edit-schedule-end'
                        ).value,

                    room:
                        row.querySelector(
                            '.edit-schedule-room'
                        ).value.trim()

                });

            });


        const data = {

            offering_id:
                currentEditingOfferingId,

            instructor_name:
                document.getElementById(
                    'edit_instructor'
                ).value.trim(),

            schedules:
                schedules

        };


        saveButton.disabled = true;

        saveButton.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Saving...
        `;


        try {

            const response =
                await fetch(
                    '<?= BASE_URL ?>backend/api/update_course_offering.php',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json'
                        },
                        body:
                            JSON.stringify(data)
                    }
                );


            const result =
                await response.json();


            if (!result.success) {

                alert(result.message);

                return;

            }


            alert(
                'Course offering updated successfully.'
            );


            window.location.reload();


        } catch (error) {

            alert(
                'Something went wrong while updating the course offering.'
            );


        } finally {

            saveButton.disabled = false;

            saveButton.innerHTML = `
                <i class="fa-solid fa-check"></i>
                Save Changes
            `;

        }

    }
);

});


</script>

</body>

</html>