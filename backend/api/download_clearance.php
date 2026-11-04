<?php
/** Dynamic, original student clearance PDF. No third-party PHP library required. */
require_once dirname(__DIR__) . '/bootstrap.php';
require_role('STUDENT');
require_once dirname(__DIR__) . '/models/student.php';

$studentId = (int) ($_SESSION['student_id'] ?? 0);
if ($studentId < 1) { http_response_code(403); exit('Student session required.'); }
$student = student_find_by_id($conn, $studentId);
if (!$student) { http_response_code(404); exit('Student not found.'); }

$stmt = $conn->prepare('SELECT semester, school_year FROM system_settings ORDER BY setting_id DESC LIMIT 1');
$stmt->execute();
$period = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$period || !$period['semester'] || !$period['school_year']) {
    http_response_code(409); exit('Current enrollment period is not configured.');
}

// Server-side authorization: the PDF URL must not bypass the enrollment workflow.
$phase = (string) ($student['enrollment_phase'] ?? 'NOT_STARTED');
if (!in_array($phase, ['EVALUATION', 'CLEARANCE', 'COR', 'ENROLLED'], true)) {
    http_response_code(403);
    exit('Complete professor evaluation before downloading Clearance.');
}
$statusStmt = $conn->prepare('SELECT enrollment_status FROM system_settings WHERE setting_id = 1 LIMIT 1');
$statusStmt->execute();
$statusRow = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();
if (($statusRow['enrollment_status'] ?? '') !== 'OPEN') {
    http_response_code(403);
    exit('Enrollment is currently closed.');
}

// These offices are provisional, based on the user-supplied sample form.
$offices = [
    'Computer Laboratory / MIS',
    'Health Services Office',
    'University Library',
    'Program Chair',
    'College Dean',
    'National Service Training Program Office',
    "Registrar's Office",
    'Student Values Formation Unit',
    'Student Organization and Activity Unit',
    'Student Affairs and Services Office',
];

// Minimal, self-contained one-page PDF writer (standard Helvetica fonts).
function pdfEscape(string $s): string {
    $s = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', ' ', ' '], $s === false ? '' : $s);
}
function txt(float $x, float $y, string $s, int $size = 10, bool $bold = false): string {
    return sprintf('BT /%s %d Tf %.2f %.2f Td (%s) Tj ET' . "\n", $bold ? 'F2' : 'F1', $size, $x, $y, pdfEscape($s));
}
function linePdf(float $x1, float $y1, float $x2, float $y2): string {
    return sprintf('%.2f %.2f m %.2f %.2f l S' . "\n", $x1, $y1, $x2, $y2);
}
function rectangle(float $x, float $y, float $w, float $h): string {
    return sprintf('%.2f %.2f %.2f %.2f re S' . "\n", $x, $y, $w, $h);
}
function clipText(string $s, int $limit): string {
    return mb_strlen($s, 'UTF-8') > $limit ? mb_substr($s, 0, $limit - 3, 'UTF-8') . '...' : $s;
}

$name = trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$schoolYear = $period['school_year'];
$semester = $period['semester'];
$program = (string) ($student['program'] ?? '');
$year = (string) ($student['year_level'] ?? '');
$yearLevel = ctype_digit($year) ? $year . ($year === '1' ? 'st' : ($year === '2' ? 'nd' : ($year === '3' ? 'rd' : 'th'))) . ' Year' : $year;

$p = "0.4 w\n";
$p .= txt(183, 756, 'STUDENT CLEARANCE FORM', 16, true);
$p .= txt(204, 735, 'Enrollment Clearance Document', 10);
$p .= linePdf(42, 720, 570, 720);
$p .= txt(44, 696, 'STUDENT INFORMATION', 11, true);
$p .= txt(44, 671, 'Student Name:', 9, true) . txt(145, 671, clipText($name, 49), 10);
$p .= txt(44, 650, 'Student Number:', 9, true) . txt(145, 650, (string) ($student['student_number'] ?? ''), 10);
$p .= txt(44, 629, 'Program:', 9, true) . txt(145, 629, clipText($program, 53), 10);
$p .= txt(44, 608, 'Year and Section:', 9, true) . txt(145, 608, clipText($yearLevel . ' / ' . ($student['section'] ?? ''), 52), 10);
$p .= txt(44, 587, 'Enrollment Period:', 9, true) . txt(145, 587, $semester . ', A.Y. ' . $schoolYear, 10);
$p .= linePdf(42, 573, 570, 573);
$p .= txt(44, 552, 'PURPOSE OF CLEARANCE (check one)', 10, true);
foreach (['Enrolling', 'Graduating', 'Transferring', 'Others'] as $i => $label) {
    $x = 45 + ($i % 2) * 260;
    $y = 532 - intdiv($i, 2) * 21;
    $p .= rectangle($x, $y - 1, 10, 10) . txt($x + 17, $y + 1, $label, 9);
}
$p .= txt(44, 469, 'REQUIRED CLEARANCE SIGNATURES', 11, true);
$p .= txt(44, 453, 'Obtain the signature and date from each required office.', 8);

$top = 438;
$bottom = 438 - 11 * 29;
$p .= rectangle(42, $bottom, 528, $top - $bottom);
foreach ([73, 291, 428, 500] as $x) { $p .= linePdf($x, $bottom, $x, $top); }
for ($i = 1; $i <= 10; $i++) { $p .= linePdf(42, $top - $i * 29, 570, $top - $i * 29); }
$p .= txt(50, 420, 'No.', 8, true) . txt(82, 420, 'Department / Office', 8, true);
$p .= txt(300, 420, 'Authorized Signatory', 8, true) . txt(439, 420, 'Signature', 8, true) . txt(510, 420, 'Date', 8, true);
foreach ($offices as $i => $office) {
    $y = $top - ($i + 1) * 29 - 18;
    $p .= txt(53, $y, (string) ($i + 1), 9);
    $office = clipText($office, 40);
    // Wrap long office names across two lines without truncating essential labels.
    if (strlen($office) > 32 && str_contains($office, ' ')) {
        $split = strrpos(substr($office, 0, 31), ' ');
        $p .= txt(81, $y + 5, substr($office, 0, $split), 8);
        $p .= txt(81, $y - 6, substr($office, $split + 1), 8);
    } else {
        $p .= txt(81, $y, $office, 8);
    }
}
$p .= txt(44, 81, 'Student Signature: _______________________________', 9);
$p .= txt(367, 81, 'Date: __________________', 9);
$p .= txt(44, 58, 'This is a project-generated clearance form. Signatures are completed manually.', 8);

$stream = $p;
$objects = [
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
    '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
    '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream",
];
$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $i => $obj) {
    $offsets[] = strlen($pdf);
    $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
}
$xref = strlen($pdf);
$pdf .= "xref\n0 7\n0000000000 65535 f \n";
foreach ($offsets as $i => $offset) {
    if ($i === 0) continue;
    $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
}
$pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
$filename = 'Clearance_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $student['student_number']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
exit;
