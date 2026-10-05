<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/user.php';

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


require_login();


if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'ADMIN'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'You are not authorized to perform this action.'
    ]);

    exit;
}


$action = $_POST['action'] ?? '';


if (!in_array($action, ['OPEN', 'CLOSED'], true)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid enrollment action.'
    ]);

    exit;
}


try {

    $conn->begin_transaction();


    $stmt = $conn->prepare(
        'SELECT enrollment_status
         FROM system_settings
         WHERE setting_id = 1
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $settings = $result->fetch_assoc();

    $stmt->close();


    if (!$settings) {

        throw new RuntimeException(
            'Enrollment settings were not found.'
        );

    }


    $currentStatus = $settings['enrollment_status'];


    if ($currentStatus === $action) {

        $conn->rollback();

        echo json_encode([
            'success' => true,
            'message' => 'Enrollment status is already ' . $action . '.',
            'status' => $action
        ]);

        exit;
    }


    $stmt = $conn->prepare(
        'UPDATE system_settings
         SET enrollment_status = ?
         WHERE setting_id = 1'
    );

    $stmt->bind_param('s', $action);

    $stmt->execute();

    $stmt->close();


    $historyStatus = $action === 'OPEN'
        ? 'OPENED'
        : 'CLOSED';


    $adminUserId = (int) $_SESSION['user_id'];


    $stmt = $conn->prepare(
        'INSERT INTO enrollment_status_history (
            status,
            changed_by
         )
         VALUES (?, ?)'
    );

    $stmt->bind_param(
        'si',
        $historyStatus,
        $adminUserId
    );

    $stmt->execute();

    $stmt->close();


    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => $action === 'OPEN'
            ? 'Enrollment has been opened.'
            : 'Enrollment has been closed.',
        'status' => $action
    ]);

} catch (Throwable $e) {

    if ($conn->errno === 0) {
        // Nothing needed here.
    }

    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Nothing needed here.
    }

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}