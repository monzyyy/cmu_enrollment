<?php
require_once __DIR__ . '/../bootstrap.php';
require_role('ADMIN');
header('Content-Type: application/json');
function edit_fail($msg,$code=400) {http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}
if ($_SERVER['REQUEST_METHOD']!=='POST') edit_fail('Method not allowed.',405);
$start=trim($_POST['start_date']??''); $end=trim($_POST['end_date']??'');
foreach ([$start,$end] as $date) { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date); if (!$d || $d->format('Y-m-d')!==$date) edit_fail('Invalid date.'); }
if ($start>$end) edit_fail('End date cannot precede start date.');
try {
 $conn->begin_transaction();
 $r=$conn->query('SELECT semester,school_year FROM system_settings WHERE setting_id=1 FOR UPDATE')->fetch_assoc();
 if (!$r) throw new RuntimeException('Current period missing.');
 // Semester/year are intentionally ignored: changing them requires Start New Period.
 $s=$conn->prepare('UPDATE enrollment_periods SET start_date=?,end_date=? WHERE semester=? AND REPLACE(school_year," ","")=REPLACE(?," ","")');
 $s->bind_param('ssss',$start,$end,$r['semester'],$r['school_year']); $s->execute(); $s->close();
 $s=$conn->prepare('SELECT period_id FROM enrollment_periods WHERE semester=? AND REPLACE(school_year," ","")=REPLACE(?," ","") LIMIT 1');
 $s->bind_param('ss',$r['semester'],$r['school_year']); $s->execute(); $exists=$s->get_result()->fetch_assoc(); $s->close();
 if (!$exists) throw new RuntimeException('Current period is missing from enrollment_periods.');
 $s=$conn->prepare('UPDATE system_settings SET start_date=?,end_date=? WHERE setting_id=1'); $s->bind_param('ss',$start,$end);$s->execute();$s->close();
 $conn->commit(); echo json_encode(['success'=>true,'message'=>'Enrollment dates updated.']);
} catch(Throwable $e){$conn->rollback();error_log($e->getMessage());edit_fail('Unable to update enrollment dates.');}
