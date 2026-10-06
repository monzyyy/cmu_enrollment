<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

require_role('ADMIN');

$currentPage = 'course_management';

$courses = [];

$sql = '
    SELECT
        course_id,
        course_code,
        course_name,
        units,
        is_active
    FROM courses
    ORDER BY
        is_active DESC,
        course_code ASC
';

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

}

$totalCourses = count($courses);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Course Management</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

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

    <!-- LOGO -->

    <div class="imglogo">
        <span>CMU</span>
    </div>


    <!-- SIDEBAR -->

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>


    <!-- MOBILE OVERLAY -->

    <div class="overlay" id="overlay"></div>


    <!-- MAIN -->

    <div class="main">


        <!-- TOPBAR -->

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>


        <!-- PAGE CONTENT -->

        <div class="content">


            <!-- PAGE HEADER -->

            <section class="admin-student-enrollment-header">

                <div>

                    <h1>Course Management</h1>

                    <p>
                        Manage the reusable courses available for course offerings.
                    </p>

                </div>

                <button
                    type="button"
                    class="course-management-add-button"
                    id="addCourseButton"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Course
                </button>

            </section>


            <!-- COURSE CARD -->

            <section class="course-management-card">


                <!-- CARD HEADER -->

                <div class="admin-student-enrollment-card-header">

                    <div>

                        <h2>Course Catalog</h2>

                        <p>
                            <?= e($totalCourses) ?> courses in the catalog
                        </p>

                    </div>


                    <div class="course-management-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="courseSearch"
                            placeholder="Search course..."
                        >

                    </div>

                </div>


                <!-- TABLE -->

                <div class="course-management-table-wrap">

                    <table class="course-management-table">

                        <thead>

                            <tr>

                                <th>Course Code</th>

                                <th>Course Name</th>

                                <th>Units</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody id="courseTableBody">

                            <?php if (empty($courses)): ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        class="course-management-empty"
                                    >
                                        No courses found.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($courses as $course): ?>

                                    <tr>

                                        <td>
                                            <?= e($course['course_code']) ?>
                                        </td>

                                        <td>
                                            <?= e($course['course_name']) ?>
                                        </td>

                                        <td>
                                            <?= e($course['units']) ?>
                                        </td>

                                        <td>

                                            <?php if ((int) $course['is_active'] === 1): ?>

                                                <span class="course-status active">
                                                    Active
                                                </span>

                                            <?php else: ?>

                                                <span class="course-status inactive">
                                                    Inactive
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <div class="course-management-actions">

                                                <button
                                                    type="button"
                                                    class="course-management-edit-button"
                                                    data-course-id="<?= e($course['course_id']) ?>"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                    Edit
                                                </button>


                                                <?php if ((int) $course['is_active'] === 1): ?>

                                                    <button
                                                        type="button"
                                                        class="course-management-status-button is-deactivate"
                                                        data-course-id="<?= e($course['course_id']) ?>"
                                                        data-action="deactivate"
                                                    >
                                                        <i class="fa-solid fa-ban"></i>
                                                        Deactivate
                                                    </button>

                                                <?php else: ?>

                                                    <button
                                                        type="button"
                                                        class="course-management-status-button is-activate"
                                                        data-course-id="<?= e($course['course_id']) ?>"
                                                        data-action="activate"
                                                    >
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                        Reactivate
                                                    </button>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


        </div>

    </div>

</div>
<!-- ADD COURSE MODAL -->

<div class="course-management-modal" id="addCourseModal">

    <div class="course-management-modal-content">

        <div class="course-management-modal-header">

            <div>
                <h2>Add Course</h2>
                <p>Add a new course to the course catalog.</p>
            </div>

            <button
                type="button"
                class="course-management-modal-close"
                id="closeAddCourseModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form id="addCourseForm">

            <div class="course-management-form-group">

                <label for="courseCode">
                    Course Code
                </label>

                <input
                    type="text"
                    id="courseCode"
                    placeholder="e.g. IT 307"
                    maxlength="50"
                    required
                >

            </div>


            <div class="course-management-form-group">

                <label for="courseName">
                    Course Name
                </label>

                <input
                    type="text"
                    id="courseName"
                    placeholder="e.g. Information Assurance"
                    maxlength="150"
                    required
                >

            </div>


            <div class="course-management-form-group">

                <label for="courseUnits">
                    Units
                </label>

                <input
                    type="number"
                    id="courseUnits"
                    min="1"
                    max="10"
                    value="3"
                    required
                >

            </div>


            <div
                class="course-management-form-message"
                id="addCourseMessage"
            ></div>


            <div class="course-management-modal-actions">

                <button
                    type="button"
                    class="course-management-cancel-button"
                    id="cancelAddCourse"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="course-management-save-button"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Course
                </button>

            </div>

        </form>

    </div>

