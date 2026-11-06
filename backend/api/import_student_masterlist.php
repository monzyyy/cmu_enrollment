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
if (!isset($_FILES['masterlist_file']) || $_FILES['masterlist_file']['error'] !== UPLOAD_ERR_OK) output_import(false,'Select a valid CSV or XLSX file.',[],422);
$file=$_FILES['masterlist_file'];
$extension=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
if (!in_array($extension,['csv','xlsx'],true) || $file['size'] > 5*1024*1024 || $file['size'] === 0 || !is_uploaded_file($file['tmp_name'])) output_import(false,'Upload a CSV or XLSX file smaller than 5 MB.',[],422);
$expected=['student_number','first_name','middle_name','last_name','date_of_birth','email','program','year_level','section','previous_program','previous_year_level','previous_section','previous_semester','previous_school_year'];

 
function read_xlsx_masterlist(string $path): array {
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) throw new RuntimeException('XLSX requires the PHP zip and SimpleXML extensions. Enable them in php.ini and restart Apache.');
    $zip=new ZipArchive();
    if ($zip->open($path)!==true) throw new RuntimeException('Cannot open the XLSX workbook.');
    try {
        $totalSize=0;
        for($i=0;$i<$zip->numFiles;$i++) {
            $stat=$zip->statIndex($i);
            $totalSize+=(int)($stat['size']??0);
            if ($totalSize>30*1024*1024) throw new RuntimeException('The Excel workbook is too large to process.');
        }
        $workbookXml=$zip->getFromName('xl/workbook.xml');
        $relsXml=$zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml===false || $relsXml===false) throw new RuntimeException('Invalid XLSX workbook structure.');
        $workbook=@simplexml_load_string($workbookXml);
        $rels=@simplexml_load_string($relsXml);
        if (!$workbook || !$rels) throw new RuntimeException('Invalid XLSX workbook XML.');
        $sheets=$workbook->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->sheets->sheet;
        if (!isset($sheets[0])) throw new RuntimeException('Excel workbook has no worksheet.');
        $sheetAttrs=$sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId=(string)$sheetAttrs['id'];
        $target=null;
        foreach ($rels->Relationship as $rel) {
            if ((string)$rel['Id']===$relationshipId) {
                if ((string)$rel['TargetMode']==='External') throw new RuntimeException('External worksheet links are not supported.');
                $target=(string)$rel['Target'];break;
            }
        }
        if (!$target) throw new RuntimeException('Cannot locate the first worksheet.');
        $target=ltrim($target,'/');
        if (!str_starts_with($target,'xl/')) $target='xl/'.$target;
        $parts=[];
        foreach (explode('/',$target) as $part) {
            if ($part==='..') array_pop($parts);
            elseif ($part!=='.' && $part!=='') $parts[]=$part;
        }
        $sheetPath=implode('/',$parts);
        $sheetXml=$zip->getFromName($sheetPath);
        if ($sheetXml===false) throw new RuntimeException('First worksheet is missing.');
        $sheet=@simplexml_load_string($sheetXml);
        if (!$sheet) throw new RuntimeException('Invalid worksheet XML.');
        $shared=[];
        $sharedXml=$zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml!==false) {
            $strings=@simplexml_load_string($sharedXml);
            if (!$strings) throw new RuntimeException('Invalid Excel shared strings.');
            foreach ($strings->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->si as $si) {
                $value='';
                foreach ($si->xpath('.//*[local-name()="t"]') as $t) $value.=(string)$t;
                $shared[]=$value;
            }
        }
        $rows=[];
        $sheetRows=$sheet->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->sheetData->row;
        foreach ($sheetRows as $row) {
            $rowIndex=(int)$row['r'];
            if ($rowIndex<1) continue;
            if ($rowIndex>1001) throw new RuntimeException('Maximum 1,000 records per import.');
            $values=array_fill(0,14,'');
            foreach ($row->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
                $reference=(string)$cell['r'];
                if (!preg_match('/^([A-Z]+)[0-9]+$/i',$reference,$match)) continue;
                $column=0;
                foreach (str_split(strtoupper($match[1])) as $letter) $column=$column*26+ord($letter)-64;
                if ($column<1 || $column>14) {
                     
                    continue;
                }
                $type=(string)$cell['t'];
                if ($type==='e') throw new RuntimeException("Excel row $rowIndex contains an error cell.");
                if ($type==='inlineStr') {
                    $value='';
                    foreach ($cell->xpath('.//*[local-name()="is"]//*[local-name()="t"]') as $t) $value.=(string)$t;
                } else {
                    $value=(string)$cell->v;
                    if ($type==='s') {
                        if (!ctype_digit($value) || !isset($shared[(int)$value])) throw new RuntimeException('Invalid Excel shared string index.');
                        $value=$shared[(int)$value];
                    }
                }
                 
                if ($column===5 && $value!=='' && is_numeric($value)) {
                    $serial=(float)$value;
                    if ($serial>=1 && $serial<=2958465) {
                        $date=new DateTimeImmutable('1899-12-30',new DateTimeZone('UTC'));
                        $value=$date->modify('+' . (int)floor($serial) . ' days')->format('Y-m-d');
                    }
                }
                $values[$column-1]=$value;
            }
            $rows[$rowIndex]=$values;
        }
        ksort($rows,SORT_NUMERIC);
        return $rows;
    } finally { $zip->close(); }
}

try {
    if ($extension==='csv') {
        $fh=fopen($file['tmp_name'],'rb');
        if (!$fh) throw new RuntimeException('Unable to read CSV file.');
        $rows=[];$line=0;
        while (($row=fgetcsv($fh,0,',','"',''))!==false) {
            $line++;
            if ($line>1001) throw new RuntimeException('Maximum 1,000 records per import.');
            $rows[$line]=$row;
        }
        fclose($fh);
    } else {
        $rows=read_xlsx_masterlist($file['tmp_name']);
    }
} catch (Throwable $e) { output_import(false,$e->getMessage(),[],422); }
$headers=$rows[1]??null;
if (!$headers) output_import(false,'The file is empty.',[],422);
$headers=array_map(fn($x)=>strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$x))),$headers);
if ($headers!==$expected) output_import(false,'File headers do not match the masterlist template. Keep all 14 columns in their original order.',[],422);
$records=[];$errors=[];$seenNumbers=[];$seenEmails=[];$rowNumber=1;
foreach ($rows as $rowNumber => $row) {
    if ($rowNumber===1) continue;
    if (count($row)===1 && trim((string)$row[0])==='') continue;
    if (count($row)===14 && count(array_filter($row,static fn($v)=>trim((string)$v)!==''))===0) continue;
    if (count($row)!==count($expected)) {$errors[]="Row $rowNumber: incorrect number of columns.";continue;}
    $r=array_combine($expected,array_map('trim',$row));
    foreach ($r as $v) if (!mb_check_encoding($v,'UTF-8')) {$errors[]="Row $rowNumber: use UTF-8 text in the file.";continue 2;}
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
