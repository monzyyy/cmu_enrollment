<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');

$submissionId = (int) ($_POST['submission_id'] ?? 0);
$action = $_POST['action'] ?? '';
$rejectionReason = trim($_POST['rejection_reason'] ?? '');

file_put_contents(
    __DIR__ . '/reject_debug.txt',
    print_r($_POST, true)
);

if ($submissionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid COR submission.'
    ]);
    exit;
}

if (!in_array($action, ['APPROVE', 'REJECT'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid review action.'
    ]);
    exit;
}

if (
    $action === 'REJECT' &&
    $rejectionReason === ''
) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a rejection reason.'
    ]);
    exit;
}

$adminId = (int) $_SESSION['user_id'];

$conn->begin_transaction();

try {

    $stmt = $conn->prepare(
        'SELECT
            cor_submission_id,
            student_id,
            status
         FROM cor_submissions
         WHERE cor_submission_id = ?
         FOR UPDATE'
    );

    $stmt->bind_param('i', $submissionId);
    $stmt->execute();

    $submission =
        $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$submission) {
        throw new Exception(
            'COR submission not found.'
        );
    }


    if ($submission['status'] !== 'PENDING') {
        throw new Exception(
            'This COR submission has already been reviewed.'
        );
    }


    $studentId =
        (int) $submission['student_id'];


    if ($action === 'APPROVE') {

        $stmt = $conn->prepare(
            'UPDATE cor_submissions
             SET
                status = "APPROVED",
                reviewed_at = NOW(),
                reviewed_by = ?,
                rejection_reason = NULL
             WHERE cor_submission_id = ?'
        );

        $stmt->bind_param(
            'ii',
            $adminId,
            $submissionId
        );

        $stmt->execute();

        $stmt->close();


        $stmt = $conn->prepare(
            'UPDATE students
             SET enrollment_phase = "ENROLLED"
             WHERE student_id = ?
               AND enrollment_phase = "COR"'
        );

        $stmt->bind_param(
            'i',
            $studentId
        );

        $stmt->execute();

        $stmt->close();


        $message =
            'COR approved successfully.';

    } else {

        $stmt = $conn->prepare(
            'UPDATE cor_submissions
            SET
                status = "REJECTED",
                reviewed_at = NOW(),
                reviewed_by = ?,
                rejection_reason = ?
            WHERE cor_submission_id = ?'
        );

        $stmt->bind_param(
            'isi',
            $adminId,
            $rejectionReason,
            $submissionId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Reject update failed: ' . $stmt->error
            );
        }

        $stmt->close();


        $message =
            'COR rejected successfully.';
    }


    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;