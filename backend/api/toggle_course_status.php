<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

$data = json_decode(
    file_get_contents('php://input'),
    true
);

$courseId = (int) ($data['course_id'] ?? 0);
$action = $data['action'] ?? '';

if ($courseId <= 0 || !in_array($action, ['activate', 'deactivate'], true)) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid course information.'
    ]);

    exit;
}


$newStatus = $action === 'activate' ? 1 : 0;


/*
 * Check that the course exists.
 */

$stmt = $conn->prepare(
    'SELECT course_id, is_active
     FROM courses
     WHERE course_id = ?
     LIMIT 1'
);

$stmt->bind_param(
    'i',
    $courseId
);

$stmt->execute();

$result = $stmt->get_result();

$course = $result->fetch_assoc();

$stmt->close();


if (!$course) {

    echo json_encode([
        'success' => false,
        'message' => 'Course not found.'
    ]);

    exit;
}


/*
 * Update status.
 */

$stmt = $conn->prepare(
    'UPDATE courses
     SET is_active = ?
     WHERE course_id = ?'
);

$stmt->bind_param(
    'ii',
    $newStatus,
    $courseId
);

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        'success' => false,
        'message' => 'Failed to update the course status.'
    ]);

    exit;
}

$stmt->close();


echo json_encode([
    'success' => true,
    'message' => $newStatus === 1
        ? 'Course reactivated successfully.'
        : 'Course deactivated successfully.',
    'is_active' => $newStatus
]);