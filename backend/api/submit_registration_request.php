<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function registration_response(int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['success' => $status === 200, 'message' => $message]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    registration_response(405, 'Method not allowed.');
}
if (!empty($_SESSION['user_id'])) registration_response(403, 'Please sign out first.');
$token = (string)($_POST['csrf_token'] ?? '');
if ($token === '' || !hash_equals((string)($_SESSION['registration_csrf'] ?? ''), $token)) {
    registration_response(403, 'Please reload the registration page.');
}
$studentNumber = trim((string)($_POST['student_number'] ?? ''));
$lastName = trim((string)($_POST['last_name'] ?? ''));
$birthDate = trim((string)($_POST['date_of_birth'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
if ($password !== $confirmPassword) registration_response(422, 'The passwords do not match.');
if (strlen($password) < 8 || strlen($password) > 72 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    registration_response(422, 'Use 8 to 72 characters, including a letter and a number.');
}
if ($birthDate === '' || !checkdate((int)substr($birthDate, 5, 2), (int)substr($birthDate, 8, 2), (int)substr($birthDate, 0, 4)) ||
    $studentNumber === '' || strlen($studentNumber) > 50 || $lastName === '' || strlen($lastName) > 100 ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
    registration_response(422, 'Enter valid student information.');
}
$now = time();
$requests = array_values(array_filter($_SESSION['registration_submission_times'] ?? [], static fn($t) => is_int($t) && $t > $now - 3600));
if (count($requests) >= 5) registration_response(429, 'Too many attempts. Please try again later.');
$requests[] = $now;
$_SESSION['registration_submission_times'] = $requests;
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('SELECT masterlist_id, email, registration_status, user_id FROM student_masterlist WHERE student_number = ? AND LOWER(TRIM(last_name)) = LOWER(?) AND date_of_birth = ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('sss', $studentNumber, $lastName, $birthDate);
    $stmt->execute();
    $master = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$master || $master['registration_status'] !== 'UNREGISTERED' || $master['user_id'] !== null) {
        $conn->rollback();
        registration_response(422, 'Unable to verify eligibility. Please check your details or contact the Registrar.');
    }
    $stmt = $conn->prepare('SELECT 1 FROM users WHERE account_number = ? LIMIT 1');
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $userExists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    $stmt = $conn->prepare('SELECT 1 FROM students WHERE student_number = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $studentNumber, $master['email']);
    $stmt->execute();
    $studentExists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if ($userExists || $studentExists) {
        $conn->rollback();
        registration_response(422, 'An account may already exist. Please contact the Registrar.');
    }
    $id = (int)$master['masterlist_id'];
    $stmt = $conn->prepare('SELECT status FROM student_registration_requests WHERE masterlist_id = ? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existing && $existing['status'] !== 'REJECTED') {
        $conn->rollback();
        registration_response(422, 'A registration request already exists. Please contact the Registrar.');
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($existing) {
        $stmt = $conn->prepare("UPDATE student_registration_requests SET password_hash = ?, status = 'PENDING', requested_at = NOW(), reviewed_by = NULL, reviewed_at = NULL, rejection_reason = NULL WHERE masterlist_id = ?");
        $stmt->bind_param('si', $passwordHash, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_registration_requests (masterlist_id, password_hash, status) VALUES (?, ?, 'PENDING')");
        $stmt->bind_param('is', $id, $passwordHash);
    }
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    registration_response(200, 'Registration request submitted. Please wait for Registrar approval before logging in.');
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Registration submission error: ' . $e->getMessage());
    registration_response(500, 'Unable to process your request. Please try again later.');
}