</div>

<!-- EDIT COURSE MODAL -->

<div class="course-management-modal" id="editCourseModal">

    <div class="course-management-modal-content">

        <!-- HEADER -->
        <div class="course-management-modal-header">

            <div>
                <h2>Edit Course</h2>
                <p>Update the course information below.</p>
            </div>

            <button
                type="button"
                class="course-management-modal-close"
                id="closeEditCourseModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <!-- FORM -->
        <form id="editCourseForm">

            <input
                type="hidden"
                id="editCourseId"
            >


            <!-- COURSE CODE -->
            <div class="course-management-form-group">

                <label for="editCourseCode">
                    Course Code
                </label>

                <input
                    type="text"
                    id="editCourseCode"
                    maxlength="50"
                    required
                >

            </div>


            <!-- COURSE NAME -->
            <div class="course-management-form-group">

                <label for="editCourseName">
                    Course Name
                </label>

                <input
                    type="text"
                    id="editCourseName"
                    maxlength="150"
                    required
                >

            </div>


            <!-- UNITS -->
            <div class="course-management-form-group">

                <label for="editCourseUnits">
                    Units
                </label>

                <input
                    type="number"
                    id="editCourseUnits"
                    min="1"
                    max="10"
                    required
                >

            </div>


            <!-- MESSAGE -->
            <div
                class="course-management-form-message"
                id="editCourseMessage"
            ></div>


            <!-- ACTIONS -->
            <div class="course-management-modal-actions">

                <button
                    type="button"
                    class="course-management-cancel-button"
                    id="cancelEditCourse"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="course-management-save-button"
                >
                    <i class="fa-solid fa-check"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>


<script>

const courseSearch = document.getElementById('courseSearch');

if (courseSearch) {

    courseSearch.addEventListener('input', function () {

        const searchValue = this.value.toLowerCase().trim();

        const rows = document.querySelectorAll(
            '#courseTableBody tr'
        );

        rows.forEach(function (row) {

            const text = row.textContent.toLowerCase();

            row.style.display =
                text.includes(searchValue)
                    ? ''
                    : 'none';

        });

    });

}

const addCourseButton = document.getElementById('addCourseButton');
const addCourseModal = document.getElementById('addCourseModal');
const closeAddCourseModal = document.getElementById('closeAddCourseModal');
const cancelAddCourse = document.getElementById('cancelAddCourse');
const addCourseForm = document.getElementById('addCourseForm');
const addCourseMessage = document.getElementById('addCourseMessage');


function openAddCourseModal() {

    addCourseModal.classList.add('show');

}


function closeCourseModal() {

    addCourseModal.classList.remove('show');

    addCourseForm.reset();

    addCourseMessage.textContent = '';
    addCourseMessage.classList.remove('show');

}


addCourseButton.addEventListener('click', function () {

    openAddCourseModal();

});


closeAddCourseModal.addEventListener('click', function () {

    closeCourseModal();

});


cancelAddCourse.addEventListener('click', function () {

    closeCourseModal();

});


addCourseModal.addEventListener('click', function (event) {

    if (event.target === addCourseModal) {

        closeCourseModal();

    }

});


addCourseForm.addEventListener('submit', async function (event) {

    event.preventDefault();


    const courseCode = document
        .getElementById('courseCode')
        .value
        .trim()
        .toUpperCase();

    const courseName = document
        .getElementById('courseName')
        .value
        .trim();

    const units = parseInt(
        document.getElementById('courseUnits').value,
        10
    );


    if (!courseCode || !courseName || !units || units <= 0) {

        addCourseMessage.textContent =
            'Please complete all course information.';

        addCourseMessage.classList.add('show');

        return;
    }


    try {

        const response = await fetch(
            'backend/api/add_course.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({
                    course_code: courseCode,
                    course_name: courseName,
                    units: units
                })
            }
        );


        const data = await response.json();


        if (!data.success) {

            addCourseMessage.textContent =
                data.message || 'Failed to add the course.';

            addCourseMessage.classList.add('show');

            return;
        }


        alert(data.message);

        window.location.reload();


    } catch (error) {

        addCourseMessage.textContent =
            'Something went wrong. Please try again.';

        addCourseMessage.classList.add('show');

    }

});
const editCourseModal = document.getElementById('editCourseModal');
const closeEditCourseModal = document.getElementById('closeEditCourseModal');
const cancelEditCourse = document.getElementById('cancelEditCourse');
const editCourseForm = document.getElementById('editCourseForm');
const editCourseMessage = document.getElementById('editCourseMessage');


