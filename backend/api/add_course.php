<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$courseCode = trim($data['course_code'] ?? '');
$courseName = trim($data['course_name'] ?? '');
$units = (int) ($data['units'] ?? 0);

if ($courseCode === '' || $courseName === '' || $units <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Please complete all course information.'
    ]);

    exit;
}


/* Check if course code already exists */

$stmt = $conn->prepare(
    'SELECT course_id
     FROM courses
     WHERE course_code = ?
     LIMIT 1'
);

$stmt->bind_param('s', $courseCode);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    echo json_encode([
        'success' => false,
        'message' => 'This course code already exists.'
    ]);

    exit;
}

$stmt->close();


/* Insert course */

$stmt = $conn->prepare(
    'INSERT INTO courses (
        course_code,
        course_name,
        units,
        is_active
    )
    VALUES (?, ?, ?, 1)'
);

$stmt->bind_param(
    'ssi',
    $courseCode,
    $courseName,
    $units
);

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        'success' => false,
        'message' => 'Failed to add the course.'
    ]);

    exit;
}

$stmt->close();


echo json_encode([
    'success' => true,
    'message' => 'Course added successfully.'
]);