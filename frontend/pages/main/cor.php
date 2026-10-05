<?php

require_role('STUDENT');

$currentPage = 'cor';

$studentId = (int) $_SESSION['student_id'];

$stmt = $conn->prepare(
    'SELECT
        student_number,
        first_name,
        middle_name,
        last_name,
        program,
        year_level,
        section
     FROM students
     WHERE student_id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $studentId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: ' . BASE_URL . '?page=error404');
    exit;
}

$stmt = $conn->prepare(
    'SELECT
        cor_submission_id,
        file_name,
        submitted_at,
        status,
        rejection_reason
     FROM cor_submissions
     WHERE student_id = ?
     ORDER BY submitted_at DESC
     LIMIT 1'
);

$stmt->bind_param('i', $studentId);
$stmt->execute();

$corSubmission = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>

<div class="student-page">

    <div class="student-page-header">
        <div>
            <h1>Certificate of Registration</h1>
            <p>Submit your signed Certificate of Registration for enrollment.</p>
        </div>
    </div>

    <div class="student-card">

        <div class="student-card-header">
            <div>
                <h2>Student Information</h2>
                <p>Your enrollment information</p>
            </div>
        </div>

        <div class="student-info-grid">

            <div class="student-info-item">
                <span>Student Number</span>
                <strong>
                    <?= htmlspecialchars($student['student_number']) ?>
                </strong>
            </div>

            <div class="student-info-item">
                <span>Name</span>
                <strong>
                    <?= htmlspecialchars(
                        $student['first_name'] . ' ' .
                        $student['last_name']
                    ) ?>
                </strong>
            </div>

            <div class="student-info-item">
                <span>Program</span>
                <strong>
                    <?= htmlspecialchars($student['program']) ?>
                </strong>
            </div>

            <div class="student-info-item">
                <span>Year Level</span>
                <strong>
                    <?= htmlspecialchars($student['year_level']) ?>
                </strong>
            </div>

            <div class="student-info-item">
                <span>Section</span>
                <strong>
                    <?= htmlspecialchars($student['section']) ?>
                </strong>
            </div>

        </div>

    </div>


    <div class="student-card">

        <div class="student-card-header">
            <div>
                <h2>Submit Signed COR</h2>
                <p>Upload a clear copy of your signed Certificate of Registration.</p>
            </div>
        </div>

        <?php if (!$corSubmission): ?>

            <form
                action="<?= BASE_URL ?>backend/api/submit_cor.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="cor-upload-area">

                    <label for="cor_file">
                        Select signed COR
                    </label>

                    <input
                        type="file"
                        id="cor_file"
                        name="cor_file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required
                    >

                    <p>
                        Accepted formats: JPG, PNG, PDF
                    </p>

                </div>

                <button
                    type="submit"
                    class="student-primary-button"
                >
                    Submit COR
                </button>

            </form>

        <?php else: ?>

            <div class="cor-submission-status">

                <div>
                    <span>Status</span>

                    <?php if ($corSubmission['status'] === 'PENDING'): ?>

                        <strong class="status-badge status-pending">
                            Pending Review
                        </strong>

                    <?php elseif ($corSubmission['status'] === 'APPROVED'): ?>

                        <strong class="status-badge status-approved">
                            Approved
                        </strong>

                    <?php elseif ($corSubmission['status'] === 'REJECTED'): ?>

                        <strong class="status-badge status-rejected">
                            Rejected
                        </strong>

                    <?php endif; ?>

                </div>

                <div>
                    <span>Submitted File</span>
                    <strong>
                        <?= htmlspecialchars($corSubmission['file_name']) ?>
                    </strong>
                </div>

                <div>
                    <span>Submitted On</span>
                    <strong>
                        <?= htmlspecialchars($corSubmission['submitted_at']) ?>
                    </strong>
                </div>

                <?php if (
                    $corSubmission['status'] === 'REJECTED' &&
                    !empty($corSubmission['rejection_reason'])
                ): ?>

                    <div class="cor-rejection-message">

                        <span>Reason for Rejection</span>

                        <p>
                            <?= htmlspecialchars(
                                $corSubmission['rejection_reason']
                            ) ?>
                        </p>

                    </div>

                    <a
                        href="<?= BASE_URL ?>?page=cor"
                        class="student-primary-button"
                    >
                        Submit Corrected COR
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>