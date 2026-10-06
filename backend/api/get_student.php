<?php

require_once __DIR__ . '/../bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

$studentId = (int) ($_GET['id'] ?? 0);

if ($studentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid student ID.'
    ]);
    exit;
}

$stmt = $conn->prepare(
    'SELECT
        s.student_id,
        s.user_id,
        s.student_number,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.program,
        s.year_level,
        s.section,
        s.email,
        s.phone,
        u.is_active
    FROM students s
    INNER JOIN users u
        ON u.user_id = s.user_id
    WHERE s.student_id = ?
    LIMIT 1'
);

$stmt->bind_param('i', $studentId);

$stmt->execute();

$student =
    $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$student) {

    echo json_encode([
        'success' => false,
        'message' => 'Student not found.'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'student' => $student
]);