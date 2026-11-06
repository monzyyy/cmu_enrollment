<?php
require_once __DIR__ . '/../bootstrap.php';
require_role('ADMIN');
header('Content-Type: application/json');
function fail_period(string $message, int $code = 400): void { http_response_code($code); echo json_encode(['success'=>false,'message'=>$message]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail_period('Method not allowed.',405);
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '') || empty($_SESSION['csrf_token'])) fail_period('Invalid security token.',403);
if (($_POST['confirmation'] ?? '') !== 'START NEW PERIOD') fail_period('Confirmation is required.');
$semester = trim($_POST['semester'] ?? '');
$schoolYear = trim($_POST['school_year'] ?? '');
$start = trim($_POST['start_date'] ?? '');
$end = trim($_POST['end_date'] ?? '');
if (!in_array($semester, ['1st Semester','2nd Semester','Summer'],true)) fail_period('Invalid semester.');
if (!preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $schoolYear, $m) || (int)$m[2] !== (int)$m[1]+1) fail_period('Use a school year such as 2027 - 2028.');
$schoolYear = $m[1] . ' - ' . $m[2];
foreach ([$start,$end] as $date) { $dt = DateTimeImmutable::createFromFormat('!Y-m-d',$date); if (!$dt || $dt->format('Y-m-d') !== $date) fail_period('Enter valid start and end dates.'); }
if ($start > $end) fail_period('End date must not precede start date.');
try {
    $conn->begin_transaction();
    $stmt=$conn->prepare('SELECT enrollment_status,semester,school_year FROM system_settings WHERE setting_id=1 FOR UPDATE');
    $stmt->execute(); $settings=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$settings || $settings['enrollment_status'] !== 'CLOSED') throw new RuntimeException('Close the current enrollment period first.');
    if ($settings['semester']===$semester && preg_replace('/\s+/','',$settings['school_year'])===preg_replace('/\s+/','',$schoolYear)) throw new RuntimeException('Choose a different enrollment period.');
    $stmt=$conn->prepare('SELECT period_id,status FROM enrollment_periods WHERE semester=? AND REPLACE(school_year," ","")=REPLACE(?," ","") FOR UPDATE');
    $stmt->bind_param('ss',$semester,$schoolYear); $stmt->execute(); $existing=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($existing) throw new RuntimeException('That enrollment period already exists.');
     
    $stmt=$conn->prepare('SELECT period_id FROM enrollment_periods WHERE semester=? AND REPLACE(school_year," ","")=REPLACE(?," ","") FOR UPDATE');
    $stmt->bind_param('ss',$settings['semester'],$settings['school_year']);
    $stmt->execute(); $currentPeriod=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$currentPeriod) throw new RuntimeException('Current enrollment period was not found in enrollment_periods.');
    $currentPeriodId=(int)$currentPeriod['period_id'];
     
    $stmt=$conn->prepare('SELECT COUNT(*) AS n FROM cor_submissions c JOIN enrollment_periods p ON c.period_id=p.period_id WHERE p.semester=? AND REPLACE(p.school_year," ","")=REPLACE(?," ","") AND c.status="PENDING"');
    $stmt->bind_param('ss',$settings['semester'],$settings['school_year']); $stmt->execute(); $pendingCor=(int)$stmt->get_result()->fetch_assoc()['n']; $stmt->close();
    $stmt=$conn->prepare('SELECT COUNT(*) AS n FROM clearance_submissions WHERE semester=? AND REPLACE(school_year," ","")=REPLACE(?," ","") AND status="PENDING"');
    $stmt->bind_param('ss',$settings['semester'],$settings['school_year']); $stmt->execute(); $pendingClearance=(int)$stmt->get_result()->fetch_assoc()['n']; $stmt->close();
    if ($pendingCor || $pendingClearance) throw new RuntimeException('Resolve pending COR and Clearance submissions before starting a new period.');
     
    $result=$conn->query('SELECT COUNT(*) AS n FROM cor_submissions WHERE period_id IS NULL');
    if ((int)$result->fetch_assoc()['n'] > 0) throw new RuntimeException('Some old COR submissions have no period_id. Verify and assign their period before starting a new period.');
     
     
     
     
    $stmt = $conn->prepare(
        'INSERT INTO student_course_enrollments
            (student_id, offering_id, enrollment_type)
         SELECT s.student_id, co.offering_id, "REGULAR"
         FROM students s
         INNER JOIN course_offerings co
           ON co.program = s.program
          AND co.year_level = s.year_level
          AND co.section = s.section
         WHERE co.semester = ?
           AND REPLACE(co.school_year, " ", "") = REPLACE(?, " ", "")
           AND NOT EXISTS (
               SELECT 1 FROM student_course_enrollments sce
               WHERE sce.student_id = s.student_id
                 AND sce.offering_id = co.offering_id
           )'
    );
    $stmt->bind_param('ss', $settings['semester'], $settings['school_year']);
    $stmt->execute();
    $assignmentsSaved = $stmt->affected_rows;
    $stmt->close();

    $stmt=$conn->prepare('UPDATE enrollment_periods SET status="CLOSED",closed_at=COALESCE(closed_at,NOW()) WHERE period_id=?');
    $stmt->bind_param('i',$currentPeriodId); $stmt->execute(); $stmt->close();
    $stmt=$conn->prepare('INSERT INTO enrollment_periods (semester,school_year,start_date,end_date,status) VALUES (?,?,?, ?,"CLOSED")');
    $stmt->bind_param('ssss',$semester,$schoolYear,$start,$end); $stmt->execute(); $stmt->close();
    $stmt=$conn->prepare('UPDATE system_settings SET semester=?,school_year=?,start_date=?,end_date=?,enrollment_status="CLOSED" WHERE setting_id=1');
    $stmt->bind_param('ssss',$semester,$schoolYear,$start,$end); $stmt->execute(); $stmt->close();
    $conn->query('UPDATE students SET enrollment_phase="NOT_STARTED", enrollment_status="Closed"');
    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'New period created (CLOSED). Student phases reset; previous records preserved. New historical course assignments saved: ' . $assignmentsSaved . '.', 'assignments_saved'=>$assignmentsSaved]);
} catch(Throwable $e) {
    $conn->rollback(); error_log('start_enrollment_period: '.$e->getMessage());
    fail_period($e instanceof RuntimeException ? $e->getMessage() : 'Unable to create enrollment period.',400);
}
