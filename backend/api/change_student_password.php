<?php
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function password_response(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['success' => $code === 200, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    password_response(405, 'Method not allowed.');
}
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'STUDENT' || empty($_SESSION['student_id'])) {
    password_response(401, 'Please log in as a student.');
}
if (empty($_SESSION['profile_password_csrf']) || !hash_equals($_SESSION['profile_password_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
    password_response(403, 'Session expired. Refresh the page and try again.');
}

$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');
if ($current === '' || strlen($new) < 8 || strlen($new) > 72 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
    password_response(422, 'Enter your current password and a new password of 8–72 characters with a letter and number.');
}
if (!hash_equals($new, $confirm)) password_response(422, 'New passwords do not match.');
if (hash_equals($current, $new)) password_response(422, 'Choose a different password.');

 
if (($_SESSION['student_pw_lock_until'] ?? 0) > time()) {
    password_response(429, 'Too many incorrect attempts. Please try again in five minutes.');
}

$userId = (int) $_SESSION['user_id'];
$studentId = (int) $_SESSION['student_id'];
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? AND role = ? AND is_active = 1 FOR UPDATE');
    $role = 'STUDENT';
    $stmt->bind_param('is', $userId, $role);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('SELECT student_id FROM students WHERE student_id = ? AND user_id = ? FOR UPDATE');
    $stmt->bind_param('ii', $studentId, $userId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || !$student) {
        $conn->rollback();
        password_response(403, 'Account not found. Please sign in again.');
    }
    if (!password_verify($current, $user['password_hash'])) {
        $conn->rollback();
        $_SESSION['student_pw_failures'] = (int)($_SESSION['student_pw_failures'] ?? 0) + 1;
        if ($_SESSION['student_pw_failures'] >= 5) {
            $_SESSION['student_pw_lock_until'] = time() + 300;
            $_SESSION['student_pw_failures'] = 0;
        }
        password_response(422, 'Current password is incorrect.');
    }
    if (password_verify($new, $user['password_hash'])) {
        $conn->rollback();
        password_response(422, 'Choose a different password.');
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?');
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare('UPDATE students SET password_hash = ? WHERE student_id = ? AND user_id = ?');
    $stmt->bind_param('sii', $hash, $studentId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    unset($_SESSION['student_pw_failures'], $_SESSION['student_pw_lock_until']);
    session_regenerate_id(true);
    password_response(200, 'Password changed successfully. Use your new password the next time you log in.');
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Student password change failed: ' . $e->getMessage());
    password_response(500, 'Unable to change password. Please try again.');
}
