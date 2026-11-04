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

</body>
</html>