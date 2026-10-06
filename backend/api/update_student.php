<?php

require_once __DIR__ . '/../bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$data = json_decode(
    file_get_contents('php://input'),
    true
);

$studentId    = (int) ($data['student_id'] ?? 0);
$studentNumber = trim($data['student_number'] ?? '');
$password     = $data['password'] ?? '';
$firstName    = trim($data['first_name'] ?? '');
$middleName   = trim($data['middle_name'] ?? '');
$lastName     = trim($data['last_name'] ?? '');
$program      = trim($data['program'] ?? '');
$yearLevel    = (int) ($data['year_level'] ?? 0);
$section      = trim($data['section'] ?? '');
$email        = trim($data['email'] ?? '');
$phone        = trim($data['phone'] ?? '');
$isActive = (int) ($data['is_active'] ?? 0);

if ($isActive !== 0 && $isActive !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid account status.'
    ]);
    exit;
}

if (
    $studentId <= 0 ||
    $studentNumber === '' ||
    $firstName === '' ||
    $lastName === '' ||
    $program === '' ||
    $yearLevel < 1 ||
    $yearLevel > 4 ||
    $section === '' ||
    $email === ''
) {
    echo json_encode([
        'success' => false,
        'message' => 'Please complete all required fields.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare(
        'SELECT
            student_id,
            user_id
         FROM students
         WHERE student_id = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$student) {
        throw new Exception(
            'Student record not found.'
        );
    }

    $userId = (int) $student['user_id'];

    $stmt = $conn->prepare(
        'SELECT user_id
         FROM users
         WHERE account_number = ?
           AND user_id != ?
         LIMIT 1'
    );

    $stmt->bind_param(
        'si',
        $studentNumber,
        $userId
    );

    $stmt->execute();

    $existingUser = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingUser) {
        throw new Exception(
            'Another account already uses this student number.'
        );
    }

    $stmt = $conn->prepare(
        'SELECT student_id
         FROM students
         WHERE student_number = ?
           AND student_id != ?
         LIMIT 1'
    );

    $stmt->bind_param(
        'si',
        $studentNumber,
        $studentId
    );

    $stmt->execute();

    $existingStudent = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingStudent) {
        throw new Exception(
            'Another student already uses this student number.'
        );
    }

    $stmt = $conn->prepare(
        'SELECT student_id
         FROM students
         WHERE email = ?
           AND student_id != ?
         LIMIT 1'
    );

    $stmt->bind_param(
        'si',
        $email,
        $studentId
    );

    $stmt->execute();

    $existingEmail = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingEmail) {
        throw new Exception(
            'Another student already uses this email.'
        );
    }

    $stmt = $conn->prepare(
        'UPDATE users
         SET account_number = ?
         WHERE user_id = ?'
    );

    $stmt->bind_param(
        'si',
        $studentNumber,
        $userId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Failed to update account.'
        );
    }

    $stmt->close();

    $stmt = $conn->prepare(
        'UPDATE users
        SET is_active = ?
        WHERE user_id = ?'
    );

    $stmt->bind_param(
        'ii',
        $isActive,
        $userId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Failed to update account status.'
        );
    }

    $stmt->close();

    if ($password !== '') {

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            'UPDATE users
             SET password_hash = ?
             WHERE user_id = ?'
        );

        $stmt->bind_param(
            'si',
            $passwordHash,
            $userId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Failed to update password.'
            );
        }

        $stmt->close();

        $stmt = $conn->prepare(
            'UPDATE students
             SET password_hash = ?
             WHERE student_id = ?'
        );

        $stmt->bind_param(
            'si',
            $passwordHash,
            $studentId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Failed to update student password.'
            );
        }

        $stmt->close();
    }

    $stmt = $conn->prepare(
        'UPDATE students
         SET
            student_number = ?,
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            program = ?,
            year_level = ?,
            section = ?,
            email = ?,
            phone = ?
         WHERE student_id = ?'
    );

    $stmt->bind_param(
        'sssssisssi',
        $studentNumber,
        $firstName,
        $middleName,
        $lastName,
        $program,
        $yearLevel,
        $section,
        $email,
        $phone,
        $studentId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Failed to update student information.'
        );
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully.'
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}