function openEditCourseModal(courseId, courseCode, courseName, units) {

    document.getElementById('editCourseId').value = courseId;
    document.getElementById('editCourseCode').value = courseCode;
    document.getElementById('editCourseName').value = courseName;
    document.getElementById('editCourseUnits').value = units;

    editCourseMessage.textContent = '';
    editCourseMessage.classList.remove('show');

    editCourseModal.classList.add('show');
}


function closeEditCourseModalFunction() {

    editCourseModal.classList.remove('show');

    editCourseForm.reset();

    editCourseMessage.textContent = '';
    editCourseMessage.classList.remove('show');
}


/* Edit buttons */

document
    .querySelectorAll('.course-management-edit-button')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const row = this.closest('tr');

            const courseId = this.dataset.courseId;

            const cells = row.querySelectorAll('td');

            const courseCode = cells[0].textContent.trim();
            const courseName = cells[1].textContent.trim();
            const units = cells[2].textContent.trim();

            openEditCourseModal(
                courseId,
                courseCode,
                courseName,
                units
            );

        });

    });


closeEditCourseModal.addEventListener(
    'click',
    function () {

        closeEditCourseModalFunction();

    }
);


cancelEditCourse.addEventListener(
    'click',
    function () {

        closeEditCourseModalFunction();

    }
);


editCourseModal.addEventListener(
    'click',
    function (event) {

        if (event.target === editCourseModal) {

            closeEditCourseModalFunction();

        }

    }
);


/* Save changes */

editCourseForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();


        const courseId = parseInt(
            document.getElementById('editCourseId').value,
            10
        );

        const courseCode = document
            .getElementById('editCourseCode')
            .value
            .trim()
            .toUpperCase();

        const courseName = document
            .getElementById('editCourseName')
            .value
            .trim();

        const units = parseInt(
            document.getElementById('editCourseUnits').value,
            10
        );


        if (
            !courseId ||
            !courseCode ||
            !courseName ||
            !units ||
            units <= 0
        ) {

            editCourseMessage.textContent =
                'Please complete all course information.';

            editCourseMessage.classList.add('show');

            return;
        }


        try {

            const response = await fetch(
                'backend/api/update_course.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        course_id: courseId,
                        course_code: courseCode,
                        course_name: courseName,
                        units: units
                    })
                }
            );


            const data = await response.json();


            if (!data.success) {

                editCourseMessage.textContent =
                    data.message || 'Failed to update the course.';

                editCourseMessage.classList.add('show');

                return;
            }


            alert(data.message);

            window.location.reload();


        } catch (error) {

            editCourseMessage.textContent =
                'Something went wrong. Please try again.';

            editCourseMessage.classList.add('show');

        }

    }
);

document
    .querySelectorAll('.course-management-status-button')
    .forEach(function (button) {

        button.addEventListener('click', async function () {

            const courseId = parseInt(
                this.dataset.courseId,
                10
            );

            const action = this.dataset.action;

            const actionText =
                action === 'deactivate'
                    ? 'deactivate'
                    : 'reactivate';


            const confirmed = confirm(
                `Are you sure you want to ${actionText} this course?`
            );


            if (!confirmed) {
                return;
            }


            try {

                const response = await fetch(
                    'backend/api/toggle_course_status.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json'
                        },

                        body: JSON.stringify({
                            course_id: courseId,
                            action: action
                        })
                    }
                );


                const data = await response.json();


                if (!data.success) {

                    alert(
                        data.message ||
                        'Failed to update the course status.'
                    );

                    return;
                }


                alert(data.message);

                window.location.reload();


            } catch (error) {

                alert(
                    'Something went wrong. Please try again.'
                );

            }

        });

    });

</script>

</body>
</html>