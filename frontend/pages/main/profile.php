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

$currentPage = 'profile';
if (empty($_SESSION['profile_password_csrf'])) {
    $_SESSION['profile_password_csrf'] = bin2hex(random_bytes(32));
}


/* ---------------------------------------------------------
   HELPERS
--------------------------------------------------------- */

// Show a dash for empty values.
function pf_value($value)
{
    $value = trim((string) $value);
    return $value === '' ? '–' : $value;
}

// One read-only label + value row.
function pf_field($label, $value)
{
    $text = pf_value($value);

    echo '<div class="pf-field">';
    echo '<span class="pf-label">' . e($label) . '</span>';
    echo '<div class="pf-value" title="' . e($text) . '">' . e($text) . '</div>';
    echo '</div>';
}

/* ---------------------------------------------------------
   REGISTRAR-MANAGED STUDENT PROFILE
--------------------------------------------------------- */
$photoUrl = !empty($student['photo'])
    ? BASE_URL . 'uploads/students/' . rawurlencode($student['photo'])
    : '';

$personalLeft = [
    'First Name'   => $student['first_name'] ?? '',
    'Middle Name'  => $student['middle_name'] ?? '',
    'Last Name'    => $student['last_name'] ?? '',
];

$personalRight = [
    'Email Address'  => $student['email'] ?? '',
    'Contact Number' => $student['phone'] ?? ($student['cellphone_no'] ?? ''),
];

$academicLeft = [
    'Student Number' => $student['student_number'] ?? '',
    'Program'        => $student['program_name'] ?? ($student['program'] ?? ''),
];

$academicRight = [
    'Year Level' => $student['year_level'] ?? '',
    'Section'    => $student['section_name'] ?? ($student['section'] ?? ''),
];

$accountStatus = $student['account_status'] ?? ($student['status'] ?? '');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Personal Information</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* Profile-specific two-column academic information layout. */
.pf-profile-academic { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 12px 14px 16px; }
.pf-profile-academic .pf-fields { min-width: 0; }
@media (max-width: 760px) { .pf-profile-academic { grid-template-columns: 1fr; } }

