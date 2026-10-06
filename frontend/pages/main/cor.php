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
    header('Location: ' . BASE_URL . '?page=error404');
    exit;
}

$studentNumber = $student['student_number'] ?? '';

$studentName = trim(
    ($student['first_name'] ?? '') . ' ' .
    ($student['middle_name'] ?? '') . ' ' .
    ($student['last_name'] ?? '')
);

$currentPage = 'cor';

$programName = $student['program'] ?? '';
$yearLevel = (int) ($student['year_level'] ?? 0);
$sectionName = $student['section'] ?? '';

$academicYear = '2026-2027';


function cor_year_level(int $yearLevel): string
{
    return match ($yearLevel) {
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
        default => $yearLevel . 'th Year'
    };
}


/*
 * Get the student's latest COR submission.
 */
$stmt = $conn->prepare(
    'SELECT
        cor_submission_id,
        file_name,
        file_path,
        submitted_at,
        status,
        reviewed_at,
        rejection_reason
     FROM cor_submissions
     WHERE student_id = ?
     ORDER BY cor_submission_id DESC
     LIMIT 1'
);

$stmt->bind_param('i', $studentId);
$stmt->execute();

$latestCorSubmission = $stmt->get_result()->fetch_assoc();

$stmt->close();


$corStatus = $latestCorSubmission['status'] ?? 'NOT_SUBMITTED';


