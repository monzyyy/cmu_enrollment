<?php

require_role('ADMIN');

$currentPage = 'cor_submissions';


/* SEARCH */

$search = trim($_GET['search'] ?? '');


/* STATUS FILTER */

$statusFilter = $_GET['status'] ?? '';

$allowedStatuses = [
    'PENDING',
    'APPROVED',
    'REJECTED'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}


/* PAGINATION */

$studentsPerPage = 5;

$currentPageNumber = max(
    1,
    (int) ($_GET['cor_page'] ?? 1)
);


/* COUNT SUBMISSIONS */

$countSql = '
    SELECT COUNT(*) AS total
    FROM cor_submissions c
    INNER JOIN students s
        ON s.student_id = c.student_id
    WHERE 1 = 1
';

$countTypes = '';
$countValues = [];


if ($search !== '') {

    $countSql .= '
        AND (
            s.student_number LIKE ?
            OR s.first_name LIKE ?
            OR s.middle_name LIKE ?
            OR s.last_name LIKE ?
            OR s.program LIKE ?
            OR s.section LIKE ?
        )
    ';

    $searchValue = '%' . $search . '%';

    $countTypes .= 'ssssss';

    $countValues = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];
}


if ($statusFilter !== '') {

    $countSql .= '
        AND c.status = ?
    ';

    $countTypes .= 's';
    $countValues[] = $statusFilter;
}


$countStmt = $conn->prepare($countSql);


if (!empty($countValues)) {
    $countStmt->bind_param($countTypes, ...$countValues);
}


$countStmt->execute();

$countResult = $countStmt->get_result()->fetch_assoc();

$totalSubmissions = (int) ($countResult['total'] ?? 0);

$countStmt->close();


/* TOTAL PAGES */

$totalPages = max(
    1,
    (int) ceil($totalSubmissions / $studentsPerPage)
);

$currentPageNumber = min(
    $currentPageNumber,
    $totalPages
);

$offset = (
    $currentPageNumber - 1
) * $studentsPerPage;


/* GET SUBMISSIONS */

$sql = '
    SELECT
        c.cor_submission_id,
        c.student_id,
        c.file_name,
        c.file_path,
        c.submitted_at,
        c.status,

        s.student_number,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.program,
        s.year_level,
        s.section

    FROM cor_submissions c

    INNER JOIN students s
        ON s.student_id = c.student_id

    WHERE 1 = 1
';

$types = '';
$values = [];


if ($search !== '') {

    $sql .= '
        AND (
            s.student_number LIKE ?
            OR s.first_name LIKE ?
            OR s.middle_name LIKE ?
            OR s.last_name LIKE ?
            OR s.program LIKE ?
            OR s.section LIKE ?
        )
    ';

    $types .= 'ssssss';

    $values = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];
}


if ($statusFilter !== '') {

    $sql .= '
        AND c.status = ?
    ';

    $types .= 's';
    $values[] = $statusFilter;
}


$sql .= '
    ORDER BY c.submitted_at DESC
    LIMIT ? OFFSET ?
';

$types .= 'ii';

$values[] = $studentsPerPage;
$values[] = $offset;


$stmt = $conn->prepare($sql);

$stmt->bind_param($types, ...$values);

$stmt->execute();

$submissions = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/* STATUS COUNTS */

$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;

$statusResult = $conn->query(
    "SELECT
        SUM(status = 'PENDING') AS pending_count,
        SUM(status = 'APPROVED') AS approved_count,
        SUM(status = 'REJECTED') AS rejected_count
     FROM cor_submissions"
);

if ($statusResult) {

    $statusCounts = $statusResult->fetch_assoc();

    $pendingCount = (int) ($statusCounts['pending_count'] ?? 0);
    $approvedCount = (int) ($statusCounts['approved_count'] ?? 0);
    $rejectedCount = (int) ($statusCounts['rejected_count'] ?? 0);
}


/* STATUS LABEL */

function cor_status_label(string $status): string
{
    return match ($status) {
        'PENDING' => 'Pending',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
        default => 'Unknown',
    };
}


/* STATUS CLASS */

