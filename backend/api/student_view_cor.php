<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_role('STUDENT');

$studentId = (int) ($_SESSION['student_id'] ?? 0);
$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$studentId || !$submissionId || $submissionId < 1) {
    http_response_code(400);
    exit('Invalid request.');
}

 
 
$stmt = $conn->prepare(
    'SELECT cs.file_name, cs.file_path
     FROM cor_submissions cs
     JOIN enrollment_periods ep ON ep.period_id = cs.period_id
     JOIN system_settings ss ON ss.setting_id = 1
     WHERE cs.cor_submission_id = ? AND cs.student_id = ?
       AND cs.status = "APPROVED"
       AND NOT (ep.semester = ss.semester
                AND REPLACE(ep.school_year, " ", "") = REPLACE(ss.school_year, " ", ""))
     LIMIT 1'
);
$stmt->bind_param('ii', $submissionId, $studentId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$record) {
    http_response_code(404);
    exit('Approved historical COR not found.');
}

$root = realpath(dirname(__DIR__, 2) . '/uploads/cor');
$path = realpath(dirname(__DIR__, 2) . '/' . $record['file_path']);
if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit('COR document not found.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
    http_response_code(415);
    exit('Unsupported file type.');
}
$filename = basename($record['file_name']);
$filename = str_replace(["\r", "\n", '"', "\\"], '_', $filename);
$disposition = isset($_GET['download']) && $_GET['download'] === '1' ? 'attachment' : 'inline';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
readfile($path);
exit;