$canUpload = in_array(
    $corStatus,
    ['NOT_SUBMITTED', 'REJECTED'],
    true
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Certificate of Registration</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="layout">

    <div class="imglogo">
        <span>CMU</span>
    </div>

    <?php require FRONTEND_PATH . 'includes/sidebar.php'; ?>


    <main class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>


        <div class="content">


            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div>

                    <h1>Certificate of Registration</h1>

                    <p>
                        Submit your signed Certificate of Registration
                        for registrar review.
                    </p>

                </div>

            </div>


            <!-- STUDENT INFORMATION -->

            <div class="cl-card">

                <div class="cl-card-head">

                    <div class="co-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>

                        <h2>Student Information</h2>

                        <p>
                            Your enrollment information
                        </p>

                    </div>

                </div>


                <div class="cl-info">

                    <div class="cl-info-item">

                        <small>
                            Student Number
                        </small>

                        <strong>
                            <?= htmlspecialchars($studentNumber) ?>
                        </strong>

                    </div>


                    <div class="cl-info-item">

                        <small>
                            Student Name
                        </small>

                        <strong>
                            <?= htmlspecialchars($studentName) ?>
                        </strong>

                    </div>


                    <div class="cl-info-item">

                        <small>
                            Program
                        </small>

                        <strong>
                            <?= htmlspecialchars($programName) ?>
                        </strong>

                    </div>


                    <div class="cl-info-item">

                        <small>
                            Year Level
                        </small>

                        <strong>
                            <?= htmlspecialchars(cor_year_level($yearLevel)) ?>
                        </strong>

                    </div>


                    <div class="cl-info-item">

                        <small>
                            Section
                        </small>

                        <strong>
                            <?= htmlspecialchars($sectionName) ?>
                        </strong>

                    </div>


                    <div class="cl-info-item">

                        <small>
                            Academic Year
                        </small>

                        <strong>
                            <?= htmlspecialchars($academicYear) ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- COR SUBMISSION PROCESS -->

            <div class="cl-card">

                <div class="cl-card-head">

                    <div class="co-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>

                    <div>

                        <h2>COR Submission Process</h2>

                        <p>
                            Follow these steps before submitting your COR.
                        </p>

                    </div>

                </div>


                <div class="cl-steps">


                    <!-- STEP 1 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-download"></i>

                        </div>

                        <h3>
                            Download
                        </h3>

                        <p>
                            Get your COR.
                        </p>

                    </div>


                    <div class="cl-step-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>


                    <!-- STEP 2 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-print"></i>

                        </div>

                        <h3>
                            Print
                        </h3>

                        <p>
                            Print the document.
                        </p>

                    </div>


                    <div class="cl-step-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>


                    <!-- STEP 3 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-pen"></i>

                        </div>

                        <h3>
                            Sign
                        </h3>

                        <p>
                            Sign the COR.
                        </p>

                    </div>


                    <div class="cl-step-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>


                    <!-- STEP 4 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-camera"></i>

                        </div>

                        <h3>
                            Scan / Photo
                        </h3>

                        <p>
                            Create a clear copy.
                        </p>

                    </div>


                    <div class="cl-step-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>


                    <!-- STEP 5 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-upload"></i>

                        </div>

                        <h3>
                            Upload
                        </h3>

                        <p>
                            Upload your COR.
                        </p>

                    </div>


                    <div class="cl-step-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>


                    <!-- STEP 6 -->

                    <div class="cl-step">

                        <div class="co-icon">

                            <i class="fa-solid fa-paper-plane"></i>

                        </div>

                        <h3>
                            Submit
                        </h3>

                        <p>
                            Send to Registrar.
                        </p>

                    </div>


                </div>

            </div>


            <!-- MAIN CONTENT GRID -->

            <div class="cl-grid">


                <!-- LEFT COLUMN -->

                <div class="cl-col">


                    <!-- COR DOCUMENT -->

                    <div class="cl-card">

                        <div class="cl-card-head">

                            <div class="co-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>

                            <div>

                                <h2>Certificate of Registration</h2>

                                <p>
                                    Download your COR before signing.
                                </p>

                            </div>

                        </div>


                        <div class="cl-status">

                            <i class="fa-solid fa-file-pdf"></i>

                            <strong>
                                Certificate of Registration
                            </strong>

                            <span>
                                Academic Year <?= htmlspecialchars($academicYear) ?>
                            </span>

                            <button
                                type="button"
                                class="cl-action"
                            >

                                <i class="fa-solid fa-download"></i>

                                Download COR

                            </button>

                        </div>

                    </div>


                    <!-- UPLOAD -->

                    <div class="cl-card">

                        <div class="cl-card-head">

                            <div class="co-icon">
                                <i class="fa-solid fa-upload"></i>
                            </div>

                            <div>

                                <h2>Upload Signed COR</h2>

                                <p>
                                    Upload the completed and signed document.
                                </p>

                            </div>

                        </div>


                        <form
                            action="<?= BASE_URL ?>backend/api/submit_cor.php"
                            method="POST"
                            enctype="multipart/form-data"
                            id="corForm"
                        >

                            <div class="cl-dropzone"
                                 id="corDropzone">

                                <input
                                    type="file"
                                    id="corFile"
                                    name="cor_file"
                                    accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                    <?= $canUpload ? '' : 'disabled' ?>
                                    hidden
                                >


                                <label
                                    for="corFile"
                                    style="cursor: pointer;"
                                >

                                    <div class="co-icon"
                                         style="margin: 0 auto 6px;">

                                        <i class="fa-solid fa-cloud-arrow-up"></i>

                                    </div>


                                    <strong
                                        class="cl-drop-title"
                                        id="corDropTitle"
                                    >
                                        Choose your signed COR
                                    </strong>


                                    <span
                                        class="cl-drop-hint"
                                        id="corDropHint"
                                    >
                                        JPG, PNG, or PDF • Maximum 10 MB
                                    </span>

                                </label>

                            </div>


                            <button
                                type="submit"
                                id="corSubmit"
                                class="cl-action"
                                disabled
                            >

                                <i class="fa-solid fa-paper-plane"></i>

                                Submit COR

                            </button>

                        </form>

                    </div>


                    <!-- SUBMISSION STATUS -->

                    <div class="cl-card">

                        <div class="cl-card-head">

                            <div class="co-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>

                            <div>

                                <h2>Submission Status</h2>

                                <p>
                                    Current status of your COR submission.
                                </p>

                            </div>

                        </div>


                        <?php if ($corStatus === 'NOT_SUBMITTED'): ?>

                            <div class="cl-status">

                                <i class="fa-solid fa-file-circle-exclamation"></i>

                                <strong>
                                    Not Submitted
                                </strong>

                                <span>
                                    You have not submitted your signed COR yet.
                                </span>

                            </div>


                        <?php elseif ($corStatus === 'PENDING'): ?>

                            <div class="cl-status is-pending">

                                <i class="fa-solid fa-clock"></i>

                                <strong>
                                    Pending Review
                                </strong>

                                <span>
                                    Your COR has been submitted and is waiting
                                    for Registrar review.
                                </span>

                            </div>


                        <?php elseif ($corStatus === 'APPROVED'): ?>

                            <div class="cl-status is-approved">

                                <i class="fa-solid fa-circle-check"></i>

                                <strong>
                                    Approved
                                </strong>

                                <span>
                                    Your COR has been approved.
                                </span>

                            </div>


                        <?php elseif ($corStatus === 'REJECTED'): ?>

                            <div class="cl-status is-rejected">

                                <i class="fa-solid fa-circle-xmark"></i>

                                <strong>
                                    Rejected
                                </strong>

                                <span>
                                    Your COR was rejected.
                                    Please submit a corrected copy.
                                </span>

                                <?php if (!empty($latestCorSubmission['rejection_reason'])): ?>

                                    <div class="cl-rejection-reason">
                                        <strong>Reason:</strong>

                                        <?= e($latestCorSubmission['rejection_reason']) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- RIGHT COLUMN -->

                <div class="cl-col">


                    <!-- GUIDELINES -->

                    <div class="cl-card">

                        <div class="cl-card-head">

                            <div class="co-icon">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>

                            <div>

                                <h2>COR Submission Guidelines</h2>

                                <p>
                                    Important reminders before submitting.
                                </p>

                            </div>

                        </div>


                        <div class="cl-status">

                            <div style="text-align: left; width: 100%;">

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Make sure the COR is readable.
                                </p>

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Make sure required signatures are present.
                                </p>

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Upload the complete signed COR.
                                </p>

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Accepted files: JPG, PNG, or PDF.
                                </p>

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Maximum file size is 10 MB.
                                </p>

                                <p>
                                    <i class="fa-solid fa-check"></i>
                                    Only one pending submission is allowed.
                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- AFTER SUBMISSION -->

                    <div class="cl-card">

                        <div class="cl-card-head">

                            <div class="co-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>

                            <div>

                                <h2>After Submission</h2>

                                <p>
                                    What happens next?
                                </p>

                            </div>

                        </div>


                        <div class="cl-status">

                            <i class="fa-solid fa-user-check"></i>

                            <span>
                                Your signed COR will be reviewed by the
                                Registrar.
                            </span>

                            <span>
                                Once approved, your enrollment status will
                                be updated to <strong>Enrolled</strong>.
                            </span>

                        </div>

                    </div>


                </div>


            </div>


        </div>

    </main>

</div>


<script>

const corForm = document.getElementById('corForm');
const corFile = document.getElementById('corFile');
const corSubmit = document.getElementById('corSubmit');
const corDropzone = document.getElementById('corDropzone');
const corDropTitle = document.getElementById('corDropTitle');
const corDropHint = document.getElementById('corDropHint');


corFile.addEventListener('change', function () {

    const file = this.files[0];

    corSubmit.disabled = true;

    corDropzone.classList.remove(
        'has-file',
        'has-error'
    );


    if (!file) {

        corDropTitle.textContent =
            'Choose your signed COR';

        corDropHint.textContent =
            'JPG, PNG, or PDF • Maximum 10 MB';

        return;

    }


    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf'
    ];


    const maxSize = 10 * 1024 * 1024;


    if (!allowedTypes.includes(file.type)) {

        corDropzone.classList.add('has-error');

        corDropTitle.textContent =
            'Invalid file type';

        corDropHint.textContent =
            'Please upload a JPG, PNG, or PDF file.';

        this.value = '';

        return;

    }


    if (file.size > maxSize) {

        corDropzone.classList.add('has-error');

        corDropTitle.textContent =
            'File is too large';

        corDropHint.textContent =
            'The maximum file size is 10 MB.';

        this.value = '';

        return;

    }


    corDropzone.classList.add('has-file');

    corDropTitle.textContent =
        file.name;

    corDropHint.textContent =
        'File selected successfully.';

    corSubmit.disabled = false;

});


