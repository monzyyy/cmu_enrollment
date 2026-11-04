<?php
/** Project-generated COR; original layout, current-period course offerings. No PDF dependency. */
require_once dirname(__DIR__) . '/bootstrap.php';
require_role('STUDENT');
require_once dirname(__DIR__) . '/models/student.php';

$studentId = (int) ($_SESSION['student_id'] ?? 0);
if (!$studentId) { http_response_code(403); exit('Student session required.'); }
$student = student_find_by_id($conn, $studentId);
if (!$student) { http_response_code(404); exit('Student not found.'); }

$stmt = $conn->prepare('SELECT semester, school_year, enrollment_status FROM system_settings WHERE setting_id = 1 LIMIT 1');
$stmt->execute(); $period = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$period || !$period['semester'] || !$period['school_year']) { http_response_code(409); exit('Current enrollment period is not configured.'); }

// Server-side phase and approval checks prevent direct-URL bypass.
if (!in_array((string) ($student['enrollment_phase'] ?? 'NOT_STARTED'), ['CLEARANCE', 'COR', 'ENROLLED'], true)) {
    http_response_code(403); exit('Complete the required enrollment steps before downloading COR.');
}
$clearanceStmt = $conn->prepare('SELECT clearance_id FROM clearance_submissions WHERE student_id = ? AND semester = ? AND school_year = ? AND status = "APPROVED" LIMIT 1');
$clearanceStmt->bind_param('iss', $studentId, $period['semester'], $period['school_year']);
$clearanceStmt->execute();
$hasApprovedClearance = (bool) $clearanceStmt->get_result()->fetch_assoc();
$clearanceStmt->close();
if (!$hasApprovedClearance) {
    http_response_code(403); exit('Current-semester Clearance must be approved before downloading COR.');
}
if (($period['enrollment_status'] ?? '') !== 'OPEN') {
    $approvedStmt = $conn->prepare('SELECT cs.cor_submission_id FROM cor_submissions cs JOIN enrollment_periods ep ON ep.period_id = cs.period_id WHERE cs.student_id = ? AND ep.semester = ? AND ep.school_year = ? AND cs.status = "APPROVED" LIMIT 1');
    $approvedStmt->bind_param('iss', $studentId, $period['semester'], $period['school_year']);
    $approvedStmt->execute();
    $hasApprovedCor = (bool) $approvedStmt->get_result()->fetch_assoc();
    $approvedStmt->close();
    if (!$hasApprovedCor) { http_response_code(403); exit('Enrollment is currently closed.'); }
}

