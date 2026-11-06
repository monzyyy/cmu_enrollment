<?php
require_once __DIR__ . '/../bootstrap.php';
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
function registration_reply(int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') registration_reply(405, ['success'=>false,'message'=>'Method not allowed.']);
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') registration_reply(403, ['success'=>false,'message'=>'Unauthorized.']);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) registration_reply(400, ['success'=>false,'message'=>'Invalid request.']);
if (empty($_SESSION['registration_review_csrf']) || !is_string($data['csrf_token'] ?? null) || !hash_equals($_SESSION['registration_review_csrf'], $data['csrf_token'])) registration_reply(403, ['success'=>false,'message'=>'Invalid security token. Refresh and retry.']);
$requestId = filter_var($data['request_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$action = $data['action'] ?? '';
if (!$requestId || !in_array($action, ['approve','reject'], true)) registration_reply(422, ['success'=>false,'message'=>'Invalid registration request.']);
if ($action === 'approve' && ($data['identity_verified'] ?? false) !== true) registration_reply(422, ['success'=>false,'message'=>'Verify the student identity before approval.']);
$reason = trim((string)($data['rejection_reason'] ?? ''));
if (mb_strlen($reason) > 2000) registration_reply(422, ['success'=>false,'message'=>'Rejection reason is too long.']);
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT r.request_id, r.status, r.password_hash AS requested_password_hash, m.masterlist_id, m.student_number, m.first_name, m.middle_name, m.last_name, m.program, m.year_level, m.section, m.previous_program, m.previous_year_level, m.previous_section, m.previous_semester, m.previous_school_year, m.email, m.registration_status, m.user_id FROM student_registration_requests r INNER JOIN student_masterlist m ON m.masterlist_id=r.masterlist_id WHERE r.request_id=? FOR UPDATE");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$record || $record['status'] !== 'PENDING') throw new DomainException('This request is no longer pending. Refresh the page.');
    if ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE student_registration_requests SET status='REJECTED', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE request_id=? AND status='PENDING'");
        $reviewer = (int)$_SESSION['user_id'];
        $stmt->bind_param('isi', $reviewer, $reason, $requestId);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new RuntimeException('Unable to reject request.');
        $stmt->close();
        $conn->commit();
        registration_reply(200, ['success'=>true,'message'=>'Registration request rejected.']);
    }
    if ($record['registration_status'] !== 'UNREGISTERED' || $record['user_id'] !== null) throw new DomainException('Masterlist record is not eligible for account creation.');
    $number = $record['student_number'];
    $email = strtolower(trim($record['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Masterlist email is invalid.');
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE account_number=? LIMIT 1');
    $stmt->bind_param('s', $number); $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($exists) throw new DomainException('An account already exists for this student number.');
    $stmt = $conn->prepare('SELECT student_id FROM students WHERE student_number=? OR LOWER(TRIM(email))=? LIMIT 1');
    $stmt->bind_param('ss', $number, $email); $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($exists) throw new DomainException('A student account already exists with this student number or email.');
    $hash = (string)($record['requested_password_hash'] ?? '');
    if ($hash === '' || password_get_info($hash)['algo'] === null) {
        throw new DomainException('This request has no valid student-created password. Ask the student to register again.');
    }
     
     
    $previousProgram = trim((string)($record['previous_program'] ?? ''));
    $previousYear = (int)($record['previous_year_level'] ?? 0);
    $previousSection = trim((string)($record['previous_section'] ?? ''));
    $previousSemester = trim((string)($record['previous_semester'] ?? ''));
    $previousSchoolYear = preg_replace('/\s+/', '', (string)($record['previous_school_year'] ?? ''));
    if ($previousProgram === '' || $previousYear < 1 || $previousSection === '' ||
        $previousSemester === '' || $previousSchoolYear === '') {
        throw new DomainException('Previous-semester academic information is incomplete. Ask the Registrar to correct the masterlist before approval.');
    }

    $stmt = $conn->prepare("SELECT offering_id FROM course_offerings
        WHERE program = ? AND year_level = ? AND section = ? AND semester = ?
          AND REPLACE(REPLACE(REPLACE(school_year, ' ', ''), CHAR(9), ''), CHAR(10), '') = ?
        ORDER BY offering_id");
    $stmt->bind_param('sisss', $previousProgram, $previousYear, $previousSection, $previousSemester, $previousSchoolYear);
    $stmt->execute();
    $offeringsResult = $stmt->get_result();
    $offeringIds = [];
    while ($offering = $offeringsResult->fetch_assoc()) {
        $offeringIds[] = (int)$offering['offering_id'];
    }
    $stmt->close();
    if (!$offeringIds) {
        throw new DomainException('No previous-semester course offerings match this student. Ask the Registrar to verify the academic records before approval.');
    }

    $role = 'STUDENT'; $active = 1; $mustChange = 0;
    $stmt = $conn->prepare('INSERT INTO users (account_number,password_hash,role,is_active,must_change_password) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sssii', $number, $hash, $role, $active, $mustChange);
    $stmt->execute(); $userId = (int)$conn->insert_id; $stmt->close();
    $first = $record['first_name']; $middle = $record['middle_name']; $last = $record['last_name'];
    $program = $record['program']; $year = (int)$record['year_level']; $section = $record['section'];
    $phase = 'NOT_STARTED';
    $stmt = $conn->prepare('INSERT INTO students (user_id,student_number,first_name,middle_name,last_name,program,year_level,section,email,password_hash,enrollment_phase) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('isssssissss', $userId, $number, $first, $middle, $last, $program, $year, $section, $email, $hash, $phase);
    $stmt->execute(); $studentId = (int)$conn->insert_id; $stmt->close();

     
     
    $enrollmentType = 'REGULAR';
    $stmt = $conn->prepare('INSERT INTO student_course_enrollments (student_id, offering_id, enrollment_type) VALUES (?, ?, ?)');
    foreach ($offeringIds as $offeringId) {
        $stmt->bind_param('iis', $studentId, $offeringId, $enrollmentType);
        $stmt->execute();
    }
    $stmt->close();

    $masterlistId = (int)$record['masterlist_id'];
    $stmt = $conn->prepare("UPDATE student_masterlist SET registration_status='REGISTERED', user_id=? WHERE masterlist_id=? AND registration_status='UNREGISTERED' AND user_id IS NULL");
    $stmt->bind_param('ii', $userId, $masterlistId); $stmt->execute();
    if ($stmt->affected_rows !== 1) throw new RuntimeException('Unable to link masterlist.');
    $stmt->close();
    $reviewer = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE student_registration_requests SET status='APPROVED', reviewed_by=?, reviewed_at=NOW(), rejection_reason=NULL WHERE request_id=? AND status='PENDING'");
    $stmt->bind_param('ii', $reviewer, $requestId); $stmt->execute();
    if ($stmt->affected_rows !== 1) throw new RuntimeException('Unable to finalize approval.');
    $stmt->close();
    $conn->commit();
    registration_reply(200, ['success'=>true,'message'=>'Student account approved with ' . count($offeringIds) . ' previous-semester subjects linked. The student can log in using the password created during registration.']);
} catch (DomainException $e) {
    $conn->rollback();
    registration_reply(409, ['success'=>false,'message'=>$e->getMessage()]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Registration review failed: ' . $e->getMessage());
    registration_reply(500, ['success'=>false,'message'=>'Unable to process this request. Check server logs.']);
}