function cor_status_class(string $status): string
{
    return match ($status) {
        'PENDING' => 'admin-cor-status-pending',
        'APPROVED' => 'admin-cor-status-approved',
        'REJECTED' => 'admin-cor-status-rejected',
        default => '',
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>CMU | COR Submissions</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/main.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>frontend/assets/css/admin.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>

<div class="layout">


    <div class="imglogo">
        <span>CMU</span>
    </div>


    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>


    <div class="overlay" id="overlay"></div>


    <div class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>


        <div class="content">


            <!-- PAGE HEADER -->

            <section class="admin-cor-header">

                <div>

                    <h1>COR Submissions</h1>

                    <p>
                        Review and manage submitted Certificates of Registration.
                    </p>

                </div>

            </section>


            <!-- SUMMARY -->

            <section class="admin-cor-summary">

                <div class="admin-cor-summary-card">

                    <span>Pending</span>

                    <strong>
                        <?= $pendingCount ?>
                    </strong>

                </div>


                <div class="admin-cor-summary-card">

                    <span>Approved</span>

                    <strong>
                        <?= $approvedCount ?>
                    </strong>

                </div>


                <div class="admin-cor-summary-card">

                    <span>Rejected</span>

                    <strong>
                        <?= $rejectedCount ?>
                    </strong>

                </div>

            </section>


            <!-- TABLE CARD -->

            <section class="admin-cor-card">


                <div class="admin-cor-card-header">

                    <div>

                        <h2>COR Submission List</h2>

                        <p>
                            View submitted COR files and their current status.
                        </p>

                    </div>


                    <!-- SEARCH -->

                    <form
                        method="GET"
                        action="<?= e(BASE_URL) ?>"
                        class="admin-cor-search"
                    >

                        <input
                            type="hidden"
                            name="page"
                            value="cor_submissions"
                        >

                        <input
                            type="hidden"
                            name="status"
                            value="<?= e($statusFilter) ?>"
                        >


                        <div class="admin-cor-search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Search student..."
                                autocomplete="off"
                            >

                        </div>

                    </form>

                </div>


                <!-- FILTER -->

                <div class="admin-cor-toolbar">

                    <form
                        method="GET"
                        action="<?= e(BASE_URL) ?>"
                        class="admin-cor-filter"
                    >

                        <input
                            type="hidden"
                            name="page"
                            value="cor_submissions"
                        >

                        <input
                            type="hidden"
                            name="search"
                            value="<?= e($search) ?>"
                        >

                        <label for="corStatus">
                            Status
                        </label>

                        <select
                            id="corStatus"
                            name="status"
                            onchange="this.form.submit()"
                        >

                            <option value="">
                                All
                            </option>

                            <option
                                value="PENDING"
                                <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>
                            >
                                Pending
                            </option>

                            <option
                                value="APPROVED"
                                <?= $statusFilter === 'APPROVED' ? 'selected' : '' ?>
                            >
                                Approved
                            </option>

                            <option
                                value="REJECTED"
                                <?= $statusFilter === 'REJECTED' ? 'selected' : '' ?>
                            >
                                Rejected
                            </option>

                        </select>

                    </form>

                </div>


                <!-- RESULT COUNT -->

                <div class="admin-cor-table-info">

                    <?php if ($totalSubmissions > 0): ?>

                        <?php

                        $displayStart = $offset + 1;

                        $displayEnd = min(
                            $offset + $studentsPerPage,
                            $totalSubmissions
                        );

                        ?>

                        <span>
                            Showing <?= $displayStart ?>–<?= $displayEnd ?>
                            of <?= $totalSubmissions ?> submissions
                        </span>

                    <?php else: ?>

                        <span>
                            No COR submissions found
                        </span>

                    <?php endif; ?>

                </div>


                <!-- TABLE -->

                <div class="admin-cor-table-wrapper">

                    <table class="admin-cor-table">

                        <thead>

                            <tr>

                                <th>
                                    Student Number
                                </th>

                                <th>
                                    Student Name
                                </th>

                                <th>
                                    Program
                                </th>

                                <th>
                                    Year
                                </th>

                                <th>
                                    Submitted
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($submissions)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="admin-cor-empty"
                                >
                                    No COR submissions found.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($submissions as $submission): ?>

                                <?php

                                $fullName = trim(
                                    $submission['first_name']
                                    . ' '
                                    . ($submission['middle_name'] ?? '')
                                    . ' '
                                    . $submission['last_name']
                                );

                                ?>


                                <tr>


                                    <td>

                                        <?= e(
                                            $submission['student_number']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e($fullName) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $submission['program']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= (int) $submission['year_level'] ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'M j, Y',
                                            strtotime(
                                                $submission['submitted_at']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="admin-cor-status <?= cor_status_class($submission['status']) ?>"
                                        >

                                            <?= cor_status_label(
                                                $submission['status']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="admin-cor-view-button"
                                            data-submission-id="<?= (int) $submission['cor_submission_id'] ?>"
                                            data-student-number="<?= e($submission['student_number']) ?>"
                                            data-student-name="<?= e($fullName) ?>"
                                            data-program="<?= e($submission['program']) ?>"
                                            data-year="<?= (int) $submission['year_level'] ?>"
                                            data-section="<?= e($submission['section']) ?>"
                                            data-submitted="<?= e(date('M j, Y h:i A', strtotime($submission['submitted_at']))) ?>"
                                            data-status="<?= e($submission['status']) ?>"
                                            data-file-name="<?= e($submission['file_name']) ?>"
                                            data-file-path="<?= e($submission['file_path']) ?>"
                                        >
                                            View
                                        </button>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- PAGINATION -->

                <?php if ($totalPages > 1): ?>

                    <div class="admin-cor-pagination">


                        <a
                            href="<?= e(
                                BASE_URL
                                . '?page=cor_submissions'
                                . '&search=' . urlencode($search)
                                . '&status=' . urlencode($statusFilter)
                                . '&cor_page='
                                . ($currentPageNumber - 1)
                            ) ?>"
                            class="<?= $currentPageNumber <= 1 ? 'disabled' : '' ?>"
                        >

                            <i class="fa-solid fa-chevron-left"></i>

                            Previous

                        </a>


                        <div class="admin-cor-page-numbers">

                            <?php for (
                                $pageNumber = 1;
                                $pageNumber <= $totalPages;
                                $pageNumber++
                            ): ?>

                                <a
                                    href="<?= e(
                                        BASE_URL
                                        . '?page=cor_submissions'
                                        . '&search=' . urlencode($search)
                                        . '&status=' . urlencode($statusFilter)
                                        . '&cor_page='
                                        . $pageNumber
                                    ) ?>"
                                    class="<?= $pageNumber === $currentPageNumber ? 'active' : '' ?>"
                                >

                                    <?= $pageNumber ?>

                                </a>

                            <?php endfor; ?>

                        </div>


                        <a
                            href="<?= e(
                                BASE_URL
                                . '?page=cor_submissions'
                                . '&search=' . urlencode($search)
                                . '&status=' . urlencode($statusFilter)
                                . '&cor_page='
                                . ($currentPageNumber + 1)
                            ) ?>"
                            class="<?= $currentPageNumber >= $totalPages ? 'disabled' : '' ?>"
                        >

                            Next

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>


                    </div>

                <?php endif; ?>


            </section>


        </div>

    </div>

</div>

</div>

<!-- COR VIEW MODAL -->

<div
    class="admin-cor-modal"
    id="corViewModal"
    aria-hidden="true"
>

    <div class="admin-cor-modal-overlay" id="corModalOverlay"></div>


    <div
        class="admin-cor-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="corModalTitle"
    >

        <!-- HEADER -->

        <div class="admin-cor-modal-header">

            <div>

                <h2 id="corModalTitle">
                    COR Submission
                </h2>

                <p>
                    Review the submitted Certificate of Registration.
                </p>

            </div>


            <button
                type="button"
                class="admin-cor-modal-close"
                id="corModalClose"
                aria-label="Close"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <!-- STUDENT INFORMATION -->

        <div class="admin-cor-modal-info">

            <div>
                <small>Student Number</small>
                <strong id="modalStudentNumber">-</strong>
            </div>

            <div>
                <small>Student Name</small>
                <strong id="modalStudentName">-</strong>
            </div>

            <div>
                <small>Program</small>
                <strong id="modalProgram">-</strong>
            </div>

            <div>
                <small>Year & Section</small>
                <strong id="modalYearSection">-</strong>
            </div>

            <div>
                <small>Submitted</small>
                <strong id="modalSubmitted">-</strong>
            </div>

            <div>
                <small>Status</small>
                <strong id="modalStatus">-</strong>
            </div>

            <div
                class="admin-cor-review-message"
                id="corReviewMessage">
            </div>

        </div>


        <!-- FILE -->

        <div class="admin-cor-modal-file">

            <div class="admin-cor-modal-file-header">

                <div>

                    <h3>
                        Submitted COR
                    </h3>

                    <span id="modalFileName">
                        -
                    </span>

                </div>

            </div>


            <div
                class="admin-cor-file-preview"
                id="corFilePreview"
            >

                <div class="admin-cor-file-placeholder">

                    <i class="fa-solid fa-file"></i>

                    <p>
                        Loading document...
                    </p>

                </div>

            </div>

            <div class="admin-cor-rejection-area" id="corRejectionArea">

                <label for="corRejectionReason">
                    Rejection Reason
                </label>

                <textarea
                    id="corRejectionReason"
                    rows="4"
                    placeholder="Enter the reason for rejecting this COR..."
                ></textarea>

            </div>

            <div class="admin-cor-modal-actions">

                <button
                    type="button"
                    class="admin-cor-reject-button"
                    id="corRejectButton"
                >
                    <i class="fa-solid fa-xmark"></i>
                    Reject COR
                </button>

                <button
                    type="button"
                    class="admin-cor-approve-button"
                    id="corApproveButton"
                >
                    <i class="fa-solid fa-check"></i>
                    Approve COR
                </button>

            </div>

        </div>


    </div>

</div>

<div
    class="admin-cor-image-modal"
    id="corImageModal"
    aria-hidden="true"
>

    <div
        class="admin-cor-image-overlay"
        id="corImageModalOverlay"
    ></div>

    <div class="admin-cor-image-content">

        <button
            type="button"
            class="admin-cor-image-close"
            id="corImageModalClose"
            aria-label="Close image preview"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <img
            id="corMaximizedImage"
            src=""
            alt="Maximized COR"
        >

    </div>

</div>


<script
    src="<?= BASE_URL ?>frontend/assets/js/custom.js"
></script>

<script>

const corModalClose =
    document.getElementById('corModalClose');

const corModalOverlay =
    document.getElementById('corModalOverlay');

const corViewModal =
    document.getElementById('corViewModal');

const viewButtons =
    document.querySelectorAll('.admin-cor-view-button');

const modalFileName =
    document.getElementById('modalFileName');

const corApproveButton =
    document.getElementById('corApproveButton');

const corRejectButton =
    document.getElementById('corRejectButton');

let currentSubmissionId = null;

corRejectButton.addEventListener(
    'click',
    function () {

        const submissionId = currentSubmissionId;

        const rejectionReason =
            document.getElementById(
                'corRejectionReason'
            ).value.trim();


        if (rejectionReason === '') {

            alert(
                'Please enter a rejection reason.'
            );

            return;
        }


        const formData =
            new FormData();

        formData.append(
            'submission_id',
            submissionId
        );

        formData.append(
            'action',
            'REJECT'
        );

        formData.append(
            'rejection_reason',
            rejectionReason
        );


        fetch(
            '<?= BASE_URL ?>backend/api/review_cor.php',
            {
                method: 'POST',
                body: formData
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {

            if (!data.success) {

                alert(data.message);

                return;
            }


            alert(data.message);

            location.reload();

        })
        .catch(function () {

            alert(
                'Something went wrong while rejecting the COR.'
            );

        });

    }
);



viewButtons.forEach(function (button) {

    button.addEventListener('click', function () {

        currentSubmissionId = Number(this.dataset.submissionId);
        document.getElementById('corRejectionReason').value = '';

        const submissionId =
            this.dataset.submissionId;

        const fileName =
            this.dataset.fileName;

        const fileExtension =
            fileName
                .split('.')
                .pop()
                .toLowerCase();


        const corFilePreview =
            document.getElementById('corFilePreview');

        corFilePreview.innerHTML = '';


        if (
            fileExtension === 'png' ||
            fileExtension === 'jpg' ||
            fileExtension === 'jpeg'
        ) {

            const image =
                document.createElement('img');

            image.src =
                '<?= BASE_URL ?>backend/api/view_cor.php?id=' +
                submissionId;

            image.alt =
                'Submitted COR';

            image.className =
                'admin-cor-preview-image';

            image.style.cursor = 'zoom-in';

            image.addEventListener('click', function () {

                const corImageModal =
                    document.getElementById('corImageModal');

                const corMaximizedImage =
                    document.getElementById('corMaximizedImage');

                corMaximizedImage.src = this.src;

                corImageModal.classList.add('show');

                corImageModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

            });

            corFilePreview.appendChild(image);

        }

        const studentNumber =
            this.dataset.studentNumber;

        const studentName =
            this.dataset.studentName;

        const program =
            this.dataset.program;

        const year =
            this.dataset.year;

        const section =
            this.dataset.section;

        const submitted =
            this.dataset.submitted;

        const status =
            this.dataset.status;

        const corReviewMessage =
            document.getElementById('corReviewMessage');

        if (status === 'PENDING') {

            corReviewMessage.textContent =
                'This COR is waiting for review.';

            corApproveButton.style.display = '';
            corRejectButton.style.display = '';

        } else if (status === 'APPROVED') {

            corReviewMessage.textContent =
                'This COR has already been approved.';

            corApproveButton.style.display = 'none';
            corRejectButton.style.display = 'none';

        } else if (status === 'REJECTED') {

            corReviewMessage.textContent =
                'This COR was rejected. The student may submit a corrected copy.';

            corApproveButton.style.display = 'none';
            corRejectButton.style.display = 'none';

        } else {

            corReviewMessage.textContent = '';

            corApproveButton.style.display = 'none';
            corRejectButton.style.display = 'none';

        }

        
        document.getElementById(
            'modalFileName'
        ).textContent = fileName;

        document.getElementById(
            'modalStudentNumber'
        ).textContent = studentNumber;


        document.getElementById(
            'modalStudentName'
        ).textContent = studentName;


        document.getElementById(
            'modalProgram'
        ).textContent = program;


        document.getElementById(
            'modalYearSection'
        ).textContent =
            year + ' Year • ' + section;


        document.getElementById(
            'modalSubmitted'
        ).textContent = submitted;


        document.getElementById(
            'modalStatus'
        ).textContent = status;


        corViewModal.classList.add('show');

        corViewModal.setAttribute(
            'aria-hidden',
            'false'
        );



    });

});

function closeCorModal() {

    corViewModal.classList.remove('show');

    corViewModal.setAttribute(
        'aria-hidden',
        'true'
    );

}

const corImageModal =
    document.getElementById('corImageModal');

const corImageModalClose =
    document.getElementById('corImageModalClose');

const corImageModalOverlay =
    document.getElementById('corImageModalOverlay');

function closeCorImageModal() {

    corImageModal.classList.remove('show');

    corImageModal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.getElementById(
        'corMaximizedImage'
    ).src = '';

}

corImageModalClose.addEventListener(
    'click',
    closeCorImageModal
);

corImageModalOverlay.addEventListener(
    'click',
    closeCorImageModal
);


corModalClose.addEventListener(
    'click',
    closeCorModal
);


corModalOverlay.addEventListener(
    'click',
    closeCorModal
);

corApproveButton.addEventListener(
    'click',
    function () {

        const submissionId = currentSubmissionId;


        const formData =
            new FormData();

        formData.append('submission_id', submissionId);
        formData.append('action', 'APPROVE');


        fetch(
            '<?= BASE_URL ?>backend/api/review_cor.php',
            {
                method: 'POST',
                body: formData
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {

            if (!data.success) {

                alert(data.message);

                return;

            }


            alert(data.message);

            location.reload();

        })
        .catch(function () {

            alert(
                'Something went wrong while approving the COR.'
            );

        });

    }
);

</script>

</body>

</html>