<?php
declare(strict_types=1);




require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_CODE_ATTEMPTS = 5;       
const VERIFIED_WINDOW   = 900;     

function respond(int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['success' => $status === 200, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, 'Method not allowed.');
}
if (!empty($_SESSION['user_id'])) {
    respond(403, 'Sign out before creating another account.');
}

$submittedToken = (string)($_POST['csrf_token'] ?? '');
if ($submittedToken === '' || !hash_equals((string)($_SESSION['registration_csrf'] ?? ''), $submittedToken)) {
    respond(403, 'Please reload the registration page and try again.');
}

$studentNumber = trim((string)($_POST['student_number'] ?? ''));
$code          = trim((string)($_POST['code'] ?? ''));

if ($studentNumber === '' || strlen($studentNumber) > 50 || !preg_match('/^\d{6}$/', $code)) {
    respond(422, 'Enter your student number and the 6-digit code.');
}

 
 
$invalid = 'That code is incorrect or has expired. Check the code, or go back and request a new one.';

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare('SELECT masterlist_id, registration_status, user_id
        FROM student_masterlist WHERE student_number = ? LIMIT 1');
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $master = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $row = null;
    if ($master && $master['registration_status'] === 'UNREGISTERED' && $master['user_id'] === null) {
         
        $id = (int)$master['masterlist_id'];
        $stmt = $conn->prepare('SELECT verification_id, code_hash, expires_at, attempts, consumed_at
            FROM registration_verifications WHERE masterlist_id = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$row || $row['consumed_at'] !== null) {
         
        password_hash($code, PASSWORD_DEFAULT);
        $conn->rollback();
        respond(422, $invalid);
    }

    $expired = strtotime((string)$row['expires_at']) < time();
    $locked  = (int)$row['attempts'] >= MAX_CODE_ATTEMPTS;
    if ($expired || $locked) {
        password_hash($code, PASSWORD_DEFAULT);
        $conn->rollback();
        respond(422, $invalid);
    }

    if (!password_verify($code, (string)$row['code_hash'])) {
        $stmt = $conn->prepare('UPDATE registration_verifications SET attempts = attempts + 1 WHERE verification_id = ?');
        $verificationId = (int)$row['verification_id'];
        $stmt->bind_param('i', $verificationId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();  
        respond(422, $invalid);
    }

    $verificationId = (int)$row['verification_id'];
    $stmt = $conn->prepare('UPDATE registration_verifications SET verified_at = NOW() WHERE verification_id = ?');
    $stmt->bind_param('i', $verificationId);
    $stmt->execute();
    $stmt->close();
    $conn->commit();

    session_regenerate_id(true);
    $_SESSION['registration_verified'] = [
        'masterlist_id'   => (int)$master['masterlist_id'],
        'verification_id' => $verificationId,
        'expires'         => time() + VERIFIED_WINDOW,
    ];

    respond(200, 'Code verified. Create your password to finish.');
} catch (Throwable $exception) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Registration code verification error: ' . $exception->getMessage());
    respond(500, 'Unable to process your request right now. Please try again later.');
}
