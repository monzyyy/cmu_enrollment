<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

require_role('ADMIN');

$currentPage = 'student_management';

$studentsPerPage = 5;

$currentStudentPage = max(
    1,
    (int) ($_GET['student_page'] ?? 1)
);

$search = trim($_GET['search'] ?? '');


/* TOTAL STUDENTS */

$result = $conn->query(
    'SELECT COUNT(*) AS total
     FROM students'
);

$totalStudents = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = (int) ($row['total'] ?? 0);
}


/* COUNT FILTERED STUDENTS */

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
            OR section LIKE ?
            OR email LIKE ?'
    );

    $searchValue = '%' . $search . '%';

    $countStmt->bind_param(
        'sssssss',
        $searchValue,
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

$countResult =
    $countStmt->get_result()->fetch_assoc();

$totalFilteredStudents =
    (int) ($countResult['total'] ?? 0);

$countStmt->close();


/* PAGINATION */

$totalStudentPages = max(
    1,
    (int) ceil(
        $totalFilteredStudents /
        $studentsPerPage
    )
);

$currentStudentPage = min(
    $currentStudentPage,
    $totalStudentPages
);

$studentOffset =
    ($currentStudentPage - 1) *
    $studentsPerPage;


/* GET STUDENTS */

if ($search !== '') {

    $stmt = $conn->prepare(
        'SELECT
            s.student_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            s.enrollment_phase,
            u.is_active
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         WHERE
            s.student_number LIKE ?
            OR s.first_name LIKE ?
            OR s.middle_name LIKE ?
            OR s.last_name LIKE ?
            OR s.program LIKE ?
            OR s.section LIKE ?
            OR s.email LIKE ?
         ORDER BY
            s.last_name ASC,
            s.first_name ASC
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param(
        'sssssssii',
        $searchValue,
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
            s.student_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            s.enrollment_phase,
            u.is_active
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         ORDER BY
            s.last_name ASC,
            s.first_name ASC
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param(
        'ii',
        $studentsPerPage,
        $studentOffset
    );
}

$stmt->execute();

$students =
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

    <title>Student Management</title>

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

    <div class="imglogo">
        <span>CMU</span>
    </div>

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

    <main class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>

        <div class="content">

            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div>

                    <h1>Student Management</h1>

                    <p>
                        Manage student accounts and enrollment information.
                    </p>

                </div>

                <div>

                    <button
                        type="button"
                        class="admin-primary-button"
                        id="addStudentButton"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        Add Student
                    </button>

                </div>

            </div>


            <!-- STUDENT SUMMARY -->

            <div class="admin-summary-card">

                <div class="admin-summary-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>

                    <span>Total Students</span>

                    <strong>
                        <?= e($totalStudents) ?>
                    </strong>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="admin-cor-search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Search student..."
                                autocomplete="off"
                            >

                        </div>


            <!-- STUDENT TABLE -->

            <div class="admin-table-card">

                <div class="admin-student-enrollment-table-info">

                    <?php if ($totalFilteredStudents > 0): ?>

                        <?php

                        $displayStart =
                            $studentOffset + 1;

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

                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>Student Number</th>

                                <th>Student Name</th>

                                <th>Program</th>

                                <th>Year</th>

                                <th>Section</th>

                                <th>Email</th>

                                <th>Status</th>

                                <th>Enrollment</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody id="studentTableBody">

                        <?php if (empty($students)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="admin-empty-state"
                                >
                                    No students found.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($students as $student): ?>

                                <?php

                                $studentName = trim(
                                    ($student['first_name'] ?? '') . ' ' .
                                    ($student['middle_name'] ?? '') . ' ' .
                                    ($student['last_name'] ?? '')
                                );

                                $enrollmentPhase =
                                    $student['enrollment_phase'] ?? 'NOT_STARTED';

                                $isActive =
                                    (int) ($student['is_active'] ?? 0) === 1;

                                ?>

                                <tr>

                                    <td>
                                        <?= e($student['student_number']) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= e($studentName) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e($student['program']) ?>
                                    </td>

                                    <td>
                                        <?= e($student['year_level']) ?>
                                    </td>

                                    <td>
                                        <?= e($student['section'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($student['email']) ?>
                                    </td>

                                    <td>

                                        <?php if ($isActive): ?>

                                            <span class="admin-status-badge is-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="admin-status-badge is-inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="admin-status-badge">
                                            <?= e($enrollmentPhase) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <div class="student-management-actions">

                                            <button
                                                type="button"
                                                class="student-management-edit-button"
                                                data-student-id="<?= e($student['student_id']) ?>"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                                Edit
                                            </button>

                                        </div>

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
                                . '?page=student_management'
                                . '&search=' . urlencode($search)
                                . '&student_page='
                                . ($currentStudentPage - 1)
                            ) ?>"
                            class="<?= $currentStudentPage <= 1 ? 'disabled' : '' ?>"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                            Previous
                        </a>


                        <div class="admin-student-enrollment-page-numbers">

                            <?php for (
                                $pageNumber = 1;
                                $pageNumber <= $totalStudentPages;
                                $pageNumber++
                            ): ?>

                                <a
                                    href="<?= e(
                                        BASE_URL
                                        . '?page=student_management'
                                        . '&search=' . urlencode($search)
                                        . '&student_page='
                                        . $pageNumber
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
                                . '?page=student_management'
                                . '&search=' . urlencode($search)
                                . '&student_page='
                                . ($currentStudentPage + 1)
                            ) ?>"
                            class="<?= $currentStudentPage >= $totalStudentPages ? 'disabled' : '' ?>"
                        >
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<!-- ADD STUDENT MODAL -->

<div class="student-modal-overlay" id="addStudentModal">

    <div class="student-modal">

        <div class="student-modal-header">

            <div>
                <h2>Add Student</h2>
                <p>Create a new student account.</p>
            </div>

            <button
                type="button"
                class="student-modal-close"
                id="closeAddStudentModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form id="addStudentForm">

            <div class="student-modal-body">

                <!-- ACCOUNT INFORMATION -->

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user-lock"></i>
                        Account Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="studentNumber">
                                Student Number
                            </label>

                            <input
                                type="text"
                                id="studentNumber"
                                name="student_number"
                                placeholder="e.g. 202401555"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentPassword">
                                Password
                            </label>

                            <input
                                type="password"
                                id="studentPassword"
                                name="password"
                                placeholder="Enter initial password"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- PERSONAL INFORMATION -->

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user"></i>
                        Student Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="studentFirstName">
                                First Name
                            </label>

                            <input
                                type="text"
                                id="studentFirstName"
                                name="first_name"
                                placeholder="First name"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentMiddleName">
                                Middle Name
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="studentMiddleName"
                                name="middle_name"
                                placeholder="Middle name"
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentLastName">
                                Last Name
                            </label>

                            <input
                                type="text"
                                id="studentLastName"
                                name="last_name"
                                placeholder="Last name"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentProgram">
                                Program
                            </label>

                            <input
                                type="text"
                                id="studentProgram"
                                name="program"
                                placeholder="e.g. BS Information Technology"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentYearLevel">
                                Year Level
                            </label>

                            <select
                                id="studentYearLevel"
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


                        <div class="student-form-group">

                            <label for="studentSection">
                                Section
                            </label>

                            <input
                                type="text"
                                id="studentSection"
                                name="section"
                                placeholder="e.g. 3D"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentEmail">
                                Email
                            </label>

                            <input
                                type="email"
                                id="studentEmail"
                                name="email"
                                placeholder="student@example.com"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentPhone">
                                Phone
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="studentPhone"
                                name="phone"
                                placeholder="09XXXXXXXXX"
                            >

                        </div>

                    </div>

                </div>


                <div
                    class="student-form-message"
                    id="addStudentMessage"
                ></div>

            </div>


            <div class="student-modal-footer">

                <button
                    type="button"
                    class="student-modal-cancel"
                    id="cancelAddStudent"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="admin-primary-button"
                    id="saveStudentButton"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Add Student
                </button>

            </div>

        </form>

    </div>

</div>

<!-- EDIT STUDENT MODAL -->

<div class="student-modal-overlay" id="editStudentModal">

    <div class="student-modal">

        <div class="student-modal-header">

            <div>
                <h2>Edit Student</h2>
                <p>Update the student's account and information.</p>
            </div>

            <button
                type="button"
                class="student-modal-close"
                id="closeEditStudentModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form id="editStudentForm">

            <input
                type="hidden"
                id="editStudentId"
                name="student_id"
            >

            <div class="student-modal-body">

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user-lock"></i>
                        Account Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="editStudentNumber">
                                Student Number
                            </label>

                            <input
                                type="text"
                                id="editStudentNumber"
                                name="student_number"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentPassword">
                                Password
                                <span>(Leave blank to keep current)</span>
                            </label>

                            <input
                                type="password"
                                id="editStudentPassword"
                                name="password"
                                placeholder="Leave blank to keep current"
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentStatus">
                                Account Status
                            </label>

                            <select
                                id="editStudentStatus"
                                name="is_active"
                                required
                            >
                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>
                            </select>

                        </div>

                    </div>

                    

                </div>

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user"></i>
                        Student Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="editStudentFirstName">
                                First Name
                            </label>

                            <input
                                type="text"
                                id="editStudentFirstName"
                                name="first_name"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentMiddleName">
                                Middle Name
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="editStudentMiddleName"
                                name="middle_name"
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentLastName">
                                Last Name
                            </label>

                            <input
                                type="text"
                                id="editStudentLastName"
                                name="last_name"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentProgram">
                                Program
                            </label>

                            <input
                                type="text"
                                id="editStudentProgram"
                                name="program"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentYearLevel">
                                Year Level
                            </label>

                            <select
                                id="editStudentYearLevel"
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

                        <div class="student-form-group">

                            <label for="editStudentSection">
                                Section
                            </label>

                            <input
                                type="text"
                                id="editStudentSection"
                                name="section"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentEmail">
                                Email
                            </label>

                            <input
                                type="email"
                                id="editStudentEmail"
                                name="email"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentPhone">
                                Phone
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="editStudentPhone"
                                name="phone"
                            >

                        </div>

                    </div>

                </div>

                <div
                    class="student-form-message"
                    id="editStudentMessage"
                ></div>

            </div>

            <div class="student-modal-footer">

                <button
                    type="button"
                    class="student-modal-cancel"
                    id="cancelEditStudent"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="admin-primary-button"
                    id="updateStudentButton"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>


const addStudentButton =
    document.getElementById('addStudentButton');

const addStudentModal =
    document.getElementById('addStudentModal');

const closeAddStudentModal =
    document.getElementById('closeAddStudentModal');

const cancelAddStudent =
    document.getElementById('cancelAddStudent');

const addStudentForm =
    document.getElementById('addStudentForm');

const addStudentMessage =
    document.getElementById('addStudentMessage');


function openAddStudentModal() {

    addStudentModal.classList.add('is-open');

}


function closeAddStudentModalWindow() {

    addStudentModal.classList.remove('is-open');

    addStudentForm.reset();

    addStudentMessage.className =
        'student-form-message';

    addStudentMessage.textContent = '';

}


addStudentButton.addEventListener(
    'click',
    openAddStudentModal
);


closeAddStudentModal.addEventListener(
    'click',
    closeAddStudentModalWindow
);


cancelAddStudent.addEventListener(
    'click',
    closeAddStudentModalWindow
);


addStudentModal.addEventListener(
    'click',
    function (event) {

        if (event.target === addStudentModal) {
            closeAddStudentModalWindow();
        }

    }
);

addStudentForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();

        addStudentMessage.className =
            'student-form-message';

        addStudentMessage.textContent = '';

        const saveButton =
            document.getElementById('saveStudentButton');

        saveButton.disabled = true;

        saveButton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

        const formData = new FormData(addStudentForm);

        const data = {
            student_id: formData.get('student_id'),
            student_number: formData.get('student_number'),
            password: formData.get('password'),
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            program: formData.get('program'),
            year_level: formData.get('year_level'),
            section: formData.get('section'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            is_active: formData.get('is_active')
        };

        try {

            const response = await fetch(
                'backend/api/add_student.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            );

            const result = await response.json();

            if (!result.success) {

                addStudentMessage.className =
                    'student-form-message is-error';

                addStudentMessage.textContent =
                    result.message;

                return;
            }

            addStudentMessage.className =
                'student-form-message is-success';

            addStudentMessage.textContent =
                result.message;

            setTimeout(function () {
                window.location.reload();
            }, 800);

        } catch (error) {

            addStudentMessage.className =
                'student-form-message is-error';

            addStudentMessage.textContent =
                'Something went wrong. Please try again.';

        } finally {

            saveButton.disabled = false;

            saveButton.innerHTML =
                '<i class="fa-solid fa-user-plus"></i> Add Student';

        }

    }
);

const editStudentModal =
    document.getElementById('editStudentModal');

const closeEditStudentModal =
    document.getElementById('closeEditStudentModal');

const cancelEditStudent =
    document.getElementById('cancelEditStudent');

const editStudentForm =
    document.getElementById('editStudentForm');

const editStudentMessage =
    document.getElementById('editStudentMessage');


function closeEditStudentModalWindow() {

    editStudentModal.classList.remove('is-open');

    editStudentForm.reset();

    editStudentMessage.className =
        'student-form-message';

    editStudentMessage.textContent = '';

}


document
    .querySelectorAll('.student-management-edit-button')
    .forEach(function (button) {

        button.addEventListener(
            'click',
            async function () {

                const studentId =
                    this.dataset.studentId;

                editStudentMessage.className =
                    'student-form-message';

                editStudentMessage.textContent = '';

                try {

                    const response = await fetch(
                        'backend/api/get_student.php?id=' +
                        encodeURIComponent(studentId)
                    );

                    const result =
                        await response.json();

                    if (!result.success) {

                        alert(result.message);

                        return;
                    }

                    const student =
                        result.student;

                    document.getElementById(
                        'editStudentId'
                    ).value = student.student_id;

                    document.getElementById(
                        'editStudentNumber'
                    ).value = student.student_number;

                    document.getElementById(
                        'editStudentStatus'
                    ).value = student.is_active;

                    document.getElementById(
                        'editStudentFirstName'
                    ).value = student.first_name;

                    document.getElementById(
                        'editStudentMiddleName'
                    ).value = student.middle_name || '';

                    document.getElementById(
                        'editStudentLastName'
                    ).value = student.last_name;

                    document.getElementById(
                        'editStudentProgram'
                    ).value = student.program;

                    document.getElementById(
                        'editStudentYearLevel'
                    ).value = student.year_level;

                    document.getElementById(
                        'editStudentSection'
                    ).value = student.section;

                    document.getElementById(
                        'editStudentEmail'
                    ).value = student.email;

                    document.getElementById(
                        'editStudentPhone'
                    ).value = student.phone || '';

                    editStudentModal.classList.add(
                        'is-open'
                    );

                } catch (error) {

                    alert(
                        'Unable to load student information.'
                    );

                }

            }
        );

    });


closeEditStudentModal.addEventListener(
    'click',
    closeEditStudentModalWindow
);


cancelEditStudent.addEventListener(
    'click',
    closeEditStudentModalWindow
);


editStudentModal.addEventListener(
    'click',
    function (event) {

        if (event.target === editStudentModal) {
            closeEditStudentModalWindow();
        }

    }
);


editStudentForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();

        const updateButton =
            document.getElementById(
                'updateStudentButton'
            );

        editStudentMessage.className =
            'student-form-message';

        editStudentMessage.textContent = '';

        updateButton.disabled = true;

        updateButton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        const formData =
            new FormData(editStudentForm);

        const data = {
            student_id: formData.get('student_id'),
            student_number: formData.get('student_number'),
            password: formData.get('password'),
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            program: formData.get('program'),
            year_level: formData.get('year_level'),
            section: formData.get('section'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            is_active: parseInt(
                formData.get('is_active'),
                10
            )
        };

        try {

            const response = await fetch(
                'backend/api/update_student.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            );

            const result =
                await response.json();

            if (!result.success) {

                editStudentMessage.className =
                    'student-form-message is-error';

                editStudentMessage.textContent =
                    result.message;

                return;
            }

            editStudentMessage.className =
                'student-form-message is-success';

            editStudentMessage.textContent =
                result.message;

            setTimeout(function () {

                window.location.reload();

            }, 800);

        } catch (error) {

            editStudentMessage.className =
                'student-form-message is-error';

            editStudentMessage.textContent =
                'Something went wrong. Please try again.';

        } finally {

            updateButton.disabled = false;

            updateButton.innerHTML =
                '<i class="fa-solid fa-floppy-disk"></i> Save Changes';

        }

    }
);

</script>

</body>
</html>