
<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('STUDENT');

header('Content-Type: application/json');

function clearanceResponse(
    bool $success,
    string $message,
    int $statusCode = 200
): void {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    clearanceResponse(false, 'Invalid request method.', 405);
}

$studentId = (int)($_SESSION['student_id'] ?? 0);

if ($studentId <= 0) {
    clearanceResponse(false, 'Student not identified.', 401);
}

// Require a valid CSRF token created in the form.
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    clearanceResponse(false, 'Invalid security token.', 403);
}

// Get current enrollment period.
$stmt = $conn->prepare("
    SELECT semester, school_year
    FROM system_settings
    WHERE enrollment_status = 'OPEN'
      AND semester IS NOT NULL
      AND school_year IS NOT NULL
    ORDER BY setting_id DESC
    LIMIT 1
");
$stmt->execute();
$period = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$period) {
    clearanceResponse(false, 'Enrollment is not open.', 403);
}

$semester = $period['semester'];
$schoolYear = $period['school_year'];

if (
    !isset($_FILES['clearance_file']) ||
    $_FILES['clearance_file']['error'] !== UPLOAD_ERR_OK
) {
    clearanceResponse(false, 'Please select a clearance file.', 400);
}

$file = $_FILES['clearance_file'];

if (
    !is_uploaded_file($file['tmp_name']) ||
    $file['size'] <= 0 ||
    $file['size'] > 10 * 1024 * 1024
) {
    clearanceResponse(false, 'Invalid file or file exceeds 10 MB.', 400);
}

$allowedTypes = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!isset($allowedTypes[$mime])) {
    clearanceResponse(false, 'Only PDF, JPG, and PNG are allowed.', 400);
}

$extension = $allowedTypes[$mime];

// Store outside the public project directory.
// Configure this directory with restricted permissions.
$uploadDirectory = dirname(__DIR__, 4) . '/cmu_private_uploads/clearance/';

if (
    !is_dir($uploadDirectory) &&
    !mkdir($uploadDirectory, 0700, true)
) {
    clearanceResponse(false, 'Unable to prepare upload directory.', 500);
}

$filename = 'CLR_' . $studentId . '_'
    . bin2hex(random_bytes(16)) . '.' . $extension;

$absolutePath = $uploadDirectory . $filename;

// Store a relative reference, not a public URL.
$storedPath = 'clearance/' . $filename;

$transactionStarted = false;
$fileMoved = false;

try {
    $conn->begin_transaction();
    $transactionStarted = true;

    // Lock student record to prevent concurrent submissions.
    $stmt = $conn->prepare("
        SELECT enrollment_phase
        FROM students
        WHERE student_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$student ||
        $student['enrollment_phase'] !== 'EVALUATION'
    ) {
        throw new RuntimeException(
            'Complete faculty evaluation before submitting clearance.'
        );
    }

    // Check latest submission for the active period.
    $stmt = $conn->prepare("
        SELECT status
        FROM clearance_submissions
        WHERE student_id = ?
          AND semester = ?
          AND school_year = ?
        ORDER BY clearance_id DESC
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param('iss', $studentId, $semester, $schoolYear);
    $stmt->execute();
    $previous = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        $previous &&
        $previous['status'] !== 'REJECTED'
    ) {
        throw new RuntimeException(
            'You already have a pending or approved clearance.'
        );
    }

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('File upload failed.');
    }

    $fileMoved = true;

    $originalName = basename($file['name']);
    $originalName = substr($originalName, 0, 255);

    $stmt = $conn->prepare("
        INSERT INTO clearance_submissions (
            student_id,
            semester,
            school_year,
            file_path,
            original_filename,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'PENDING')
    ");

    $stmt->bind_param(
        'issss',
        $studentId,
        $semester,
        $schoolYear,
        $storedPath,
        $originalName
    );

    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $transactionStarted = false;

    clearanceResponse(
        true,
        'Clearance submitted successfully. Awaiting admin review.'
    );

} catch (Throwable $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }

    if ($fileMoved && is_file($absolutePath)) {
        unlink($absolutePath);
    }

    error_log('Clearance submission: ' . $e->getMessage());

    clearanceResponse(
        false,
        'Unable to submit clearance. Check your enrollment phase, existing submission, and uploaded file.',
        400
    );
}
