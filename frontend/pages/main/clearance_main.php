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

$currentPage = 'clearance_main';

/* ---------------------------------------------------------
   STUDENT INFO
   TODO: replace the fallbacks with your real column names.
--------------------------------------------------------- */
$studentNumber = $student['student_number'] ?? '2024-1234';
$programName   = $student['program']        ?? 'BS Information Technology';
$yearLevel     = $student['year_level']     ?? '3rd Year';
$sectionName   = $student['section']        ?? 'BSIT - 3D';
$academicYear  = '2026-2027';

/* ---------------------------------------------------------
   CLEARANCE STATUS
   NOT_SUBMITTED | PENDING | APPROVED | REJECTED
   TODO: load the real value from your clearance table.
--------------------------------------------------------- */
$clearanceStatus = 'NOT_SUBMITTED';

$statusMap = [
    'NOT_SUBMITTED' => ['icon' => 'fa-file-circle-question', 'class' => '',             'text' => 'Your clearance form has not been submitted yet'],
    'PENDING'       => ['icon' => 'fa-clock',                'class' => 'is-pending',   'text' => 'Your clearance form is waiting for review'],
    'APPROVED'      => ['icon' => 'fa-circle-check',         'class' => 'is-approved',  'text' => 'Your clearance form has been approved'],
    'REJECTED'      => ['icon' => 'fa-circle-xmark',         'class' => 'is-rejected',  'text' => 'Your clearance form was rejected. Please upload a new copy'],
];

$statusView = $statusMap[$clearanceStatus] ?? $statusMap['NOT_SUBMITTED'];

// Students can only upload while nothing is waiting or approved.
$canUpload = in_array($clearanceStatus, ['NOT_SUBMITTED', 'REJECTED'], true);

