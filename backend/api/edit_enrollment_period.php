<?php

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}

require_login();

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'ADMIN'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'You are not authorized to perform this action.'
    ]);

    exit;
}


$semester = trim($_POST['semester'] ?? '');
$schoolYear = trim($_POST['school_year'] ?? '');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');


$errors = [];


if (!in_array(
    $semester,
    ['1st Semester', '2nd Semester', 'Summer'],
    true
)) {

    $errors['semester'] = 'Please select a valid semester.';
}


if (!preg_match(
    '/^\d{4}\s*-\s*\d{4}$/',
    $schoolYear
)) {

    $errors['school_year'] =
        'Please enter a valid school year.';
}


if ($startDate === '') {

    $errors['start_date'] =
        'Please enter a start date.';
}


if ($endDate === '') {

    $errors['end_date'] =
        'Please enter an end date.';
}


if (
    $startDate !== '' &&
    $endDate !== '' &&
    $startDate > $endDate
) {

    $errors['end_date'] =
        'End date cannot be earlier than the start date.';
}


if ($errors) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please correct the highlighted information.',
        'errors' => $errors
    ]);

    exit;
}


try {

    $stmt = $conn->prepare(
        'UPDATE system_settings
         SET
            semester = ?,
            school_year = ?,
            start_date = ?,
            end_date = ?
         WHERE setting_id = 1'
    );

    $stmt->bind_param(
        'ssss',
        $semester,
        $schoolYear,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $stmt->close();


    echo json_encode([
        'success' => true,
        'message' => 'Enrollment period has been updated successfully.'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}