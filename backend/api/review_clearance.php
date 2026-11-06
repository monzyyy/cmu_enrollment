
<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, bool $success, string $message): void
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Method not allowed.');
}

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        (string) ($_POST['csrf_token'] ?? '')
    )
) {
    respond(403, false, 'Invalid security token. Refresh the page.');
}

$id = filter_var(
    $_POST['submission_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$action = strtoupper(trim((string) ($_POST['action'] ?? '')));
$reason = trim((string) ($_POST['rejection_reason'] ?? ''));

if (!$id || !in_array($action, ['APPROVE', 'REJECT'], true)) {
    respond(400, false, 'Invalid review request.');
}

if ($action === 'REJECT' && $reason === '') {
    respond(400, false, 'Please provide a rejection reason.');
}

if (strlen($reason) > 2000) {
    respond(400, false, 'Rejection reason is too long.');
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT
            c.student_id,
            c.status,
            c.semester,
            c.school_year,
            s.enrollment_phase
        FROM clearance_submissions c
        INNER JOIN students s
            ON s.student_id = c.student_id
        WHERE c.clearance_id = ?
        FOR UPDATE
    ");

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $submission = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$submission) {
        throw new RuntimeException('Submission not found.');
    }

    if ($submission['status'] !== 'PENDING') {
        throw new RuntimeException('This submission has already been reviewed.');
    }

     
    $periodStmt = $conn->prepare("
        SELECT semester, school_year
        FROM system_settings
        WHERE enrollment_status = 'OPEN'
        ORDER BY setting_id DESC
        LIMIT 1
    ");

    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();

    if (
        !$period ||
        $period['semester'] !== $submission['semester'] ||
        $period['school_year'] !== $submission['school_year']
    ) {
        throw new RuntimeException(
            'This submission does not belong to the active enrollment period.'
        );
    }

    if ($submission['enrollment_phase'] !== 'EVALUATION') {
        throw new RuntimeException(
            'Student is not currently eligible for clearance review.'
        );
    }

    $newStatus = $action === 'APPROVE'
        ? 'APPROVED'
        : 'REJECTED';

    $reviewerId = $_SESSION['admin_id'] ?? null;

    $stmt = $conn->prepare("
        UPDATE clearance_submissions
        SET status = ?,
            rejection_reason = ?,
            reviewed_by = ?,
            reviewed_at = NOW()
        WHERE clearance_id = ?
          AND status = 'PENDING'
    ");

    $storedReason = $action === 'REJECT' ? $reason : null;

    $stmt->bind_param(
        'ssii',
        $newStatus,
        $storedReason,
        $reviewerId,
        $id
    );

    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Unable to update submission.');
    }

    $stmt->close();

    if ($action === 'APPROVE') {
        $studentId = (int) $submission['student_id'];

        $stmt = $conn->prepare("
            UPDATE students
            SET enrollment_phase = 'CLEARANCE',
                enrollment_status = 'Clearance'
            WHERE student_id = ?
              AND enrollment_phase = 'EVALUATION'
        ");

        $stmt->bind_param('i', $studentId);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException(
                'Unable to advance student enrollment.'
            );
        }

        $stmt->close();
    }

    $conn->commit();

    respond(
        200,
        true,
        $action === 'APPROVE'
            ? 'Clearance approved. COR is now unlocked.'
            : 'Clearance rejected. The student may resubmit.'
    );

} catch (Throwable $e) {
    $conn->rollback();

    error_log('Clearance review error: ' . $e->getMessage());

    respond(
        400,
        false,
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to review clearance.'
    );
}