/* ---------------------------------------------------------
   SIGNATURE CHECKLIST
   'signed' is read-only here: offices tick it on their side.
   TODO: replace with a query on the student's clearance records.
--------------------------------------------------------- */
$signatures = [
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Health Services Office', 'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Program Chair',        'signed' => false],
    ['office' => 'College Dean',         'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
    ['office' => 'Computer Laboratory',  'signed' => false],
];

$steps = [
    ['icon' => 'fa-download',      'title' => 'Download',   'text' => 'Get the form from the system'],
    ['icon' => 'fa-print',         'title' => 'Print',     'text' => 'Print on the required paper'],
    ['icon' => 'fa-file-signature','title' => 'Signature', 'text' => 'Acquire their signatures'],
    ['icon' => 'fa-camera',        'title' => 'Scan/Photo','text' => 'Make sure it\'s clear and readable'],
    ['icon' => 'fa-upload',        'title' => 'Upload',    'text' => 'Upload the completed form'],
    ['icon' => 'fa-paper-plane',   'title' => 'Submit',    'text' => 'Send the verification'],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Clearance</title>

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
                <h1>Clearance</h1>
                <p>Student Clearance Form</p>
            </section>


            <!-- =========================
                 STUDENT INFORMATION
            ========================== -->
            <section class="cl-card">

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
                        <small>Student Number</small>
                        <strong><?= e($studentNumber) ?></strong>
                    </div>

                    <div class="cl-info-item">
                        <small>Name</small>
                        <strong><?= e($userName) ?></strong>
                    </div>

                    <div class="cl-info-item">
                        <small>Program</small>
                        <strong><?= e($programName) ?></strong>
                    </div>

                    <div class="cl-info-item">
                        <small>Year Level</small>
                        <strong><?= e($yearLevel) ?></strong>
                    </div>

                    <div class="cl-info-item">
                        <small>Section</small>
                        <strong><?= e($sectionName) ?></strong>
                    </div>

                    <div class="cl-info-item">
                        <small>Academic Year</small>
                        <strong><?= e($academicYear) ?></strong>
                    </div>

                </div>

            </section>


            <!-- =========================
                 STEPS
            ========================== -->
            <section class="cl-card cl-steps" aria-label="Clearance steps">

                <?php foreach ($steps as $i => $step): ?>

                    <div class="cl-step">
                        <span class="co-icon"><i class="fa-solid <?= e($step['icon']) ?>"></i></span>
                        <h3><?= e($step['title']) ?></h3>
                        <p><?= e($step['text']) ?></p>
                    </div>

                    <?php if ($i < count($steps) - 1): ?>
                        <i class="fa-solid fa-arrow-right cl-step-arrow" aria-hidden="true"></i>
                    <?php endif; ?>

                <?php endforeach; ?>

            </section>


            <!-- =========================
                 MAIN GRID
            ========================== -->
            <section class="cl-grid">

                <!-- LEFT COLUMN -->
                <div class="cl-col">

                    <!-- DOWNLOAD -->
                    <div class="cl-card">

                        <div class="cl-card-head">
                            <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                            <div>
                                <h2>Clearance Form</h2>
                                <p>Download the official clearance form, print it, and obtain the required signatures.</p>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>backend/api/download_clearance.php"
                           class="cl-action">
                            <i class="fa-solid fa-download"></i>
                            Download Clearance Form
                        </a>

                    </div>


                    <!-- UPLOAD -->
                    <div class="cl-card">

                        <div class="cl-card-head">
                            <span class="co-icon"><i class="fa-solid fa-upload"></i></span>
                            <div>
                                <h2>Upload completed clearance</h2>
                                <p>Upload a clear scan or photo of your signed clearance form</p>
                            </div>
                        </div>

                        <form action="<?= BASE_URL ?>backend/api/submit_clearance.php"
                              method="POST"
                              enctype="multipart/form-data"
                              id="clearanceForm">

                            <label class="cl-dropzone <?= $canUpload ? '' : 'is-disabled' ?>"
                                   id="dropzone"
                                   for="clearanceFile">

                                <span class="co-icon"><i class="fa-solid fa-paperclip"></i></span>

                                <span class="cl-drop-title" id="dropTitle">
                                    Drag &amp; drop your file here or click to browse
                                </span>

                                <span class="cl-drop-hint" id="dropHint">
                                    JPG, PNG, or PDF - Max file size: 10 MB
                                </span>

                                <input type="file"
                                       id="clearanceFile"
                                       name="clearance_file"
                                       accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                       <?= $canUpload ? '' : 'disabled' ?>
                                       hidden>

                            </label>

                            <button type="submit"
                                    class="cl-action"
                                    id="submitButton"
                                    disabled>
                                <i class="fa-regular fa-paper-plane"></i>
                                Submit
                            </button>

                        </form>

                    </div>


                    <!-- STATUS -->
                    <div class="cl-card">

                        <div class="cl-card-head">
                            <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                            <h2>Submission Status</h2>
                        </div>

                        <div class="cl-status <?= e($statusView['class']) ?>" role="status">
                            <i class="fa-regular <?= e($statusView['icon']) ?>"></i>
                            <?= e($statusView['text']) ?>
                        </div>

                    </div>

                </div>


                <!-- RIGHT COLUMN -->
                <div class="cl-card cl-checklist">

                    <div class="cl-card-head">
                        <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                        <h2>Required Signatures Check List</h2>
                    </div>

                    <div class="cl-table-wrap">

                        <table class="cl-table">

                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th>Department / Office</th>
                                    <th class="col-check">Check Box</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($signatures as $i => $row): ?>

                                    <tr>
                                        <td class="col-num"><?= $i + 1 ?></td>
                                        <td><?= e($row['office']) ?></td>
                                        <td class="col-check">
                                            <input type="checkbox"
                                                   class="cl-checkbox"
                                                   disabled
                                                   <?= $row['signed'] ? 'checked' : '' ?>
                                                   aria-label="<?= e($row['office']) ?> signed">
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

<script>
(function () {
    var MAX_SIZE = 10 * 1024 * 1024;
    var ALLOWED  = ['image/jpeg', 'image/png', 'application/pdf'];

    var input     = document.getElementById('clearanceFile');
    var dropzone  = document.getElementById('dropzone');
    var title     = document.getElementById('dropTitle');
    var hint      = document.getElementById('dropHint');
    var submitBtn = document.getElementById('submitButton');

    if (!input || input.disabled) return;

    var defaultTitle = title.textContent;
    var defaultHint  = hint.textContent;

    function reset(message) {
        input.value = '';
        submitBtn.disabled = true;
        dropzone.classList.remove('has-file');
        title.textContent = defaultTitle;
        hint.textContent = message || defaultHint;
        dropzone.classList.toggle('has-error', !!message);
    }

    function check() {
        var file = input.files[0];
        if (!file) return reset();

        if (ALLOWED.indexOf(file.type) === -1) {
            return reset('That file type is not allowed. Use a JPG, PNG, or PDF.');
        }
        if (file.size > MAX_SIZE) {
            return reset('That file is larger than 10 MB. Choose a smaller one.');
        }

        dropzone.classList.remove('has-error');
        dropzone.classList.add('has-file');
        title.textContent = file.name;
        hint.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB - ready to submit';
        submitBtn.disabled = false;
    }

    input.addEventListener('change', check);

    ['dragenter', 'dragover'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone.addEventListener('drop', function (event) {
        if (event.dataTransfer && event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            check();
        }
    });
})();
</script>

</body>
</html>