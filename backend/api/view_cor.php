<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

$submissionId = (int) ($_GET['id'] ?? 0);

if ($submissionId <= 0) {
    http_response_code(400);
    exit('Invalid COR submission.');
}

$stmt = $conn->prepare(
    'SELECT
        file_name,
        file_path
     FROM cor_submissions
     WHERE cor_submission_id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $submissionId);
$stmt->execute();

$submission = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$submission) {
    http_response_code(404);
    exit('COR submission not found.');
}

$filePath =
    dirname(__DIR__, 2) . '/' . $submission['file_path'];

if (!is_file($filePath)) {
    http_response_code(404);
    exit('COR file not found.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mimeType = $finfo->file($filePath);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'application/pdf'
];

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(415);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mimeType);

header(
    'Content-Length: ' . filesize($filePath)
);

header(
    'Content-Disposition: inline; filename="' .
    basename($submission['file_name']) .
    '"'
);

readfile($filePath);

exit;