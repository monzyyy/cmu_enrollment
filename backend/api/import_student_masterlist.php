<?php
require_once __DIR__ . '/../bootstrap.php';
require_role('ADMIN');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function output_import(bool $success, string $message, array $errors = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(['success'=>$success,'message'=>$message,'errors'=>$errors]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') output_import(false,'POST required.',[],405);
if (empty($_SESSION['masterlist_import_csrf']) || !hash_equals($_SESSION['masterlist_import_csrf'],(string)($_POST['csrf_token']??''))) output_import(false,'Security token expired. Refresh the page and try again.',[],403);
if (!isset($_FILES['masterlist_csv']) || $_FILES['masterlist_csv']['error'] !== UPLOAD_ERR_OK) output_import(false,'Select a valid CSV file.',[],422);
$file=$_FILES['masterlist_csv'];
if ($file['size'] > 2*1024*1024 || $file['size'] === 0 || strtolower(pathinfo($file['name'],PATHINFO_EXTENSION)) !== 'csv' || !is_uploaded_file($file['tmp_name'])) output_import(false,'Upload a CSV file smaller than 2 MB.',[],422);
$fh=fopen($file['tmp_name'],'rb');
if (!$fh) output_import(false,'Unable to read file.',[],422);
$expected=['student_number','first_name','middle_name','last_name','date_of_birth','email','program','year_level','section','previous_program','previous_year_level','previous_section','previous_semester','previous_school_year'];
$headers=fgetcsv($fh,0,',','"','');
if (!$headers) output_import(false,'The CSV is empty.',[],422);
$headers=array_map(fn($x)=>strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',$x))),$headers);
if ($headers!==$expected) output_import(false,'CSV headers do not match the Excel template. Keep all 14 columns in their original order.',[],422);
$records=[];$errors=[];$seenNumbers=[];$seenEmails=[];$rowNumber=1;
while (($row=fgetcsv($fh,0,',','"',''))!==false) {
    ++$rowNumber;
    if (count($row)===1 && trim($row[0])==='') continue;
    if ($rowNumber>1001) { $errors[]='Maximum 1,000 records per import.';break; }
    if (count($row)!==count($expected)) {$errors[]="Row $rowNumber: incorrect number of columns.";continue;}
    $r=array_combine($expected,array_map('trim',$row));
    foreach ($r as $v) if (!mb_check_encoding($v,'UTF-8')) {$errors[]="Row $rowNumber: save the file as CSV UTF-8.";continue 2;}
    foreach (['student_number','first_name','last_name','email','program','year_level','section'] as $required) if ($r[$required]==='') {$errors[]="Row $rowNumber: missing $required.";continue 2;}
    if (!filter_var($r['email'],FILTER_VALIDATE_EMAIL)) {$errors[]="Row $rowNumber: invalid email.";continue;}
    $r['email']=strtolower($r['email']);
    if (!in_array($r['year_level'],['1','2','3','4'],true) || ($r['previous_year_level']!=='' && !in_array($r['previous_year_level'],['1','2','3','4'],true))) {$errors[]="Row $rowNumber: invalid year level.";continue;}
    if ($r['date_of_birth']!=='') {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$r['date_of_birth']);
        if (!$d || $d->format('Y-m-d')!==$r['date_of_birth']) {$errors[]="Row $rowNumber: date_of_birth must be YYYY-MM-DD.";continue;}
    }
    if (strlen($r['student_number'])>50 || strlen($r['email'])>100 || strlen($r['program'])>100 || strlen($r['section'])>50 || strlen($r['previous_program'])>100 || strlen($r['previous_section'])>50 || strlen($r['previous_semester'])>50 || strlen($r['previous_school_year'])>20 || strlen($r['first_name'])>100 || strlen($r['middle_name'])>100 || strlen($r['last_name'])>100) {$errors[]="Row $rowNumber: a field exceeds its maximum length.";continue;}
    if (isset($seenNumbers[$r['student_number']]) || isset($seenEmails[$r['email']])) {$errors[]="Row $rowNumber: duplicate student number or email in this file.";continue;}
    $seenNumbers[$r['student_number']]=true;$seenEmails[$r['email']]=true;
    $records[]=$r;
}
fclose($fh);
if ($errors) output_import(false,'No records imported. Correct the errors and upload again.',array_slice($errors,0,15),422);
if (!$records) output_import(false,'No student records found.',[],422);
$inserted=0;$skipped=0;
try {
 $conn->begin_transaction();
 $check=$conn->prepare('SELECT masterlist_id FROM student_masterlist WHERE student_number=? OR email=? LIMIT 1');
 $existing=$conn->prepare('SELECT user_id FROM users WHERE account_number=? LIMIT 1');
 $existingStudent=$conn->prepare('SELECT student_id FROM students WHERE student_number=? OR LOWER(email)=? LIMIT 1');
 $insert=$conn->prepare('INSERT INTO student_masterlist (student_number,first_name,middle_name,last_name,date_of_birth,email,program,year_level,section,previous_program,previous_year_level,previous_section,previous_semester,previous_school_year) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
 foreach($records as $r){
   $num=$r['student_number'];$email=$r['email'];
   $check->bind_param('ss',$num,$email);$check->execute();$a=$check->get_result()->fetch_assoc();
   $existing->bind_param('s',$num);$existing->execute();$b=$existing->get_result()->fetch_assoc();
   $existingStudent->bind_param('ss',$num,$email);$existingStudent->execute();$c=$existingStudent->get_result()->fetch_assoc();
   if($a || $b || $c){++$skipped;continue;}
   $middle=$r['middle_name']?:null;$dob=$r['date_of_birth']?:null;$year=(int)$r['year_level'];$prevProgram=$r['previous_program']?:null;$prevYear=$r['previous_year_level']!==''?(int)$r['previous_year_level']:null;$prevSection=$r['previous_section']?:null;$prevSem=$r['previous_semester']?:null;$prevSY=$r['previous_school_year']?:null;
   $insert->bind_param('sssssssississs',$num,$r['first_name'],$middle,$r['last_name'],$dob,$email,$r['program'],$year,$r['section'],$prevProgram,$prevYear,$prevSection,$prevSem,$prevSY);
   $insert->execute();++$inserted;
 }
 $conn->commit();
 output_import(true,"Import complete: $inserted new records; $skipped skipped (already in masterlist or existing accounts). No accounts were created.");
} catch(Throwable $e){$conn->rollback();error_log('Masterlist import: '.$e->getMessage());output_import(false,'Database import failed. Check the server error log.',[],500);}
