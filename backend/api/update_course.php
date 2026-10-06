<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$courseId = (int) ($data['course_id'] ?? 0);
$courseCode = trim($data['course_code'] ?? '');
$courseName = trim($data['course_name'] ?? '');
$units = (int) ($data['units'] ?? 0);

if (
    $courseId <= 0 ||
    $courseCode === '' ||
    $courseName === '' ||
    $units <= 0
) {
    echo json_encode([
        'success' => false,
        'message' => 'Please complete all course information.'
    ]);

    exit;
}


/* Check if another course already uses this code */

$stmt = $conn->prepare(
    'SELECT course_id
     FROM courses
     WHERE course_code = ?
       AND course_id <> ?
     LIMIT 1'
);

$stmt->bind_param(
    'si',
    $courseCode,
    $courseId
);

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


/* Update course */

$stmt = $conn->prepare(
    'UPDATE courses
     SET
        course_code = ?,
        course_name = ?,
        units = ?
     WHERE course_id = ?'
);

$stmt->bind_param(
    'ssii',
    $courseCode,
    $courseName,
    $units,
    $courseId
);

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        'success' => false,
        'message' => 'Failed to update the course.'
    ]);

    exit;
}

$stmt->close();


echo json_encode([
    'success' => true,
    'message' => 'Course updated successfully.'
]);