// Match the same section/semester/year rules used by the student's My Schedule page.
$stmt = $conn->prepare('SELECT co.offering_id, c.course_code, c.course_name, c.units, co.section, co.instructor_name
 FROM course_offerings co JOIN courses c ON c.course_id = co.course_id
 WHERE co.program = ? AND co.year_level = ? AND co.section = ? AND co.semester = ? AND co.school_year = ?
 ORDER BY c.course_code, co.offering_id');
$program = (string) ($student['program'] ?? '');
$year = (int) ($student['year_level'] ?? 0);
$section = (string) ($student['section'] ?? '');
$stmt->bind_param('sisss', $program, $year, $section, $period['semester'], $period['school_year']);
$stmt->execute(); $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
if (!$courses) { http_response_code(409); exit('No subjects are assigned to your section for the current enrollment period. Please contact the Registrar.'); }
foreach ($courses as &$course) {
    if (trim((string) ($course['instructor_name'] ?? '')) === '') {
        http_response_code(409); exit('A subject has no assigned professor yet. Please contact the Registrar before downloading your COR.');
    }
    $stmt = $conn->prepare('SELECT day_of_week, start_time, end_time, room FROM course_offering_schedules WHERE offering_id = ? ORDER BY schedule_id');
    $offeringId = (int) $course['offering_id']; $stmt->bind_param('i', $offeringId); $stmt->execute();
    $course['schedules'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}
unset($course);

function corPdfEsc(string $s): string {
    $v = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', ' ', ' '], $v === false ? '' : $v);
}
function corPdfText(float $x, float $y, string $s, int $size = 9, bool $bold = false): string {
    return sprintf('BT /%s %d Tf %.2f %.2f Td (%s) Tj ET' . "\n", $bold ? 'F2' : 'F1', $size, $x, $y, corPdfEsc($s));
}
function corPdfLine(float $x1, float $y1, float $x2, float $y2): string {
    return sprintf('%.2f %.2f m %.2f %.2f l S' . "\n", $x1, $y1, $x2, $y2);
}
function corPdfWrap(string $s, int $limit): array {
    $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    if ($s === '') return [''];
    $words = preg_split('/\s+/u', $s) ?: [];
    $lines = []; $line = '';
    foreach ($words as $word) {
        while (mb_strlen($word, 'UTF-8') > $limit) {
            if ($line !== '') { $lines[] = $line; $line = ''; }
            $lines[] = mb_substr($word, 0, $limit, 'UTF-8');
            $word = mb_substr($word, $limit, null, 'UTF-8');
        }
        if ($word === '') continue;
        $test = $line === '' ? $word : $line . ' ' . $word;
        if (mb_strlen($test, 'UTF-8') > $limit && $line !== '') { $lines[] = $line; $line = $word; }
        else $line = $test;
    }
    if ($line !== '') $lines[] = $line;
    return $lines ?: [''];
}
function corPdfCellLines(float $x, float $top, array $lines, int $size = 8): string {
    $out = '';
    foreach ($lines as $i => $line) $out .= corPdfText($x, $top - 13 - $i * 11, $line, $size);
    return $out;
}
function corPdfHeader(string $name, array $student, array $period, int $year, string $section, bool $continued): string {
    $p = "0.5 w\n";
    $p .= corPdfText(267, 560, 'CERTIFICATE OF REGISTRATION', 15, true);
    $p .= corPdfText(320, 540, $period['semester'] . ' | A.Y. ' . $period['school_year'], 10);
    if ($continued) $p .= corPdfText(747, 540, 'Continued', 8);
    $p .= corPdfLine(35, 525, 807, 525);
    $p .= corPdfText(38, 503, 'Student Number:', 9, true) . corPdfText(135, 503, (string) ($student['student_number'] ?? ''), 9);
    $p .= corPdfText(355, 503, 'Program:', 9, true) . corPdfText(410, 503, (string) ($student['program'] ?? ''), 9);
    $p .= corPdfText(38, 484, 'Student Name:', 9, true) . corPdfText(135, 484, $name, 9);
    $p .= corPdfText(355, 484, 'Year / Section:', 9, true) . corPdfText(450, 484, $year . ' Year / ' . $section, 9);
    $p .= corPdfLine(35, 470, 807, 470);
    $p .= corPdfText(41, 449, 'Course Code', 8, true) . corPdfText(126, 449, 'Subject', 8, true);
    $p .= corPdfText(331, 449, 'Units', 8, true) . corPdfText(368, 449, 'Section', 8, true);
    $p .= corPdfText(446, 449, 'Day / Time', 8, true) . corPdfText(613, 449, 'Room', 8, true);
    $p .= corPdfText(681, 449, 'Professor', 8, true);
    $p .= corPdfLine(35, 437, 807, 437);
    return $p;
}

$name = trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$pages = []; $content = corPdfHeader($name, $student, $period, $year, $section, false);
$top = 437; $totalUnits = 0;
foreach ($courses as $course) {
    $totalUnits += (int) $course['units'];
    $nameLines = corPdfWrap((string) $course['course_name'], 37);
    $profLines = corPdfWrap((string) $course['instructor_name'], 23);
    $scheduleLines = []; $roomLines = [];
    foreach ($course['schedules'] as $s) {
        $day = trim((string) ($s['day_of_week'] ?? ''));
        $time = ($s['start_time'] && $s['end_time']) ? date('g:i A', strtotime($s['start_time'])) . '-' . date('g:i A', strtotime($s['end_time'])) : '';
        $scheduleLines[] = trim($day . ' ' . $time);
        $roomLines[] = (string) ($s['room'] ?? '');
    }
    if (!$scheduleLines) { $scheduleLines = ['Not assigned']; $roomLines = ['-']; }
    $count = max(count($nameLines), count($profLines), count($scheduleLines), count($roomLines), 1);
    $height = max(32, 11 * $count + 13);
    if ($top - $height < 92) {
        $pages[] = $content;
        $content = corPdfHeader($name, $student, $period, $year, $section, true);
        $top = 437;
    }
    $content .= corPdfCellLines(41, $top, corPdfWrap((string) $course['course_code'], 14));
    $content .= corPdfCellLines(126, $top, $nameLines);
    $content .= corPdfCellLines(331, $top, [(string) $course['units']]);
    $content .= corPdfCellLines(368, $top, corPdfWrap((string) $course['section'], 13));
    $content .= corPdfCellLines(446, $top, $scheduleLines, 7);
    $content .= corPdfCellLines(613, $top, $roomLines);
    $content .= corPdfCellLines(681, $top, $profLines);
    $top -= $height;
    $content .= corPdfLine(35, $top, 807, $top);
}
if ($top < 115) {
    $pages[] = $content;
    $content = corPdfHeader($name, $student, $period, $year, $section, true);
    $top = 437;
}
$content .= corPdfText(40, $top - 24, 'TOTAL UNITS: ' . $totalUnits, 10, true);
$content .= corPdfText(40, $top - 51, 'Student Signature: ___________________________________', 9);
$content .= corPdfText(458, $top - 51, 'Date: ______________________', 9);
$pages[] = $content;

// Minimal multi-page PDF writer using built-in Helvetica fonts.
$objects = [];
$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
$objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
$kids = []; $next = 5;
foreach ($pages as $stream) {
    $pageId = $next++; $streamId = $next++;
    $kids[] = $pageId . ' 0 R';
    $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
    $objects[$streamId] = '<< /Length ' . strlen($stream) . ">>\nstream\n" . $stream . "endstream";
}
$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
ksort($objects);
$pdf = "%PDF-1.4\n"; $offsets = [0];
for ($i = 1; $i < $next; $i++) {
    $offsets[$i] = strlen($pdf);
    $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
}
$xref = strlen($pdf);
$pdf .= "xref\n0 $next\n0000000000 65535 f \n";
for ($i = 1; $i < $next; $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
$pdf .= "trailer\n<< /Size $next /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
$filename = 'COR_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($student['student_number'] ?? 'student')) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
exit;
