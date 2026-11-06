<?php
declare(strict_types=1);




require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, string $message, array $extra = []): never {
    http_response_code($status);
    echo json_encode(['success' => $status === 200, 'message' => $message] + $extra);
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

$verified = $_SESSION['registration_verified'] ?? null;
if (!is_array($verified) || (int)($verified['expires'] ?? 0) < time()) {
    unset($_SESSION['registration_verified']);
    respond(403, 'Your verification has expired. Please start again.', ['restart' => true]);
}

$password = (string)($_POST['password'] ?? '');
$confirm  = (string)($_POST['confirm_password'] ?? '');

if ($password !== $confirm) {
    respond(422, 'The passwords do not match.');
}
if (strlen($password) < 8 || strlen($password) > 72) {
     
    respond(422, 'Password must be 8 to 72 characters long.');
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    respond(422, 'Password must contain at least one letter and one number.');
}

$masterlistId   = (int)$verified['masterlist_id'];
$verificationId = (int)$verified['verification_id'];

try {
    $conn->begin_transaction();

     
    $stmt = $conn->prepare('SELECT student_number, first_name, middle_name, last_name, email,
            program, year_level, section, registration_status, user_id
        FROM student_masterlist WHERE masterlist_id = ? FOR UPDATE');
    $stmt->bind_param('i', $masterlistId);
    $stmt->execute();
    $m = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('SELECT verified_at, consumed_at
        FROM registration_verifications WHERE verification_id = ? AND masterlist_id = ? FOR UPDATE');
    $stmt->bind_param('ii', $verificationId, $masterlistId);
    $stmt->execute();
    $v = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$m || !$v || $v['verified_at'] === null || $v['consumed_at'] !== null
        || $m['registration_status'] !== 'UNREGISTERED' || $m['user_id'] !== null) {
        $conn->rollback();
        unset($_SESSION['registration_verified']);
        respond(403, 'This registration can no longer be completed. Please start again.', ['restart' => true]);
    }

     
    $stmt = $conn->prepare('SELECT 1 FROM users WHERE account_number = ? LIMIT 1');
    $stmt->bind_param('s', $m['student_number']);
    $stmt->execute();
    $userExists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    $stmt = $conn->prepare('SELECT 1 FROM students WHERE student_number = ? OR LOWER(TRIM(email)) = ? LIMIT 1');
    $email = strtolower(trim((string)$m['email']));
    $stmt->bind_param('ss', $m['student_number'], $email);
    $stmt->execute();
    $studentExists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    if ($userExists || $studentExists) {
        $conn->rollback();
        unset($_SESSION['registration_verified']);
        respond(409, 'An account for this student already exists. Try logging in instead.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'STUDENT';
    $isActive = 1;

    $stmt = $conn->prepare('INSERT INTO users (account_number, password_hash, role, is_active) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $m['student_number'], $passwordHash, $role, $isActive);
    $stmt->execute();
    $userId = $conn->insert_id;
    $stmt->close();

    $phase = 'NOT_STARTED';
    $middle = (string)($m['middle_name'] ?? '');
    $yearLevel = (int)$m['year_level'];
    $stmt = $conn->prepare('INSERT INTO students
        (user_id, student_number, first_name, middle_name, last_name, program, year_level, section, email, password_hash, enrollment_phase)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssissss', $userId, $m['student_number'], $m['first_name'], $middle,
        $m['last_name'], $m['program'], $yearLevel, $m['section'], $email, $passwordHash, $phase);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE student_masterlist SET registration_status = 'REGISTERED', user_id = ? WHERE masterlist_id = ?");
    $stmt->bind_param('ii', $userId, $masterlistId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE registration_verifications SET consumed_at = NOW() WHERE verification_id = ?');
    $stmt->bind_param('i', $verificationId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    unset($_SESSION['registration_verified'], $_SESSION['registration_csrf']);
    respond(200, 'Account created! Redirecting to login...', ['redirect' => BASE_URL . '?page=login']);
} catch (Throwable $exception) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Registration completion error: ' . $exception->getMessage());
    respond(500, 'Unable to create your account right now. Please try again later.');
}
