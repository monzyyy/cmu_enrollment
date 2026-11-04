<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('STUDENT');


header('Content-Type: application/json');


$studentId = $_SESSION['student_id'] ?? null;


if (!$studentId) {

    echo json_encode([
        'success' => false,
        'message' => 'Student account could not be identified.'
    ]);

    exit;
}




// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

// Check the current enrollment period.
$stmt = $conn->prepare(
    'SELECT enrollment_status, semester, school_year
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);

$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$settings || $settings['enrollment_status'] !== 'OPEN') {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'The enrollment period is closed.'
    ]);
    exit;
}

// Find the current period record. Never assign a submission to an unknown period.
$stmt = $conn->prepare(
    'SELECT period_id FROM enrollment_periods
     WHERE semester = ? AND school_year = ? LIMIT 1'
);
$stmt->bind_param('ss', $settings['semester'], $settings['school_year']);
$stmt->execute();
$periodRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$periodRow) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'The current enrollment period is not registered. Contact the Registrar.'
    ]);
    exit;
}
$currentPeriodId = (int) $periodRow['period_id'];

// Verify the student's enrollment phase.
$stmt = $conn->prepare(
    'SELECT enrollment_phase
     FROM students
     WHERE student_id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $studentId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$student ||
    $student['enrollment_phase'] !== 'CLEARANCE'
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'COR is locked. Complete Evaluation and Clearance approval first.'
    ]);
    exit;
}

// Verify approved Clearance for the active semester.
$stmt = $conn->prepare(
    'SELECT clearance_id
     FROM clearance_submissions
     WHERE student_id = ?
       AND semester = ?
       AND school_year = ?
       AND status = "APPROVED"
     LIMIT 1'
);

$stmt->bind_param(
    'iss',
    $studentId,
    $settings['semester'],
    $settings['school_year']
);

$stmt->execute();

$approvedClearance = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$approvedClearance) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'An approved Clearance is required before submitting COR.'
    ]);
    exit;
}

/*
 * Make sure the request contains a file.
 */

if (
    !isset($_FILES['cor_file']) ||
    $_FILES['cor_file']['error'] !== UPLOAD_ERR_OK
) {

    echo json_encode([
        'success' => false,
        'message' => 'Please select a COR file to upload.'
    ]);

    exit;
}


$file = $_FILES['cor_file'];


/*
 * Maximum file size: 10 MB
 */

$maxFileSize = 10 * 1024 * 1024;


if ($file['size'] > $maxFileSize) {

    echo json_encode([
        'success' => false,
        'message' => 'The file must not exceed 10 MB.'
    ]);

    exit;
}


/*
 * Allowed file types.
 */

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'application/pdf'
];


$finfo = new finfo(FILEINFO_MIME_TYPE);

$fileMimeType = $finfo->file($file['tmp_name']);


if (!in_array($fileMimeType, $allowedMimeTypes, true)) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Please upload a JPG, PNG, or PDF.'
    ]);

    exit;
}


/*
 * Check if the student already has
 * a pending COR submission.
 */

$stmt = $conn->prepare(
    'SELECT cor_submission_id
     FROM cor_submissions
     WHERE student_id = ?
       AND period_id = ?
       AND status IN ("PENDING", "APPROVED")
     LIMIT 1'
);

$stmt->bind_param('ii', $studentId, $currentPeriodId);

$stmt->execute();

$existingSubmission = $stmt->get_result()->fetch_assoc();

$stmt->close();


if ($existingSubmission) {

    echo json_encode([
        'success' => false,
        'message' => 'You already have a COR submission pending review.'
    ]);

    exit;
}


/*
 * Create upload directory.
 */

$uploadDirectory = dirname(__DIR__, 2) . '/uploads/cor/';


if (!is_dir($uploadDirectory)) {

    if (!mkdir($uploadDirectory, 0755, true)) {

        echo json_encode([
            'success' => false,
            'message' => 'Unable to create the upload directory.'
        ]);

        exit;
    }
}


/*
 * Get the original file extension.
 */

$originalExtension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);


/*
 * Generate a unique file name.
 */

$uniqueFileName =
    'COR_' .
    $studentId .
    '_' .
    time() .
    '_' .
    bin2hex(random_bytes(5)) .
    '.' .
    $originalExtension;


$filePath = $uploadDirectory . $uniqueFileName;


/*
 * Move uploaded file.
 */

if (!move_uploaded_file($file['tmp_name'], $filePath)) {

    echo json_encode([
        'success' => false,
        'message' => 'The COR file could not be uploaded.'
    ]);

    exit;
}


/*
 * Path stored in the database.
 */

$databaseFilePath =
    'uploads/cor/' . $uniqueFileName;


/*
 * Insert submission into database.
 */

$stmt = $conn->prepare(
    'INSERT INTO cor_submissions (
        student_id,
        period_id,
        file_name,
        file_path,
        submitted_at,
        status
     )
     VALUES (?, ?, ?, ?, NOW(), "PENDING")'
);


$stmt->bind_param(
    'iiss',
    $studentId,
    $currentPeriodId,
    $file['name'],
    $databaseFilePath
);


if (!$stmt->execute()) {

    $stmt->close();

    /*
     * Remove the uploaded file if
     * the database insertion fails.
     */

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    echo json_encode([
        'success' => false,
        'message' => 'The COR was uploaded but could not be recorded.'
    ]);

    exit;
}


$stmt->close();


echo json_encode([
    'success' => true,
    'message' => 'Your COR has been submitted successfully and is now pending review.'
]);