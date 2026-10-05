<?php

require_role('ADMIN');

$currentPage = 'enrollment_period';


$stmt = $conn->prepare(
    'SELECT
        enrollment_status,
        semester,
        school_year,
        start_date,
        end_date
     FROM system_settings
     WHERE setting_id = 1
     LIMIT 1'
);

$stmt->execute();

$result = $stmt->get_result();

$settings = $result->fetch_assoc();

$stmt->close();


$enrollmentStatus = $settings['enrollment_status'] ?? 'CLOSED';

$isEnrollmentOpen = $enrollmentStatus === 'OPEN';

$statusClass = $isEnrollmentOpen
    ? 'admin-status-open'
    : 'admin-status-closed';

$statusMessage = $isEnrollmentOpen
    ? 'Enrollment is currently open'
    : 'Enrollment is currently closed';


$history = [];

$result = $conn->query(
    "SELECT
        h.status,
        h.changed_at,
        CASE
            WHEN u.role = 'ADMIN' THEN 'Administrator'
            ELSE COALESCE(u.account_number, 'Unknown')
        END AS changed_by_name
     FROM enrollment_status_history h
     LEFT JOIN users u
        ON u.user_id = h.changed_by
     ORDER BY h.changed_at DESC, h.history_id DESC
     LIMIT 3"
);

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMU | Enrollment Period</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/main.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>frontend/assets/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

            <section class="admin-period-card">

                <div class="admin-period-card-header">

                    <div>

                        <h2>
                            Current Enrollment Status
                        </h2>

                        <p>
                            This status controls whether enrollment is currently open.
                        </p>

                    </div>

                    <span class="admin-status-badge <?= $statusClass ?>">

                        <span class="admin-status-dot"></span>

                        <?= htmlspecialchars(
                            $enrollmentStatus,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>

                <div class="admin-enrollment-main">

                    <p>
                        <?= $isEnrollmentOpen
                            ? 'Students can now proceed with the enrollment process.'
                            : 'Students cannot proceed with the enrollment process.' ?>
                    </p>

                    <?php if ($isEnrollmentOpen): ?>

                        <button
                            type="button"
                            class="admin-close-enrollment-button"
                            id="openCloseEnrollmentModal">
                            Close the Enrollment
                        </button>

                    <?php else: ?>

                        <button
                            type="button"
                            class="admin-open-enrollment-button"
                            id="openCloseEnrollmentModal">
                            Open the Enrollment
                        </button>

                    <?php endif; ?>

                </div>


            </section>

            <section class="admin-period-info-card">

                <div class="admin-period-info-header">

                    <div>

                        <h2>
                            Enrollment Information
                        </h2>

                        <p>
                            Information about the current enrollment period.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="admin-edit-period-button"
                        id="openEditPeriodModal">

                        <i class="fa-solid fa-pen"></i>

                        Edit Period

                    </button>

                </div>


                <div class="admin-period-info-grid">

                    <div class="admin-period-info-item">

                        <span>
                            Semester
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $settings['semester'] ?? 'Not set',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <div class="admin-period-info-item">

                        <span>
                            School Year
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $settings['school_year'] ?? 'Not set',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <div class="admin-period-info-item">

                        <span>
                            Start Date
                        </span>

                        <strong>
                            <?= !empty($settings['start_date'])
                                ? date(
                                    'F j, Y',
                                    strtotime($settings['start_date'])
                                )
                                : 'Not set' ?>
                        </strong>

                    </div>


                    <div class="admin-period-info-item">

                        <span>
                            End Date
                        </span>

                        <strong>
                            <?= !empty($settings['end_date'])
                                ? date(
                                    'F j, Y',
                                    strtotime($settings['end_date'])
                                )
                                : 'Not set' ?>
                        </strong>

                    </div>

                </div>

            </section>

            <section class="admin-history-card">

                <div class="admin-history-header">

                    <div>
                        <h2>Status History</h2>

                        <p>
                            Recent changes to the enrollment status.
                        </p>
                    </div>

                </div>


                <div class="admin-history-list">

                    <?php if (empty($history)): ?>

                        <div class="admin-history-empty">
                            No enrollment status history yet.
                        </div>

                    <?php else: ?>

                        <?php foreach ($history as $item): ?>

                            <?php
                            $isOpened = $item['status'] === 'OPENED';

                            $historyStatus = $isOpened
                                ? 'Opened'
                                : 'Closed';

                            $historyClass = $isOpened
                                ? 'admin-history-opened'
                                : 'admin-history-closed';

                            $historyDate = date(
                                'F j, Y',
                                strtotime($item['changed_at'])
                            );

                            $historyTime = date(
                                'g:i A',
                                strtotime($item['changed_at'])
                            );
                            ?>

                            <div class="admin-history-item">

                                <div class="admin-history-icon <?= $historyClass ?>">

                                    <?php if ($isOpened): ?>

                                        <i class="fa-solid fa-check"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-xmark"></i>

                                    <?php endif; ?>

                                </div>


                                <div class="admin-history-content">

                                    <strong>
                                        <?= $historyStatus ?>
                                    </strong>

                                    <span>
                                        <?= $historyDate ?>
                                        ·
                                        <?= $historyTime ?>
                                    </span>

                                </div>


                                <div class="admin-history-admin">

                                    <span>Registrar</span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $item['changed_by_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>
        </div>

    </div>

</div>
<div class="admin-modal-overlay" id="enrollmentModal">

    <div
        class="admin-enrollment-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="enrollmentModalTitle">

        <h2 id="enrollmentModalTitle">
            <?= $isEnrollmentOpen
                ? 'Close Enrollment Period?'
                : 'Open Enrollment Period?' ?>
        </h2>


        <p>
            <?= $isEnrollmentOpen
                ? 'Are you sure you want to close the current enrollment period?'
                : 'Are you sure you want to open the current enrollment period?' ?>
        </p>


        <strong class="admin-modal-warning">

            <?= $isEnrollmentOpen
                ? 'Students will no longer be able to start or continue enrollment actions.'
                : 'Students will be able to start or continue enrollment actions.' ?>

        </strong>


        <div class="admin-modal-actions">

            <button
                type="button"
                class="admin-modal-cancel"
                id="cancelEnrollmentAction">

                <?= $isEnrollmentOpen
                    ? "No, Don't Close It"
                    : "No, Don't Open It" ?>

            </button>


            <button
                type="button"
                class="admin-modal-confirm"
                id="confirmEnrollmentAction">

                <?= $isEnrollmentOpen
                    ? 'Yes, Close It'
                    : 'Yes, Open It' ?>

            </button>

        </div>

    </div>

</div>

<div class="admin-modal-overlay" id="editPeriodModal">

    <div
        class="admin-enrollment-modal admin-edit-period-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editPeriodModalTitle">

        <h2 id="editPeriodModalTitle">
            Edit Enrollment Period
        </h2>

        <p>
            Update the information for the current enrollment period.
        </p>


        <div class="admin-edit-period-form">

            <div class="admin-form-group">

                <label for="editSemester">
                    Semester
                </label>

                <select id="editSemester">

                    <option
                        value="1st Semester"
                        <?= ($settings['semester'] ?? '') === '1st Semester'
                            ? 'selected'
                            : '' ?>>
                        1st Semester
                    </option>

                    <option
                        value="2nd Semester"
                        <?= ($settings['semester'] ?? '') === '2nd Semester'
                            ? 'selected'
                            : '' ?>>
                        2nd Semester
                    </option>

                    <option
                        value="Summer"
                        <?= ($settings['semester'] ?? '') === 'Summer'
                            ? 'selected'
                            : '' ?>>
                        Summer
                    </option>

                </select>

            </div>


            <div class="admin-form-group">

                <label for="editSchoolYear">
                    School Year
                </label>

                <input
                    type="text"
                    id="editSchoolYear"
                    value="<?= htmlspecialchars(
                        $settings['school_year'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="e.g. 2026 - 2027">

            </div>


            <div class="admin-form-row">

                <div class="admin-form-group">

                    <label for="editStartDate">
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="editStartDate"
                        value="<?= htmlspecialchars(
                            $settings['start_date'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

                </div>


                <div class="admin-form-group">

                    <label for="editEndDate">
                        End Date
                    </label>

                    <input
                        type="date"
                        id="editEndDate"
                        value="<?= htmlspecialchars(
                            $settings['end_date'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

                </div>

            </div>

        </div>


        <div class="admin-modal-actions">

            <button
                type="button"
                class="admin-modal-cancel"
                id="cancelEditPeriod">
                Cancel
            </button>

            <button
                type="button"
                class="admin-modal-confirm"
                id="saveEditPeriod">
                Save Changes
            </button>

        </div>

    </div>

</div>


<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>
<script>

const enrollmentModal =
    document.getElementById('enrollmentModal');

if (enrollmentModal) {

    enrollmentModal.addEventListener(
        'click',
        function (event) {

            if (event.target === enrollmentModal) {

                enrollmentModal.classList.remove('active');

            }

        }
    );

}

const openCloseEnrollmentModal =
    document.getElementById('openCloseEnrollmentModal');

if (openCloseEnrollmentModal) {

    openCloseEnrollmentModal.addEventListener(
        'click',
        function () {

            enrollmentModal.classList.add('active');

        }
    );

}

const cancelEnrollmentAction =
    document.getElementById('cancelEnrollmentAction');

if (cancelEnrollmentAction) {

    cancelEnrollmentAction.addEventListener(
        'click',
        function () {

            enrollmentModal.classList.remove('active');

        }
    );

}

const confirmEnrollmentAction =
    document.getElementById('confirmEnrollmentAction');

if (confirmEnrollmentAction) {

    confirmEnrollmentAction.addEventListener(
        'click',
        async function () {

            confirmEnrollmentAction.disabled = true;

            const formData = new FormData();

            formData.append(
                'action',
                <?= $isEnrollmentOpen ? "'CLOSED'" : "'OPEN'" ?>
            );


            try {

                const response = await fetch(
                    '<?= BASE_URL ?>backend/api/enrollment_period.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );


                const data = await response.json();


                if (!data.success) {

                    alert(data.message);

                    confirmEnrollmentAction.disabled = false;

                    return;
                }


                enrollmentModal.classList.remove('active');


                window.location.reload();

            } catch (error) {

                alert(
                    'Something went wrong. Please try again.'
                );

                confirmEnrollmentAction.disabled = false;

            }

        }
    );

}

const editPeriodModal =
    document.getElementById('editPeriodModal');

if (editPeriodModal) {

    editPeriodModal.addEventListener(
        'click',
        function (event) {

            if (event.target === editPeriodModal) {

                editPeriodModal.classList.remove('active');

            }

        }
    );

}

const openEditPeriodModal =
    document.getElementById('openEditPeriodModal');

if (openEditPeriodModal) {

    openEditPeriodModal.addEventListener(
        'click',
        function () {

            editPeriodModal.classList.add('active');

        }
    );

}

const cancelEditPeriod =
    document.getElementById('cancelEditPeriod');

if (cancelEditPeriod) {

    cancelEditPeriod.addEventListener(
        'click',
        function () {

            editPeriodModal.classList.remove('active');

        }
    );

}

const saveEditPeriod =
    document.getElementById('saveEditPeriod');

if (saveEditPeriod) {

    saveEditPeriod.addEventListener(
        'click',
        async function () {

            saveEditPeriod.disabled = true;

            const semester =
                document.getElementById('editSemester').value;

            const schoolYear =
                document.getElementById('editSchoolYear').value.trim();

            const startDate =
                document.getElementById('editStartDate').value;

            const endDate =
                document.getElementById('editEndDate').value;


            const formData = new FormData();

            formData.append('semester', semester);
            formData.append('school_year', schoolYear);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);


            try {

                const response = await fetch(
                    '<?= BASE_URL ?>backend/api/edit_enrollment_period.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );


                const data = await response.json();


                if (!data.success) {

                    alert(data.message);

                    saveEditPeriod.disabled = false;

                    return;
                }


                editPeriodModal.classList.remove('active');

                window.location.reload();


            } catch (error) {

                alert(
                    'Something went wrong. Please try again.'
                );

                saveEditPeriod.disabled = false;

            }

        }
    );

}

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Escape' &&
            enrollmentModal &&
            enrollmentModal.classList.contains('active')
        ) {

            enrollmentModal.classList.remove('active');

        }

    }
);

</script>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>
</body>

</html>