.pf-security { padding: 18px; }
.pf-security-form { max-width: 520px; display: grid; gap: 14px; }
.pf-security-form label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
.pf-security-form input { width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 8px; padding: 11px 12px; font: inherit; background: #fff; color: #111827; }
.pf-security-form input:focus { outline: 2px solid #2563eb; outline-offset: 1px; }
.pf-security-form button { justify-self: start; border: 0; border-radius: 8px; background: #1d4ed8; color: #fff; padding: 11px 18px; font: inherit; font-weight: 700; cursor: pointer; }
.pf-security-form button:disabled { opacity: .6; cursor: wait; }
.pf-security-help { font-size: 12px; color: #64748b; margin: 0; }
.pf-security-message { font-size: 13px; margin: 0; }
.pf-security-message.success { color: #166534; }
.pf-security-message.error { color: #b91c1c; }
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

            <section class="pf-top">
                <div class="page-heading">
                    <h1>My Profile</h1>
                    <p>View your registered personal and academic information.</p>
                </div>
                <div class="pf-notice">
                    <span class="co-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <p>Your personal and academic information is maintained by the Registrar's Office. To request corrections, please contact the Registrar.</p>
                </div>
            </section>

            <section class="pf-card">
                <div class="pf-card-head">
                    <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                    <h2>Personal Information</h2>
                </div>
                <div class="pf-personal">
                    <!-- Keep the existing photo upload feature. -->
                    <form class="pf-photo"
                          action="<?= BASE_URL ?>backend/api/update_photo.php"
                          method="POST"
                          enctype="multipart/form-data"
                          id="photoForm">
                        <?php if ($photoUrl): ?>
                            <img src="<?= e($photoUrl) ?>" alt="Photo of <?= e($userName) ?>">
                        <?php else: ?>
                            <div class="pf-photo-empty" aria-hidden="true">
                                <i class="fa-regular fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <label class="pf-photo-button" for="photoInput" title="Change photo">
                            <i class="fa-solid fa-camera"></i>
                            <span class="sr-only">Change photo</span>
                        </label>
                        <input type="file" id="photoInput" name="photo"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden>
                    </form>
                    <div class="pf-fields">
                        <?php foreach ($personalLeft as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>
                    <div class="pf-fields">
                        <?php foreach ($personalRight as $label => $value): pf_field($label, $value); endforeach; ?>
                        <?php if ($accountStatus !== ''): pf_field('Account Status', $accountStatus); endif; ?>
                    </div>
                </div>
            </section>

            <section class="pf-card" style="margin-top: 14px;">
                <div class="pf-card-head">
                    <span class="co-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                    <h2>Academic Information</h2>
                </div>
                <div class="pf-profile-academic">
                    <div class="pf-fields">
                        <?php foreach ($academicLeft as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>
                    <div class="pf-fields">
                        <?php foreach ($academicRight as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="pf-card" style="margin-top: 14px;">
                <div class="pf-card-head">
                    <span class="co-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <h2>Account Security</h2>
                </div>
                <div class="pf-security">
                    <form id="changePasswordForm" class="pf-security-form" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['profile_password_csrf']) ?>">
                        <div>
                            <label for="currentPassword">Current Password</label>
                            <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div>
                            <label for="newPassword">New Password</label>
                            <input id="newPassword" type="password" name="new_password" autocomplete="new-password" minlength="8" maxlength="72" required>
                            <p class="pf-security-help">Use 8–72 characters, including at least one letter and one number.</p>
                        </div>
                        <div>
                            <label for="confirmPassword">Confirm New Password</label>
                            <input id="confirmPassword" type="password" name="confirm_password" autocomplete="new-password" required>
                        </div>
                        <p id="changePasswordMessage" class="pf-security-message" role="status" aria-live="polite"></p>
                        <button type="submit" id="changePasswordButton">Update Password</button>
                    </form>
                </div>
            </section>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

<script>
(function () {
    var input = document.getElementById('photoInput');
    var form  = document.getElementById('photoForm');

    if (!input || !form) return;

    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;

        var okType = ['image/jpeg', 'image/png'].indexOf(file.type) !== -1;
        var okSize = file.size <= 5 * 1024 * 1024;

        if (!okType || !okSize) {
            alert('Please choose a JPG or PNG photo that is 5 MB or smaller.');
            input.value = '';
            return;
        }

        form.submit();
    });
})();
</script>
<script>
(function () {
    const form = document.getElementById('changePasswordForm');
    const message = document.getElementById('changePasswordMessage');
    const button = document.getElementById('changePasswordButton');
    if (!form) return;
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        message.textContent = '';
        message.className = 'pf-security-message';
        const data = new FormData(form);
        const current = String(data.get('current_password') || '');
        const next = String(data.get('new_password') || '');
        const confirm = String(data.get('confirm_password') || '');
        if (next.length < 8 || next.length > 72 || !/[A-Za-z]/.test(next) || !/[0-9]/.test(next)) {
            message.textContent = 'Use 8–72 characters with at least one letter and one number.';
            message.classList.add('error'); return;
        }
        if (next !== confirm) {
            message.textContent = 'New passwords do not match.';
            message.classList.add('error'); return;
        }
        if (next === current) {
            message.textContent = 'Choose a different password.';
            message.classList.add('error'); return;
        }
        button.disabled = true;
        try {
            const response = await fetch(<?= json_encode(BASE_URL . 'backend/api/change_student_password.php') ?>, {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            });
            const result = await response.json();
            message.textContent = result.message || 'Unable to update password.';
            message.classList.add(response.ok && result.success ? 'success' : 'error');
            if (response.ok && result.success) form.reset();
        } catch (_) {
            message.textContent = 'Connection error. Please try again.';
            message.classList.add('error');
        } finally { button.disabled = false; }
    });
})();
</script>

</body>
</html>