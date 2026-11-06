<?php
require_role('STUDENT');
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 3) . '/backend/models/student.php';

$studentId = (int) ($_SESSION['student_id'] ?? 0);
if (!$studentId) {
header('Location: ' . BASE_URL . '?page=login');
exit;
}
$student = student_find_by_id($conn, $studentId);
if (!$student) {
header('Location: ' . BASE_URL . '?page=login');
exit;
}
$userName = trim(($student['first_name'] ?? '') . ' ' . (!empty($student['middle_name']) ? $student['middle_name'] . ' ' : '') . ($student['last_name'] ?? ''));
$currentPage = 'my_enrollment';

$settingsStmt = $conn->prepare('SELECT enrollment_status, semester, school_year, start_date, end_date FROM system_settings WHERE setting_id = 1 LIMIT 1');
$settingsStmt->execute();
$settings = $settingsStmt->get_result()->fetch_assoc() ?: [];
$settingsStmt->close();

$phase = strtoupper((string) ($student['enrollment_phase'] ?? 'NOT_STARTED'));
$phaseSteps = ['NOT_STARTED' => 0, 'EVALUATION' => 1, 'CLEARANCE' => 2, 'COR' => 3, 'ENROLLED' => 4];
$stepsDone = $phaseSteps[$phase] ?? 0;
$percent = $stepsDone * 25;

 
$clearanceStmt = $conn->prepare(
'SELECT status, rejection_reason FROM clearance_submissions
     WHERE student_id = ? AND semester = ?
       AND REPLACE(school_year, " ", "") = REPLACE(?, " ", "")
     ORDER BY clearance_id DESC LIMIT 1'
);
$clearanceStmt->bind_param('iss', $studentId, $settings['semester'], $settings['school_year']);
$clearanceStmt->execute();
$clearance = $clearanceStmt->get_result()->fetch_assoc() ?: null;
$clearanceStmt->close();

$corStmt = $conn->prepare(
'SELECT cs.status, cs.rejection_reason FROM cor_submissions cs
     INNER JOIN enrollment_periods ep ON ep.period_id = cs.period_id
     WHERE cs.student_id = ? AND ep.semester = ?
       AND REPLACE(ep.school_year, " ", "") = REPLACE(?, " ", "")
     ORDER BY cs.cor_submission_id DESC LIMIT 1'
);
$corStmt->bind_param('iss', $studentId, $settings['semester'], $settings['school_year']);
$corStmt->execute();
$cor = $corStmt->get_result()->fetch_assoc() ?: null;
$corStmt->close();

 
$historyStmt = $conn->prepare(
'SELECT cs.cor_submission_id, cs.reviewed_at, cs.submitted_at,
            ep.semester, ep.school_year
     FROM cor_submissions cs
     INNER JOIN enrollment_periods ep ON ep.period_id = cs.period_id
     WHERE cs.student_id = ? AND cs.status = "APPROVED"
       AND NOT (ep.semester = ? AND REPLACE(ep.school_year, " ", "") = REPLACE(?, " ", ""))
     ORDER BY ep.school_year DESC,
              CASE ep.semester WHEN "Summer" THEN 3 WHEN "2nd Semester" THEN 2 ELSE 1 END DESC,
              cs.reviewed_at DESC, cs.cor_submission_id DESC'
);
$historyStmt->bind_param('iss', $studentId, $settings['semester'], $settings['school_year']);
$historyStmt->execute();
$history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

$clearanceStatus = strtoupper((string) ($clearance['status'] ?? 'NOT_SUBMITTED'));
$corStatus = strtoupper((string) ($cor['status'] ?? 'NOT_SUBMITTED'));

$labels = [
'NOT_STARTED' => 'Not Started',
'EVALUATION' => 'Evaluation Completed',
'CLEARANCE' => 'Clearance Completed',
'COR' => 'COR In Progress',
'ENROLLED' => 'Officially Enrolled',
];
$phaseLabel = $labels[$phase] ?? 'Enrollment Status Unavailable';
$period = 'Not set';
if (!empty($settings['start_date']) && !empty($settings['end_date'])) {
$start = strtotime($settings['start_date']);
$end = strtotime($settings['end_date']);
if ($start && $end) $period = date('M j, Y', $start) . ' – ' . date('M j, Y', $end);
}

