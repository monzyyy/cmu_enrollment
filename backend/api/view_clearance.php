<?php
 
require_once dirname(__DIR__) . '/bootstrap.php';
require_role('ADMIN');

function clearancePreviewError(int $status, string $message): void {
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit($message);
}

$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$submissionId || $submissionId <= 0) {
    clearancePreviewError(400, 'Invalid Clearance submission ID.');
}

$stmt = $conn->prepare('SELECT original_filename, file_path FROM clearance_submissions WHERE clearance_id = ? LIMIT 1');
$stmt->bind_param('i', $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$submission) {
    clearancePreviewError(404, 'Clearance submission not found.');
}

 
 
$primaryDirectory = dirname(__DIR__, 4) . '/cmu_private_uploads/clearance/';
$projectRoot = dirname(__DIR__, 2);
$legacyDirectories = [
    dirname($projectRoot) . '/cmu_private_uploads/clearance/',
    $projectRoot . '/uploads/clearance/'
];
$storedPath = str_replace('\\', '/', (string) $submission['file_path']);
$fileName = basename($storedPath);
if ($fileName === '' || $fileName === '.' || $fileName === '..') {
    clearancePreviewError(404, 'Invalid stored Clearance filename.');
}

$filePath = null;
foreach (array_unique(array_merge([$primaryDirectory], $legacyDirectories)) as $directory) {
    $candidate = $directory . $fileName;
    if (is_file($candidate) && is_readable($candidate)) {
        $filePath = $candidate;
        break;
    }
}
if ($filePath === null) {
    error_log('Clearance preview missing file for submission ' . $submissionId . ' (stored: ' . $storedPath . ')');
    clearancePreviewError(404, 'The uploaded Clearance file could not be found.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);
$allowed = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mimeType, $allowed, true)) {
    clearancePreviewError(415, 'This Clearance file is not a supported PDF, JPG, or PNG.');
}

 
while (ob_get_level() > 0) {
    ob_end_clean();
}
$displayName = basename(str_replace('\\', '/', (string) $submission['original_filename']));
$displayName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $displayName);
if ($displayName === '') {
    $displayName = 'clearance.' . ($mimeType === 'application/pdf' ? 'pdf' : ($mimeType === 'image/png' ? 'png' : 'jpg'));
}
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $displayName . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
readfile($filePath);
exit;