corDropzone.addEventListener('dragover', function (event) {

    event.preventDefault();

    corDropzone.classList.add('is-dragging');

});


corDropzone.addEventListener('dragleave', function () {

    corDropzone.classList.remove('is-dragging');

});


corDropzone.addEventListener('drop', function (event) {

    event.preventDefault();

    corDropzone.classList.remove('is-dragging');

    const files = event.dataTransfer.files;

    if (!files.length) {
        return;
    }

    corFile.files = files;

    corFile.dispatchEvent(
        new Event('change')
    );

});


corForm.addEventListener('submit', async function (event) {

    event.preventDefault();


    if (!corFile.files.length) {

        alert('Please select a COR file first.');

        return;

    }


    corSubmit.disabled = true;

    corSubmit.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';


    try {

        const formData = new FormData(corForm);


        const response = await fetch(
            corForm.action,
            {
                method: 'POST',
                body: formData
            }
        );


        const result = await response.json();


        if (!result.success) {

            alert(result.message);

            corSubmit.disabled = false;

            corSubmit.innerHTML =
                '<i class="fa-solid fa-paper-plane"></i> Submit COR';

            return;

        }


        alert(result.message);


        /*
         * Refresh the page so the submission
         * status can later be loaded from DB.
         */

        window.location.reload();


    } catch (error) {

        alert(
            'Something went wrong while submitting your COR.'
        );


        corSubmit.disabled = false;

        corSubmit.innerHTML =
            '<i class="fa-solid fa-paper-plane"></i> Submit COR';

    }

});

</script>
</body>
</html>