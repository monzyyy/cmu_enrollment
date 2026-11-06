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

$userName = $studentName;

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


// Resolve the active academic period before reading COR submissions.
$settingsStmt = $conn->prepare(
    'SELECT enrollment_status, semester, school_year
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);
$settingsStmt->execute();
$settings = $settingsStmt->get_result()->fetch_assoc();
$settingsStmt->close();
$academicYear = $settings['school_year'] ?? $academicYear;

$currentPeriodId = null;
if ($settings) {
    $periodStmt = $conn->prepare(
        'SELECT period_id FROM enrollment_periods
         WHERE semester = ? AND school_year = ? LIMIT 1'
    );
    $periodStmt->bind_param('ss', $settings['semester'], $settings['school_year']);
    $periodStmt->execute();
    $periodRow = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();
    $currentPeriodId = $periodRow ? (int) $periodRow['period_id'] : null;
}

// Only the current period's COR may affect this page.
$latestCorSubmission = null;
if ($currentPeriodId !== null) {
    $stmt = $conn->prepare(
        'SELECT cor_submission_id, file_name, file_path, submitted_at,
                status, reviewed_at, rejection_reason
         FROM cor_submissions
         WHERE student_id = ? AND period_id = ?
         ORDER BY cor_submission_id DESC LIMIT 1'
    );
    $stmt->bind_param('ii', $studentId, $currentPeriodId);
    $stmt->execute();
    $latestCorSubmission = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$corStatus = $latestCorSubmission['status'] ?? 'NOT_SUBMITTED';

// Check whether Clearance has been approved
// for the current semester and school year.
$hasApprovedClearance = false;

if ($settings) {
    $clearanceStmt = $conn->prepare(
        'SELECT clearance_id
         FROM clearance_submissions
         WHERE student_id = ?
           AND semester = ?
           AND school_year = ?
           AND status = "APPROVED"
         LIMIT 1'
    );

    $clearanceStmt->bind_param(
        'iss',
        $studentId,
        $settings['semester'],
        $settings['school_year']
    );

    $clearanceStmt->execute();

    $hasApprovedClearance = (bool)
        $clearanceStmt->get_result()->fetch_assoc();

    $clearanceStmt->close();
}

// COR becomes available only after Clearance approval.
$corUnlocked =
    ($student['enrollment_phase'] ?? '') === 'CLEARANCE'
    && $hasApprovedClearance
    && $currentPeriodId !== null
    && ($settings['enrollment_status'] ?? '') === 'OPEN';

// Download remains available after COR submission/approval, but never before clearance approval.
$canDownloadCor = $currentPeriodId !== null
    && $hasApprovedClearance
    && in_array(($student['enrollment_phase'] ?? ''), ['CLEARANCE', 'COR', 'ENROLLED'], true)
    && (($settings['enrollment_status'] ?? '') === 'OPEN' || $corStatus === 'APPROVED');

// Prevent duplicate pending or approved submissions.
$canUpload = $corUnlocked && in_array(
    $corStatus,
    ['NOT_SUBMITTED', 'REJECTED'],
    true
);



$steps = [
    ['icon' => 'fa-download', 'title' => 'Download', 'text' => 'Get your COR'],
    ['icon' => 'fa-print', 'title' => 'Print', 'text' => 'Print the document'],
    ['icon' => 'fa-file-signature', 'title' => 'Signature', 'text' => 'Sign the COR'],
    ['icon' => 'fa-camera', 'title' => 'Scan/Photo', 'text' => 'Make a readable copy'],
    ['icon' => 'fa-upload', 'title' => 'Upload', 'text' => 'Upload the signed COR'],
    ['icon' => 'fa-paper-plane', 'title' => 'Submit', 'text' => 'Send to Registrar'],
];

$corChecklist = [
    'Student information is correct',
    'Subjects and schedule are complete',
    'Required signatures are present',
    'The signed document is clear and readable',
];

$statusMap = [
    'NOT_SUBMITTED' => ['icon' => 'fa-file-circle-question', 'class' => '', 'text' => 'Your COR has not been submitted yet'],
    'PENDING' => ['icon' => 'fa-clock', 'class' => 'is-pending', 'text' => 'Your COR is waiting for Registrar review'],
    'APPROVED' => ['icon' => 'fa-circle-check', 'class' => 'is-approved', 'text' => 'Your COR has been approved'],
    'REJECTED' => ['icon' => 'fa-circle-xmark', 'class' => 'is-rejected', 'text' => 'Your COR was rejected. Please upload a corrected copy'],
];
$statusView = $statusMap[$corStatus] ?? $statusMap['NOT_SUBMITTED'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Certificate of Registration</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- COR steps: keep the desktop layout, stack steps neatly on tablets and phones. -->
    <style>
      @media (max-width: 900px) {
        .cl-steps {
          display: flex !important;
          flex-direction: column !important;
          flex-wrap: nowrap !important;
          align-items: stretch !important;
          justify-content: flex-start !important;
          gap: 0 !important;
          min-width: 0 !important;
        }
        .cl-steps > .cl-step {
          display: flex !important;
          flex-direction: column !important;
          align-items: center !important;
          justify-content: center !important;
          width: 100% !important;
          max-width: none !important;
          min-width: 0 !important;
          flex: 0 0 auto !important;
          box-sizing: border-box !important;
          text-align: center !important;
          margin: 0 !important;
        }
        .cl-steps > .cl-step-arrow {
          display: block !important;
          position: static !important;
          align-self: center !important;
          width: auto !important;
          height: auto !important;
          flex: 0 0 auto !important;
          margin: 10px auto !important;
          transform: rotate(90deg) !important;
          text-align: center !important;
        }
      }
    </style>

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
            <section class="page-heading"><h1>Certificate of Registration</h1><p>Student Certificate of Registration (COR)</p></section>
<section class="cl-card">
  <div class="cl-card-head"><span class="co-icon"><i class="fa-solid fa-user"></i></span><div><h2>Student Information</h2><p>Your enrollment information</p></div></div>
  <div class="cl-info">
    <div class="cl-info-item"><small>Student Number</small><strong><?= e($studentNumber) ?></strong></div>
    <div class="cl-info-item"><small>Student Name</small><strong><?= e($studentName) ?></strong></div>
    <div class="cl-info-item"><small>Program</small><strong><?= e($programName) ?></strong></div>
    <div class="cl-info-item"><small>Year Level</small><strong><?= e(cor_year_level($yearLevel)) ?></strong></div>
    <div class="cl-info-item"><small>Section</small><strong><?= e($sectionName) ?></strong></div>
    <div class="cl-info-item"><small>Academic Year</small><strong><?= e($academicYear) ?></strong></div>
  </div>
</section>
<section class="cl-card cl-steps" aria-label="COR submission steps">
  <?php foreach ($steps as $i => $step): ?>
    <div class="cl-step"><span class="co-icon"><i class="fa-solid <?= e($step['icon']) ?>"></i></span><h3><?= e($step['title']) ?></h3><p><?= e($step['text']) ?></p></div>
    <?php if ($i < count($steps) - 1): ?><i class="fa-solid fa-arrow-right cl-step-arrow" aria-hidden="true"></i><?php endif; ?>
  <?php endforeach; ?>
</section>
<section class="cl-grid">
  <div class="cl-col">
    <div class="cl-card">
      <div class="cl-card-head"><span class="co-icon"><i class="fa-solid fa-file-lines"></i></span><div><h2>Certificate of Registration</h2><p>Download your COR, print it, and obtain the required signatures.</p></div></div>
      <a href="<?= $canDownloadCor ? BASE_URL . 'backend/api/download_cor.php' : '#' ?>" class="cl-action" id="downloadCorButton" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none<?= $canDownloadCor ? '' : ';opacity:.55;cursor:not-allowed' ?>" <?= $canDownloadCor ? '' : 'aria-disabled="true" onclick="return false;" title="Clearance approval is required before downloading COR"' ?>><i class="fa-solid fa-download"></i> Download COR</a>
    </div>
    <div class="cl-card">
      <div class="cl-card-head"><span class="co-icon"><i class="fa-solid fa-upload"></i></span><div><h2>Upload Signed COR</h2><p>Upload a clear scan or photo of your signed COR.</p></div></div>
      <?php if (!$corUnlocked && $corStatus !== 'APPROVED'): ?>
        <div class="cl-status is-pending" style="margin-bottom:15px" role="status"><i class="fa-solid fa-lock"></i><strong>COR Upload Locked</strong><span>
          <?php if (!$currentPeriodId): ?>The active enrollment period has not been configured. Please contact the Registrar.
          <?php elseif (($settings['enrollment_status'] ?? '') !== 'OPEN'): ?>The enrollment period is currently closed.
          <?php elseif ($corStatus === 'PENDING'): ?>Your COR is pending Registrar review.
          <?php else: ?>Complete Professor Evaluation and receive Clearance approval before submitting COR.<?php endif; ?>
        </span></div>
      <?php endif; ?>
      <form action="<?= BASE_URL ?>backend/api/submit_cor.php" method="POST" enctype="multipart/form-data" id="corForm">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <label class="cl-dropzone <?= $canUpload ? '' : 'is-disabled' ?>" id="dropzone" for="corFile">
          <span class="co-icon"><i class="fa-solid fa-paperclip"></i></span>
          <span class="cl-drop-title" id="dropTitle">Drag &amp; drop your file here or click to browse</span>
          <span class="cl-drop-hint" id="dropHint">JPG, PNG, or PDF - Max file size: 10 MB</span>
          <input type="file" id="corFile" name="cor_file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" <?= $canUpload ? '' : 'disabled' ?> hidden>
        </label>
        <button type="submit" class="cl-action" id="corSubmit" disabled><i class="fa-regular fa-paper-plane"></i> Submit COR</button>
      </form>
    </div>
    <div class="cl-card">
      <div class="cl-card-head"><span class="co-icon"><i class="fa-solid fa-clock"></i></span><div><h2>Submission Status</h2><p>Current status of your COR submission.</p></div></div>
      <div class="cl-status <?= e($statusView['class']) ?>" role="status"><i class="fa-solid <?= e($statusView['icon']) ?>"></i><?= e($statusView['text']) ?></div>
      <?php if ($corStatus === 'REJECTED' && !empty($latestCorSubmission['rejection_reason'])): ?>
        <p style="color:#dc3545;margin-top:12px;font-size:13px"><strong>Reason for rejection:</strong> <?= e($latestCorSubmission['rejection_reason']) ?></p>
      <?php endif; ?>
    </div>
  </div>
  <div class="cl-card cl-checklist">
    <div class="cl-card-head"><span class="co-icon"><i class="fa-solid fa-list-check"></i></span><div><h2>COR Submission Checklist</h2><p>Review these details before submitting your document.</p></div></div>
    <div class="cl-table-wrap"><table class="cl-table">
      <thead><tr><th class="col-num">#</th><th>Requirement</th><th class="col-check">Check Box</th></tr></thead>
      <tbody><?php foreach ($corChecklist as $i => $item): ?>
        <tr><td class="col-num"><?= $i + 1 ?></td><td><?= e($item) ?></td><td class="col-check"><input type="checkbox" class="cl-checkbox" aria-label="<?= e($item) ?>"></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
</section>
</div></div></div>
<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>



<script>
(function () {
    var MAX_SIZE = 10 * 1024 * 1024;
    var ALLOWED  = ['image/jpeg', 'image/png', 'application/pdf'];

    var input     = document.getElementById('corFile');
    var dropzone  = document.getElementById('dropzone');
    var title     = document.getElementById('dropTitle');
    var hint      = document.getElementById('dropHint');
    var submitBtn = document.getElementById('corSubmit');

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
    
    const form = document.getElementById('corForm');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!input.files.length || submitBtn.disabled) {
            return;
        }

        submitBtn.disabled = true;

        const originalButton = submitBtn.innerHTML;

        submitBtn.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('The server could not process the COR submission. Please check the COR status before trying again. If the issue persists, contact the administrator.');
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Submission failed.'
                );
            }

            alert(result.message);

            // Refresh to display PENDING status.
            window.location.reload();

        } catch (error) {
            alert(error.message || 'Unable to submit COR.');

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalButton;
        }
    });


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
        if (!input.disabled && event.dataTransfer && event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            check();
        }
    });
})();
</script>

</body>
</html>