$stages = [
[
'title' => 'Professor Evaluation', 'icon' => 'fa-clipboard-check', 'url' => 'faculty_evaluation',
'done' => $stepsDone >= 1,
'status' => $stepsDone >= 1 ? 'Completed' : ($stepsDone === 0 ? 'To Do' : 'Not Started'),
'detail' => $stepsDone >= 1 ? 'Your professor evaluation requirement is complete.' : 'Complete the required professor evaluations to unlock Clearance.',
'reason' => '',
],
[
'title' => 'Clearance', 'icon' => 'fa-file-signature', 'url' => 'clearance',
'done' => $stepsDone >= 2,
'status' => $stepsDone >= 2 ? 'Approved' : ($stepsDone < 1 ? 'Locked' : ($clearanceStatus === 'PENDING' ? 'Pending Review' : ($clearanceStatus === 'REJECTED' ? 'Rejected' : 'To Do'))),
'detail' => $stepsDone >= 2 ? 'Your signed Clearance has been approved by the Registrar.' : ($clearanceStatus === 'PENDING' && $stepsDone >= 1 ? 'Your Clearance is awaiting Registrar review.' : ($clearanceStatus === 'REJECTED' && $stepsDone >= 1 ? 'Please correct and resubmit your Clearance.' : 'Upload your signed Clearance after completing Evaluation.')),
'reason' => $stepsDone < 2 && $clearanceStatus === 'REJECTED' ? (string) ($clearance['rejection_reason'] ?? '') : '',
],
[
'title' => 'Certificate of Registration (COR)', 'icon' => 'fa-file-lines', 'url' => 'cor',
'done' => $stepsDone >= 4,
'status' => $stepsDone >= 4 ? 'Approved' : ($stepsDone < 2 ? 'Locked' : ($corStatus === 'PENDING' ? 'Pending Review' : ($corStatus === 'REJECTED' ? 'Rejected' : 'To Do'))),
'detail' => $stepsDone >= 4 ? 'Your signed COR has been approved by the Registrar.' : ($stepsDone < 2 ? 'COR unlocks after Clearance approval.' : ($corStatus === 'PENDING' ? 'Your COR is awaiting Registrar review.' : ($corStatus === 'REJECTED' ? 'Please correct and resubmit your COR.' : 'Download, sign, and upload your COR for approval.'))),
'reason' => $stepsDone < 4 && $corStatus === 'REJECTED' ? (string) ($cor['rejection_reason'] ?? '') : '',
],
[
'title' => 'Enrollment Completed', 'icon' => 'fa-graduation-cap', 'url' => 'my_enrollment',
'done' => $stepsDone === 4,
'status' => $stepsDone === 4 ? 'Completed' : 'Not Yet',
'detail' => $stepsDone === 4 ? 'You have successfully completed all enrollment requirements.' : 'Your status becomes Enrolled after the Registrar approves your COR.',
'reason' => '',
],
];
$currentPage = 'myenrollment';
$nextUrl = $stepsDone === 0 ? 'faculty_evaluation' : ($stepsDone === 1 ? 'clearance' : ($stepsDone < 4 ? 'cor' : ''));
$nextText = $stepsDone === 0 ? 'Go to Evaluation' : ($stepsDone === 1 ? 'View Clearance' : ($stepsDone < 4 ? 'View COR' : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMU | My Enrollment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="layout">
    <div class="imglogo"><span>CMU</span></div>
    <?php require FRONTEND_PATH . 'includes/sidebar.php'; ?>
    <div class="overlay" id="overlay"></div>
    <div class="main">
        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>
        <div class="content myen-page">
            <header class="page-heading">
                <h1>My Enrollment</h1>
                <p>Track your enrollment progress and review the status of each requirement.</p>
            </header>

            <section class="myen-overview">
                <div class="myen-overview-top">
                    <div>
                        <span class="myen-eyebrow">CURRENT ENROLLMENT STATUS</span>
                        <h2><?= e($phaseLabel) ?></h2>
                        <p><?= $stepsDone === 4 ? 'All enrollment requirements have been completed.' : 'Follow the steps below to complete your enrollment.' ?></p>
                    </div>
                    <span class="myen-pill <?= $stepsDone === 4 ? 'is-complete' : 'is-active' ?>">
                        <i class="fa-solid <?= $stepsDone === 4 ? 'fa-circle-check' : 'fa-clock' ?>"></i>
                        <?= $stepsDone === 4 ? 'Enrolled' : 'In Progress' ?>
                    </span>
                </div>
                <div class="myen-progress-heading"><span>Overall Progress</span><strong><?= $percent ?>%</strong></div>
                <div class="myen-progress-track" role="progressbar" aria-label="Enrollment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
                    <div class="myen-progress-fill" style="width: <?= $percent ?>%"></div>
                </div>
                <div class="myen-progress-foot"><span><?= $stepsDone ?> of 4 stages completed</span><span><?= e(($settings['semester'] ?? '') ?: 'Semester not set') ?> · A.Y. <?= e(($settings['school_year'] ?? '') ?: 'Not set') ?></span></div>
            </section>

            <div class="myen-info-grid">
                <section class="myen-card">
                    <h2><i class="fa-regular fa-id-card"></i> Student Information</h2>
                    <div class="myen-details">
                        <div><small>Student Name</small><strong><?= e($userName) ?></strong></div>
                        <div><small>Student Number</small><strong><?= e((string) ($student['student_number'] ?? 'Not set')) ?></strong></div>
                    </div>
                </section>
                <section class="myen-card">
                    <h2><i class="fa-regular fa-calendar"></i> Enrollment Information</h2>
                    <div class="myen-details">
                        <div><small>Semester</small><strong><?= e(($settings['semester'] ?? '') ?: 'Not set') ?></strong></div>
                        <div><small>School Year</small><strong><?= e(($settings['school_year'] ?? '') ?: 'Not set') ?></strong></div>
                        <div><small>Enrollment Period</small><strong><?= e($period) ?></strong></div>
                        <div><small>Period Status</small><strong><?= strtoupper((string) ($settings['enrollment_status'] ?? 'CLOSED')) === 'OPEN' ? 'Open' : 'Closed' ?></strong></div>
                    </div>
                </section>
            </div>

            <section class="myen-card">
                <div class="myen-section-head">
                    <div><h2><i class="fa-solid fa-list-check"></i> Enrollment Timeline</h2><p>Progress is updated automatically as your requirements are completed and reviewed.</p></div>
                    <span class="myen-counter"><?= $stepsDone ?>/4 Completed</span>
                </div>
                <div class="myen-timeline">
                    <?php foreach ($stages as $index => $stage): ?>
                        <?php $stateClass = $stage['done'] ? 'is-done' : (in_array($stage['status'], ['Rejected'], true) ? 'is-rejected' : (in_array($stage['status'], ['Pending Review', 'To Do'], true) ? 'is-current' : 'is-waiting')); ?>
                        <article class="myen-stage <?= $stateClass ?>">
                            <div class="myen-stage-marker"><i class="fa-solid <?= $stage['done'] ? 'fa-check' : $stage['icon'] ?>"></i></div>
                            <div class="myen-stage-content">
                                <div class="myen-stage-top"><h3><?= e($stage['title']) ?></h3><span class="myen-stage-status"><?= e($stage['status']) ?></span></div>
                                <p><?= e($stage['detail']) ?></p>
                                <?php if ($stage['reason'] !== ''): ?><div class="myen-reason"><strong>Rejection reason:</strong> <?= e($stage['reason']) ?></div><?php endif; ?>
                                <?php if ($index < 3 && !$stage['done'] && $stage['status'] !== 'Locked'): ?>
                                    <a class="myen-stage-link" href="<?= BASE_URL ?>?page=<?= urlencode($stage['url']) ?>">View <?= e($stage['title']) ?> <i class="fa-solid fa-arrow-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="myen-card" style="margin-top: 20px;">
                <div class="myen-section-head">
                    <div>
                        <h2><i class="fa-solid fa-clock-rotate-left"></i> Enrollment History</h2>
                        <p>View your approved Certificates of Registration from previous semesters.</p>
                    </div>
                    <span class="myen-counter"><?= count($history) ?> Record<?= count($history) === 1 ? '' : 's' ?></span>
                </div>
                <?php if (empty($history)): ?>
                    <p style="padding: 14px 0; color: #64748b;">No approved COR documents from previous enrollment periods yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b;">
                                    <th style="padding: 12px 10px;">Semester</th>
                                    <th style="padding: 12px 10px;">School Year</th>
                                    <th style="padding: 12px 10px;">Status</th>
                                    <th style="padding: 12px 10px;">Approved On</th>
                                    <th style="padding: 12px 10px;">Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $record): ?>
                                    <tr style="border-bottom: 1px solid #edf2f7;">
                                        <td style="padding: 13px 10px;"><?= e($record['semester']) ?></td>
                                        <td style="padding: 13px 10px;"><?= e($record['school_year']) ?></td>
                                        <td style="padding: 13px 10px; color: #15803d; font-weight: 700;">Approved</td>
                                        <td style="padding: 13px 10px;"><?= e($record['reviewed_at'] ? date('M j, Y', strtotime($record['reviewed_at'])) : 'Not recorded') ?></td>
                                        <td style="padding: 13px 10px; white-space: nowrap;">
                                            <a href="<?= e(BASE_URL) ?>backend/api/student_view_cor.php?id=<?= (int) $record['cor_submission_id'] ?>" target="_blank" rel="noopener noreferrer" style="color: #2563eb; font-weight: 700; text-decoration: none; margin-right: 12px;"><i class="fa-regular fa-eye"></i> View</a>
                                            <a href="<?= e(BASE_URL) ?>backend/api/student_view_cor.php?id=<?= (int) $record['cor_submission_id'] ?>&amp;download=1" style="color: #2563eb; font-weight: 700; text-decoration: none;"><i class="fa-solid fa-download"></i> Download</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($nextUrl !== ''): ?>
                <div class="myen-next"><div><strong>Next Step</strong><p><?= $stepsDone === 0 ? 'Begin your professor evaluation.' : ($stepsDone === 1 ? 'Complete or check your Clearance submission.' : 'Complete or check your COR submission.') ?></p></div><a href="<?= BASE_URL ?>?page=<?= $nextUrl ?>"><?= e($nextText) ?> <i class="fa-solid fa-arrow-right"></i></a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>
</body>
</html>
