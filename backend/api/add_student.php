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

$data = json_decode(file_get_contents('php://input'), true);

$studentNumber = trim($data['student_number'] ?? '');
$password      = $data['password'] ?? '';
$firstName     = trim($data['first_name'] ?? '');
$middleName    = trim($data['middle_name'] ?? '');
$lastName      = trim($data['last_name'] ?? '');
$program       = trim($data['program'] ?? '');
$yearLevel     = (int) ($data['year_level'] ?? 0);
$section       = trim($data['section'] ?? '');
$email         = strtolower(trim($data['email'] ?? ''));
$phone         = trim($data['phone'] ?? '');

if (
    $studentNumber === '' ||
    $password === '' ||
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

// Phone is optional, but supplied Philippine mobile numbers must use
// 09XXXXXXXXX (11 digits) or +639XXXXXXXXX (13 characters).
if ($phone !== '' && !preg_match('/^(?:09[0-9]{9}|\+639[0-9]{9})$/D', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).'
    ]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare(
        'SELECT user_id
         FROM users
         WHERE account_number = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();

    $existingUser = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingUser) {
        throw new Exception(
            'A user account with this student number already exists.'
        );
    }

    $stmt = $conn->prepare(
        'SELECT student_id
         FROM students
         WHERE student_number = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();

    $existingStudent = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingStudent) {
        throw new Exception(
            'A student with this student number already exists.'
        );
    }

    $stmt = $conn->prepare(
        'SELECT student_id
         FROM students
         WHERE LOWER(TRIM(email)) = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $existingEmail = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existingEmail) {
        throw new Exception(
            'This email address is already registered. Please use a different email.'
        );
    }

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $role = 'STUDENT';
    $isActive = 1;

    $stmt = $conn->prepare(
        'INSERT INTO users (
            account_number,
            password_hash,
            role,
            is_active
        )
        VALUES (?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'sssi',
        $studentNumber,
        $passwordHash,
        $role,
        $isActive
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Failed to create user account.'
        );
    }

    $userId = $conn->insert_id;

    $stmt->close();

    $enrollmentPhase = 'NOT_STARTED';

    $stmt = $conn->prepare(
        'INSERT INTO students (
            user_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            program,
            year_level,
            section,
            email,
            phone,
            password_hash,
            enrollment_phase
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'isssssisssss',
        $userId,
        $studentNumber,
        $firstName,
        $middleName,
        $lastName,
        $program,
        $yearLevel,
        $section,
        $email,
        $phone,
        $passwordHash,
        $enrollmentPhase
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Failed to create student record.'
        );
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Student added successfully.'
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}