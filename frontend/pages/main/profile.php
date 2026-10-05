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
   PROFILE DATA
   TODO: replace the right-hand keys with your real column
   names. Missing keys fall back to an empty value (shown as –).
--------------------------------------------------------- */

$birthDate = $student['birth_date'] ?? '';
$age       = '';
$birthText = '';

if ($birthDate) {
    try {
        $dob       = new DateTime($birthDate);
        $age       = $dob->diff(new DateTime('today'))->y;
        $birthText = $dob->format('M j, Y');
    } catch (Exception $ex) {
        $birthText = $birthDate;
    }
}

$photoUrl = !empty($student['photo'])
    ? BASE_URL . 'uploads/students/' . rawurlencode($student['photo'])
    : '';

$personalLeft = [
    'Student No.'   => $student['student_number'] ?? '',
    'Lastname'      => $student['last_name']      ?? '',
    'First Name'    => $student['first_name']     ?? '',
    'Middle Name'   => $student['middle_name']    ?? '',
    'Age'           => $age,
    'Date of Birth' => $birthText,
    'Sex'           => $student['sex']            ?? '',
];

$personalRight = [
    'Civil Status'   => $student['civil_status']   ?? '',
    'Citizenship'    => $student['citizenship']    ?? '',
    'Religion'       => $student['religion']       ?? '',
    'Place of Birth' => $student['place_of_birth'] ?? '',
    'Residence No.'  => $student['residence_no']   ?? '',
    'Cellphone No.'  => $student['cellphone_no']   ?? '',
    'Email Address'  => $student['email']          ?? '',
];

$address = [
    'House No. / Street'  => $student['house_street'] ?? '',
    'Barangay'            => $student['barangay']     ?? '',
    'District'            => $student['district']     ?? '',
    'Zip Code'            => $student['zip_code']     ?? '',
    'City / Municipality' => $student['city']         ?? '',
    'Province'            => $student['province']     ?? '',
];

$family = [
    "Mother's Name" => $student['mother_name']     ?? '',
    'Contact No.'   => $student['mother_contact']  ?? '',
    "Father's Name" => $student['father_name']     ?? '',
    ' Contact No.'  => $student['father_contact']  ?? '',
    'Guardian'      => $student['guardian_name']   ?? '',
    '  Contact No.' => $student['guardian_contact'] ?? '',
];

$education = [
    'Primary Education' => [
        'School Name'     => $student['primary_school']       ?? '',
        'Year Graduated'  => $student['primary_year']         ?? '',
    ],
    'Lower Secondary Education' => [
        'School Name'     => $student['lower_secondary_school'] ?? '',
        'Year Graduated'  => $student['lower_secondary_year']   ?? '',
    ],
    'Upper Secondary Education' => [
        'School Name'     => $student['upper_secondary_school'] ?? '',
        'Year Graduated'  => $student['upper_secondary_year']   ?? '',
    ],
];

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
            <section class="pf-top">

                <div class="page-heading">
                    <h1>Personal Information</h1>
                    <p>View your personal and academic information registered with the University.</p>
                </div>

                <div class="pf-notice">
                    <span class="co-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <p>
                        This information is based on your application record.
                        To request changes, contact the registrar's office.
                    </p>
                </div>

            </section>


            <!-- =========================
                 PERSONAL BACKGROUND
            ========================== -->
            <section class="pf-card">

                <div class="pf-card-head">
                    <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                    <h2>Personal Background</h2>
                </div>

                <div class="pf-personal">

                    <!-- PHOTO -->
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

                        <input type="file"
                               id="photoInput"
                               name="photo"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                               hidden>

                    </form>

                    <!-- FIELDS -->
                    <div class="pf-fields">
                        <?php foreach ($personalLeft as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>

                    <div class="pf-fields">
                        <?php foreach ($personalRight as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>

                </div>

            </section>


            <!-- =========================
                 ADDRESS + FAMILY
            ========================== -->
            <section class="pf-pair">

                <div class="pf-card">

                    <div class="pf-card-head">
                        <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                        <h2>Address Information</h2>
                    </div>

                    <div class="pf-fields is-wide">
                        <?php foreach ($address as $label => $value): pf_field($label, $value); endforeach; ?>
                    </div>

                </div>


                <div class="pf-card">

                    <div class="pf-card-head">
                        <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                        <h2>Family Background</h2>
                    </div>

                    <div class="pf-fields is-wide">
                        <?php foreach ($family as $label => $value): pf_field(trim($label), $value); endforeach; ?>
                    </div>

                </div>

            </section>


            <!-- =========================
                 EDUCATIONAL BACKGROUND
            ========================== -->
            <section class="pf-card">

                <div class="pf-card-head">
                    <span class="co-icon"><i class="fa-regular fa-user"></i></span>
                    <h2>Educational Background</h2>
                </div>

                <div class="pf-education">

                    <?php foreach ($education as $level => $fields): ?>

                        <div class="pf-edu-block">

                            <h3><?= e($level) ?></h3>

                            <div class="pf-fields">
                                <?php foreach ($fields as $label => $value): pf_field($label, $value); endforeach; ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

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