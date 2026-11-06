<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

require_role('ADMIN');

$currentPage = 'student_management';
if (empty($_SESSION['masterlist_import_csrf'])) { $_SESSION['masterlist_import_csrf'] = bin2hex(random_bytes(32)); }
if (empty($_SESSION['registration_review_csrf'])) { $_SESSION['registration_review_csrf'] = bin2hex(random_bytes(32)); }

$studentsPerPage = 5;

$currentStudentPage = max(
1,
(int) ($_GET['student_page'] ?? 1)
);

$search = trim($_GET['search'] ?? '');

 

$result = $conn->query(
'SELECT COUNT(*) AS total
     FROM students'
);

$totalStudents = 0;

if ($result) {
$row = $result->fetch_assoc();
$totalStudents = (int) ($row['total'] ?? 0);
}

 

if ($search !== '') {

$countStmt = $conn->prepare(
'SELECT COUNT(*) AS total
         FROM students
         WHERE
            student_number LIKE ?
            OR first_name LIKE ?
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR program LIKE ?
            OR section LIKE ?
            OR email LIKE ?'
);

$searchValue = '%' . $search . '%';

$countStmt->bind_param(
'sssssss',
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue
);

} else {

$countStmt = $conn->prepare(
'SELECT COUNT(*) AS total
         FROM students'
);
}

$countStmt->execute();

$countResult =
$countStmt->get_result()->fetch_assoc();

$totalFilteredStudents =
(int) ($countResult['total'] ?? 0);

$countStmt->close();

 

$totalStudentPages = max(
1,
(int) ceil(
$totalFilteredStudents /
$studentsPerPage
)
);

$currentStudentPage = min(
$currentStudentPage,
$totalStudentPages
);

$studentOffset =
($currentStudentPage - 1) *
$studentsPerPage;

 

if ($search !== '') {

$stmt = $conn->prepare(
'SELECT
            s.student_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            s.enrollment_phase,
            u.is_active
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         WHERE
            s.student_number LIKE ?
            OR s.first_name LIKE ?
            OR s.middle_name LIKE ?
            OR s.last_name LIKE ?
            OR s.program LIKE ?
            OR s.section LIKE ?
            OR s.email LIKE ?
         ORDER BY
            s.last_name ASC,
            s.first_name ASC
         LIMIT ? OFFSET ?'
);

$stmt->bind_param(
'sssssssii',
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$searchValue,
$studentsPerPage,
$studentOffset
);

} else {

$stmt = $conn->prepare(
'SELECT
            s.student_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            s.enrollment_phase,
            u.is_active
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         ORDER BY
            s.last_name ASC,
            s.first_name ASC
         LIMIT ? OFFSET ?'
);

$stmt->bind_param(
'ii',
$studentsPerPage,
$studentOffset
);
}

$stmt->execute();

$students =
$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

 
$registrationRequests = [];
$registrationRequestError = false;
$registrationSql = "SELECT r.request_id, r.status, r.requested_at, r.reviewed_at,
                           m.student_number, m.first_name, m.middle_name, m.last_name,
                           m.program, m.year_level, m.section, m.date_of_birth
                    FROM student_registration_requests r
                    INNER JOIN student_masterlist m ON m.masterlist_id = r.masterlist_id
                    ORDER BY CASE r.status WHEN 'PENDING' THEN 0 WHEN 'REJECTED' THEN 1 ELSE 2 END,
                             r.requested_at DESC";
$registrationResult = $conn->query($registrationSql);
if ($registrationResult) {
$registrationRequests = $registrationResult->fetch_all(MYSQLI_ASSOC);
$registrationResult->free();
} else {
$registrationRequestError = true;
error_log('Student Management registration requests query failed: ' . $conn->error);
}
$pendingRegistrationCount = count(array_filter($registrationRequests, static fn($r) => $r['status'] === 'PENDING'));

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Management</title>

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

    <!-- Page-specific alignment: match the other admin page headings without changing shared CSS. -->
    <style>
        .student-management-page-heading {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
        }
        .student-management-page-heading > div:first-child { min-width: 0; }
        .student-management-page-heading h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.3;
            margin: 0 0 5px;
            letter-spacing: -0.025em;
        }
        .student-management-page-heading p {
            font-family: 'Manrope', sans-serif;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.5;
            margin: 0;
        }
        .student-management-page-heading .admin-primary-button {
            flex-shrink: 0;
            margin: 0;
        }
        @media (max-width: 600px) {
            .student-management-page-heading { flex-wrap: wrap; }
        }

        .student-management-tabs { display:flex; gap:8px; margin:22px 0 18px; flex-wrap:wrap; border-bottom:1px solid #dce4ef; }
        .student-management-tab { border:0; background:transparent; padding:12px 15px; font:700 13px Manrope,sans-serif; color:#53657d; cursor:pointer; border-bottom:3px solid transparent; }
        .student-management-tab.is-active { color:#1454a2; border-bottom-color:#1454a2; }
        .student-management-tab .request-count { display:inline-block; margin-left:5px; padding:2px 7px; border-radius:20px; background:#fff0d6; color:#915c0b; font-size:11px; }
        .registration-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .registration-toolbar select { padding:9px 12px; border:1px solid #d4deeb; border-radius:8px; background:white; color:#173153; }
        .registration-status { font-weight:700; font-size:12px; }
        .registration-status.pending { color:#976000; } .registration-status.approved { color:#147c45; } .registration-status.rejected { color:#ae3030; }
        .registration-review-button { border:1px solid #c6d8ed; border-radius:7px; background:#eef5ff; color:#174c88; font-weight:700; padding:8px 13px; cursor:pointer; }
        .registration-review-button:hover { background:#dcecff; }
        .registration-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .registration-detail { min-width:0; }
        .registration-detail span { display:block; color:#68788c; font-size:12px; margin-bottom:4px; }
        .registration-detail strong { display:block; overflow-wrap:anywhere; font-size:14px; }
        @media(max-width:500px) { .registration-detail-grid { grid-template-columns:1fr; } }
        [hidden] { display:none !important; }
    </style>

</head>

<body>

<div class="layout">

    <div class="imglogo">
        <span>CMU</span>
    </div>

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

    <main class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>

        <div class="content">

            <!-- PAGE HEADING -->

            <div class="page-heading student-management-page-heading">

                <div>

                    <h1>Student Management</h1>

                    <p>
                        Manage student accounts and enrollment information.
                    </p>

                </div>

                <div>

                    <button type="button" class="admin-primary-button" id="importMasterlistButton" style="margin-right:10px">
                        <i class="fa-solid fa-file-import"></i> Import Masterlist
                    </button>
                    <button
                        type="button"
                        class="admin-primary-button"
                        id="addStudentButton"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        Add Student
                    </button>

                </div>

            </div>


            <div class="student-management-tabs" role="tablist" aria-label="Student management sections">
                <button type="button" class="student-management-tab is-active" id="studentsTab" role="tab" aria-selected="true" aria-controls="studentsPanel">Students</button>
                <button type="button" class="student-management-tab" id="registrationTab" role="tab" aria-selected="false" aria-controls="registrationPanel">Registration Requests<?php if ($pendingRegistrationCount > 0): ?><span class="request-count"><?= (int) $pendingRegistrationCount ?></span><?php endif; ?></button>
            </div>

            <section id="studentsPanel" role="tabpanel" aria-labelledby="studentsTab">
            <!-- STUDENT SUMMARY -->

            <div class="admin-summary-card">

                <div class="admin-summary-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>

                    <span>Total Students</span>

                    <strong>
                        <?= e($totalStudents) ?>
                    </strong>

                </div>

            </div>


            <!-- SEARCH -->

            <form id="studentManagementSearchForm" method="GET" action="<?= e(BASE_URL) ?>">
                <input type="hidden" name="page" value="student_management">
                <div class="admin-cor-search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="studentManagementSearch"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Search student..."
                                autocomplete="off"
                            >

                </div>
            </form>


            <!-- STUDENT TABLE -->

            <div class="admin-table-card" id="studentManagementResults" aria-live="polite">

                <div class="admin-student-enrollment-table-info">

                    <?php if ($totalFilteredStudents > 0): ?>

                        <?php

$displayStart =
$studentOffset + 1;

$displayEnd = min(
$studentOffset + $studentsPerPage,
$totalFilteredStudents
);

?>

                        <span>
                            Showing <?= $displayStart ?>–<?= $displayEnd ?>
                            of <?= $totalFilteredStudents ?> students
                        </span>

                    <?php else: ?>

                        <span>
                            No students found
                        </span>

                    <?php endif; ?>

                </div>

                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>Student Number</th>

                                <th>Student Name</th>

                                <th>Program</th>

                                <th>Year</th>

                                <th>Section</th>

                                <th>Email</th>

                                <th>Status</th>

                                <th>Enrollment</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody id="studentTableBody">

                        <?php if (empty($students)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="admin-empty-state"
                                >
                                    No students found.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($students as $student): ?>

                                <?php

$studentName = trim(
($student['first_name'] ?? '') . ' ' .
($student['middle_name'] ?? '') . ' ' .
($student['last_name'] ?? '')
);

$enrollmentPhase =
$student['enrollment_phase'] ?? 'NOT_STARTED';

$isActive =
(int) ($student['is_active'] ?? 0) === 1;

?>

                                <tr>

                                    <td>
                                        <?= e($student['student_number']) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= e($studentName) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e($student['program']) ?>
                                    </td>

                                    <td>
                                        <?= e($student['year_level']) ?>
                                    </td>

                                    <td>
                                        <?= e($student['section'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($student['email']) ?>
                                    </td>

                                    <td>

                                        <?php if ($isActive): ?>

                                            <span class="admin-status-badge is-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="admin-status-badge is-inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="admin-status-badge">
                                            <?= e($enrollmentPhase) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <div class="student-management-actions">

                                            <button
                                                type="button"
                                                class="student-management-edit-button"
                                                data-student-id="<?= e($student['student_id']) ?>"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                                Edit
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if ($totalStudentPages > 1): ?>

                    <div class="admin-student-enrollment-pagination">

                        <a
                            href="<?= e(
BASE_URL
. '?page=student_management'
. '&search=' . urlencode($search)
. '&student_page='
. ($currentStudentPage - 1)
) ?>"
                            class="<?= $currentStudentPage <= 1 ? 'disabled' : '' ?>"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                            Previous
                        </a>


                        <div class="admin-student-enrollment-page-numbers">

                            <?php for (
$pageNumber = 1;
$pageNumber <= $totalStudentPages;
$pageNumber++
): ?>

                                <a
                                    href="<?= e(
BASE_URL
. '?page=student_management'
. '&search=' . urlencode($search)
. '&student_page='
. $pageNumber
) ?>"
                                    class="<?= $pageNumber === $currentStudentPage ? 'active' : '' ?>"
                                >
                                    <?= $pageNumber ?>
                                </a>

                            <?php endfor; ?>

                        </div>


                        <a
                            href="<?= e(
BASE_URL
. '?page=student_management'
. '&search=' . urlencode($search)
. '&student_page='
. ($currentStudentPage + 1)
) ?>"
                            class="<?= $currentStudentPage >= $totalStudentPages ? 'disabled' : '' ?>"
                        >
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>

                <?php endif; ?>

            </div>
            </section>

            <section id="registrationPanel" role="tabpanel" aria-labelledby="registrationTab" hidden>
                <div class="registration-toolbar">
                    <div>
                        <h2 style="font-size:18px;font-weight:800;margin:0 0 5px">Registration Requests</h2>
                        <p style="font-size:12px;margin:0">Review student registration requests before activating accounts.</p>
                    </div>
                    <label for="registrationStatusFilter" style="font-size:13px;font-weight:600">Status
                        <select id="registrationStatusFilter">
                            <option value="ALL">All Requests</option>
                            <option value="PENDING" selected>Pending</option>
                            <option value="APPROVED">Approved</option>
                            <option value="REJECTED">Rejected</option>
                        </select>
                    </label>
                </div>
                <div class="admin-table-card">
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead><tr><th>Student Number</th><th>Student Name</th><th>Program</th><th>Year</th><th>Requested At</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody id="registrationRequestsBody">
                                <?php if ($registrationRequestError): ?>
                                    <tr><td colspan="7" class="admin-empty-state">Unable to load registration requests. Check the server logs.</td></tr>
                                <?php elseif (!$registrationRequests): ?>
                                    <tr><td colspan="7" class="admin-empty-state">No registration requests found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($registrationRequests as $request): ?>
                                        <?php $requestName = trim(($request['first_name'] ?? '') . ' ' . ($request['middle_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?>
                                        <tr data-registration-status="<?= e($request['status']) ?>">
                                            <td><?= e($request['student_number']) ?></td>
                                            <td><strong><?= e($requestName) ?></strong></td>
                                            <td><?= e($request['program'] ?: '-') ?></td>
                                            <td><?= e($request['year_level'] ?: '-') ?></td>
                                            <td><?= e($request['requested_at']) ?></td>
                                            <td><span class="registration-status <?= e(strtolower($request['status'])) ?>"><?= e(ucfirst(strtolower($request['status']))) ?></span></td>
                                            <td><button type="button" class="registration-review-button"
                                                data-request-id="<?= e($request['request_id']) ?>"
                                                data-student-number="<?= e($request['student_number']) ?>"
                                                data-student-name="<?= e($requestName) ?>"
                                                data-program="<?= e($request['program'] ?? '') ?>"
                                                data-year="<?= e($request['year_level'] ?? '') ?>"
                                                data-section="<?= e($request['section'] ?? '') ?>"
                                                data-dob="<?= e($request['date_of_birth'] ?? '') ?>"
                                                data-requested="<?= e($request['requested_at']) ?>"
                                                data-status="<?= e($request['status']) ?>">Review</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="registrationFilteredEmpty" hidden><td colspan="7" class="admin-empty-state">No requests match this status.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>

    </main>

</div>

<script>
(() => {
    const studentsTab = document.getElementById('studentsTab');
    const registrationTab = document.getElementById('registrationTab');
    const studentsPanel = document.getElementById('studentsPanel');
    const registrationPanel = document.getElementById('registrationPanel');
    const filter = document.getElementById('registrationStatusFilter');
    const rows = Array.from(document.querySelectorAll('#registrationRequestsBody tr[data-registration-status]'));
    const empty = document.getElementById('registrationFilteredEmpty');
    function switchTab(name) {
        const isStudents = name === 'students';
        studentsPanel.hidden = !isStudents;
        registrationPanel.hidden = isStudents;
        studentsTab.classList.toggle('is-active', isStudents);
        registrationTab.classList.toggle('is-active', !isStudents);
        studentsTab.setAttribute('aria-selected', String(isStudents));
        registrationTab.setAttribute('aria-selected', String(!isStudents));
    }
    function filterRequests() {
        let shown = 0;
        rows.forEach(row => {
            const visible = filter.value === 'ALL' || row.dataset.registrationStatus === filter.value;
            row.hidden = !visible;
            if (visible) shown++;
        });
        empty.hidden = rows.length === 0 || shown > 0;
    }
    studentsTab.addEventListener('click', () => switchTab('students'));
    registrationTab.addEventListener('click', () => switchTab('registration'));
    filter.addEventListener('change', filterRequests);
    filterRequests();
})();
</script>

<!-- REGISTRATION REVIEW MODAL -->
<div class="student-modal-overlay" id="registrationReviewModal" role="dialog" aria-modal="true" aria-labelledby="registrationReviewTitle">
 <div class="student-modal" style="max-width:580px">
  <div class="student-modal-header"><div><h2 id="registrationReviewTitle">Review Registration Request</h2><p>Verify identity using official Registrar records before approval.</p></div><button type="button" class="student-modal-close" id="closeRegistrationReview" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
  <div class="student-modal-body">
   <div class="registration-detail-grid">
    <div class="registration-detail"><span>Student Number</span><strong id="reviewStudentNumber"></strong></div>
    <div class="registration-detail"><span>Student Name</span><strong id="reviewStudentName"></strong></div>
    <div class="registration-detail"><span>Date of Birth</span><strong id="reviewDob"></strong></div>
    <div class="registration-detail"><span>Program</span><strong id="reviewProgram"></strong></div>
    <div class="registration-detail"><span>Year Level</span><strong id="reviewYear"></strong></div>
    <div class="registration-detail"><span>Section</span><strong id="reviewSection"></strong></div>
    <div class="registration-detail"><span>Requested At</span><strong id="reviewRequested"></strong></div>
    <div class="registration-detail"><span>Status</span><strong id="reviewStatus"></strong></div>
   </div>
   <div id="registrationDecisionFields" style="margin-top:18px">
    <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px"><input id="registrationIdentityVerified" type="checkbox" style="margin-top:3px"> I have independently verified this student's identity against official Registrar records.</label>
    <label for="registrationRejectionReason" style="display:block;margin-top:14px;font-size:13px">Rejection reason (optional)</label>
    <textarea id="registrationRejectionReason" maxlength="2000" rows="2" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px" placeholder="Reason for rejecting this request"></textarea>
   </div>
   <p id="registrationReviewMessage" role="status" aria-live="polite" style="font-size:13px;margin-top:12px;white-space:pre-wrap"></p>
  </div>
  <div class="student-modal-footer">
   <button type="button" class="student-modal-cancel" id="cancelRegistrationReview">Close</button>
   <button type="button" class="admin-primary-button" id="rejectRegistrationRequest">Reject</button>
   <button type="button" class="admin-primary-button" id="approveRegistrationRequest">Approve</button>
  </div>
 </div>
</div>
<script>
(() => {
 const modal=document.getElementById('registrationReviewModal');
 const closeButton=document.getElementById('closeRegistrationReview');
 const cancelButton=document.getElementById('cancelRegistrationReview');
 const approve=document.getElementById('approveRegistrationRequest');
 const reject=document.getElementById('rejectRegistrationRequest');
 const identity=document.getElementById('registrationIdentityVerified');
 const reason=document.getElementById('registrationRejectionReason');
 const message=document.getElementById('registrationReviewMessage');
 let requestId=null, previousFocus=null, saved=false, busy=false;
 const csrf=<?= json_encode($_SESSION['registration_review_csrf']) ?>;
 function close() {
  if(busy)return;
  modal.classList.remove('is-open');
  if(saved) { window.location.href=window.location.pathname+'?page=student_management'; return; }
  if(previousFocus) previousFocus.focus();
 }
 document.getElementById('registrationRequestsBody').addEventListener('click',event=>{
  const button=event.target.closest('.registration-review-button');
  if(!button)return;
  previousFocus=button;requestId=Number(button.dataset.requestId);saved=false;
  const fields={reviewStudentNumber:button.dataset.studentNumber,reviewStudentName:button.dataset.studentName,reviewDob:button.dataset.dob,reviewProgram:button.dataset.program,reviewYear:button.dataset.year,reviewSection:button.dataset.section,reviewRequested:button.dataset.requested,reviewStatus:button.dataset.status};
  Object.entries(fields).forEach(([id,value])=>document.getElementById(id).textContent=value||'—');
  const pending=button.dataset.status==='PENDING';
  document.getElementById('registrationDecisionFields').hidden=!pending;
  approve.hidden=!pending;reject.hidden=!pending;
  identity.checked=false;reason.value='';message.textContent='';
  modal.classList.add('is-open');closeButton.focus();
 });
 async function submit(action){
  if(busy || !requestId)return;
  if(action==='approve' && !identity.checked){message.textContent='Verify identity before approving.';return;}
  if(!window.confirm(action==='approve'?'Approve this student and create an account?':'Reject this registration request?'))return;
  busy=true;approve.disabled=true;reject.disabled=true;message.textContent='Processing...';
  try {
   const response=await fetch(<?= json_encode(BASE_URL . 'backend/api/review_registration_request.php') ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf_token:csrf,request_id:requestId,action,identity_verified:identity.checked,rejection_reason:reason.value})});
   const result=await response.json();
   if(!response.ok || !result.success)throw new Error(result.message||'Unable to process request.');
   saved=true;message.textContent=result.message||'Request updated.';
   approve.hidden=true;reject.hidden=true;document.getElementById('registrationDecisionFields').hidden=true;
  }catch(error){message.textContent=error.message||'Unable to process request.';}
  finally{busy=false;approve.disabled=false;reject.disabled=false;}
 }
 approve.addEventListener('click',()=>submit('approve'));
 reject.addEventListener('click',()=>submit('reject'));
 closeButton.addEventListener('click',close);cancelButton.addEventListener('click',close);
 modal.addEventListener('click',event=>{if(event.target===modal)close();});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&modal.classList.contains('is-open'))close();});
})();
</script>

<!-- IMPORT MASTERLIST MODAL -->
<div class="student-modal-overlay" id="importMasterlistModal">
  <div class="student-modal" style="max-width:560px">
    <div class="student-modal-header"><div><h2>Import Student Masterlist</h2><p>Upload verified registrar records, not student accounts.</p></div>
      <button type="button" class="student-modal-close" id="closeImportMasterlist" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    <form id="importMasterlistForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['masterlist_import_csrf']) ?>">
      <div class="student-modal-body">
        <p style="font-size:13px;line-height:1.6;margin-bottom:14px">Upload the original Excel workbook (.xlsx) or a CSV UTF-8 file (.csv). Use the first worksheet and keep all 14 template headers in their original order. Existing masterlist records are skipped, not overwritten.</p>
        <div class="student-form-group"><label for="masterlistFile">Masterlist file (.csv or .xlsx)</label><input type="file" name="masterlist_file" id="masterlistFile" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
        <div class="student-form-message" id="importMasterlistMessage" role="status" aria-live="polite" style="white-space:pre-line"></div>
      </div>
      <div class="student-modal-footer"><button type="button" class="student-modal-cancel" id="cancelImportMasterlist">Cancel</button><button type="submit" class="admin-primary-button" id="submitImportMasterlist"><i class="fa-solid fa-upload"></i> Import Records</button></div>
    </form>
  </div>
</div>
<script>
(() => {
 const modal=document.getElementById('importMasterlistModal');
 const form=document.getElementById('importMasterlistForm');
 const message=document.getElementById('importMasterlistMessage');
 const button=document.getElementById('submitImportMasterlist');
 function close(){modal.classList.remove('is-open');}
 document.getElementById('importMasterlistButton').addEventListener('click',()=>{message.textContent='';message.className='student-form-message';form.reset();modal.classList.add('is-open');});
 document.getElementById('closeImportMasterlist').addEventListener('click',close);
 document.getElementById('cancelImportMasterlist').addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});
 form.addEventListener('submit',async e=>{
  e.preventDefault();button.disabled=true;message.textContent='Importing...';message.className='student-form-message';
  try {
   const response=await fetch('backend/api/import_student_masterlist.php',{method:'POST',body:new FormData(form),credentials:'same-origin'});
   const result=await response.json();
   message.className='student-form-message '+(result.success?'is-success':'is-error');
   message.textContent=result.message||'Import failed.';
   if(result.errors?.length)message.textContent+='\n'+result.errors.join('\n');
  } catch(err){message.className='student-form-message is-error';message.textContent='Could not complete import. Check the server and try again.';}
  finally{button.disabled=false;}
 });
})();
</script>

<!-- ADD STUDENT MODAL -->

<div class="student-modal-overlay" id="addStudentModal">

    <div class="student-modal">

        <div class="student-modal-header">

            <div>
                <h2>Add Student</h2>
                <p>Create a new student account.</p>
            </div>

            <button
                type="button"
                class="student-modal-close"
                id="closeAddStudentModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form id="addStudentForm">

            <div class="student-modal-body">

                <!-- ACCOUNT INFORMATION -->

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user-lock"></i>
                        Account Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="studentNumber">
                                Student Number
                            </label>

                            <input
                                type="text"
                                id="studentNumber"
                                name="student_number"
                                placeholder="e.g. 202401555"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentPassword">
                                Password
                            </label>

                            <input
                                type="password"
                                id="studentPassword"
                                name="password"
                                placeholder="Enter initial password"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- PERSONAL INFORMATION -->

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user"></i>
                        Student Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="studentFirstName">
                                First Name
                            </label>

                            <input
                                type="text"
                                id="studentFirstName"
                                name="first_name"
                                placeholder="First name"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentMiddleName">
                                Middle Name
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="studentMiddleName"
                                name="middle_name"
                                placeholder="Middle name"
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentLastName">
                                Last Name
                            </label>

                            <input
                                type="text"
                                id="studentLastName"
                                name="last_name"
                                placeholder="Last name"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentProgram">
                                Program
                            </label>

                            <select id="studentProgram" name="program" required>
                                <option value="">Select program</option>
                                <option value="Bachelor of Arts in Journalism">Bachelor of Arts in Journalism</option>
                                <option value="Bachelor of Arts in Political Science">Bachelor of Arts in Political Science</option>
                                <option value="Bachelor of Public Administration">Bachelor of Public Administration</option>
                                <option value="Bachelor of Science in Social Work">Bachelor of Science in Social Work</option>
                                <option value="Bachelor of Science in Criminology">Bachelor of Science in Criminology</option>
                                <option value="BSBA Major in Financial Management">BSBA Major in Financial Management</option>
                                <option value="BSBA Major in Human Resource Management">BSBA Major in Human Resource Management</option>
                                <option value="BSBA Major in Marketing Management">BSBA Major in Marketing Management</option>
                                <option value="Bachelor of Science in Accountancy">Bachelor of Science in Accountancy</option>
                                <option value="Bachelor of Science in Management Accounting">Bachelor of Science in Management Accounting</option>
                                <option value="Bachelor of Science in Information Technology">Bachelor of Science in Information Technology</option>
                                <option value="Bachelor in Elementary Education">Bachelor in Elementary Education</option>
                                <option value="Bachelor in Secondary Education : English">Bachelor in Secondary Education : English</option>
                                <option value="Bachelor in Secondary Education : Mathematics">Bachelor in Secondary Education : Mathematics</option>
                                <option value="Bachelor in Secondary Education : Social Studies">Bachelor in Secondary Education : Social Studies</option>
                                <option value="Bachelor of Early Childhood Education">Bachelor of Early Childhood Education</option>
                                
                            </select>

                        </div>


                        <div class="student-form-group">

                            <label for="studentYearLevel">
                                Year Level
                            </label>

                            <select
                                id="studentYearLevel"
                                name="year_level"
                                required
                            >

                                <option value="">
                                    Select year level
                                </option>

                                <option value="1">
                                    1st Year
                                </option>

                                <option value="2">
                                    2nd Year
                                </option>

                                <option value="3">
                                    3rd Year
                                </option>

                                <option value="4">
                                    4th Year
                                </option>

                            </select>

                        </div>


                        <div class="student-form-group">

                            <label for="studentSection">
                                Section
                            </label>

                            <input
                                type="text"
                                id="studentSection"
                                name="section"
                                placeholder="e.g. 3D"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentEmail">
                                Email
                            </label>

                            <input
                                type="email"
                                id="studentEmail"
                                name="email"
                                placeholder="student@example.com"
                                required
                            >

                        </div>


                        <div class="student-form-group">

                            <label for="studentPhone">
                                Phone
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="studentPhone"
                                name="phone"
                                placeholder="09XXXXXXXXX"
                            >

                        </div>

                    </div>

                </div>


                <div
                    class="student-form-message"
                    id="addStudentMessage"
                ></div>

            </div>


            <div class="student-modal-footer">

                <button
                    type="button"
                    class="student-modal-cancel"
                    id="cancelAddStudent"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="admin-primary-button"
                    id="saveStudentButton"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Add Student
                </button>

            </div>

        </form>

    </div>

</div>

<!-- EDIT STUDENT MODAL -->

<div class="student-modal-overlay" id="editStudentModal">

    <div class="student-modal">

        <div class="student-modal-header">

            <div>
                <h2>Edit Student</h2>
                <p>Update the student's account and information.</p>
            </div>

            <button
                type="button"
                class="student-modal-close"
                id="closeEditStudentModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form id="editStudentForm">

            <input
                type="hidden"
                id="editStudentId"
                name="student_id"
            >

            <div class="student-modal-body">

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user-lock"></i>
                        Account Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="editStudentNumber">
                                Student Number
                            </label>

                            <input
                                type="text"
                                id="editStudentNumber"
                                name="student_number"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentPassword">
                                Password
                                <span>(Leave blank to keep current)</span>
                            </label>

                            <input
                                type="password"
                                id="editStudentPassword"
                                name="password"
                                placeholder="Leave blank to keep current"
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentStatus">
                                Account Status
                            </label>

                            <select
                                id="editStudentStatus"
                                name="is_active"
                                required
                            >
                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>
                            </select>

                        </div>

                    </div>

                    

                </div>

                <div class="student-modal-section">

                    <div class="student-modal-section-title">
                        <i class="fa-solid fa-user"></i>
                        Student Information
                    </div>

                    <div class="student-form-grid">

                        <div class="student-form-group">

                            <label for="editStudentFirstName">
                                First Name
                            </label>

                            <input
                                type="text"
                                id="editStudentFirstName"
                                name="first_name"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentMiddleName">
                                Middle Name
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="editStudentMiddleName"
                                name="middle_name"
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentLastName">
                                Last Name
                            </label>

                            <input
                                type="text"
                                id="editStudentLastName"
                                name="last_name"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentProgram">
                                Program
                            </label>

                            <select id="editStudentProgram" name="program" required>
                                <option value="">Select program</option>
                                <option value="Bachelor of Arts in Journalism">Bachelor of Arts in Journalism</option>
                                <option value="Bachelor of Arts in Political Science">Bachelor of Arts in Political Science</option>
                                <option value="Bachelor of Public Administration">Bachelor of Public Administration</option>
                                <option value="Bachelor of Science in Social Work">Bachelor of Science in Social Work</option>
                                <option value="Bachelor of Science in Criminology">Bachelor of Science in Criminology</option>
                                <option value="BSBA Major in Financial Management">BSBA Major in Financial Management</option>
                                <option value="BSBA Major in Human Resource Management">BSBA Major in Human Resource Management</option>
                                <option value="BSBA Major in Marketing Management">BSBA Major in Marketing Management</option>
                                <option value="Bachelor of Science in Accountancy">Bachelor of Science in Accountancy</option>
                                <option value="Bachelor of Science in Management Accounting">Bachelor of Science in Management Accounting</option>
                                <option value="Bachelor of Science in Information Technology">Bachelor of Science in Information Technology</option>
                                <option value="Bachelor in Elementary Education">Bachelor in Elementary Education</option>
                                <option value="Bachelor in Secondary Education : English">Bachelor in Secondary Education : English</option>
                                <option value="Bachelor in Secondary Education : Mathematics">Bachelor in Secondary Education : Mathematics</option>
                                <option value="Bachelor in Secondary Education : Social Studies">Bachelor in Secondary Education : Social Studies</option>
                                <option value="Bachelor of Early Childhood Education">Bachelor of Early Childhood Education</option>
                            </select>
                        </div>

                        <div class="student-form-group">

                            <label for="editStudentYearLevel">
                                Year Level
                            </label>

                            <select
                                id="editStudentYearLevel"
                                name="year_level"
                                required
                            >
                                <option value="">
                                    Select year level
                                </option>

                                <option value="1">
                                    1st Year
                                </option>

                                <option value="2">
                                    2nd Year
                                </option>

                                <option value="3">
                                    3rd Year
                                </option>

                                <option value="4">
                                    4th Year
                                </option>

                            </select>

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentSection">
                                Section
                            </label>

                            <input
                                type="text"
                                id="editStudentSection"
                                name="section"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentEmail">
                                Email
                            </label>

                            <input
                                type="email"
                                id="editStudentEmail"
                                name="email"
                                required
                            >

                        </div>

                        <div class="student-form-group">

                            <label for="editStudentPhone">
                                Phone
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="editStudentPhone"
                                name="phone"
                            >

                        </div>

                    </div>

                </div>

                <div
                    class="student-form-message"
                    id="editStudentMessage"
                ></div>

            </div>

            <div class="student-modal-footer">

                <button
                    type="button"
                    class="student-modal-cancel"
                    id="cancelEditStudent"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="admin-primary-button"
                    id="updateStudentButton"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>
(() => {
    const form = document.getElementById('studentManagementSearchForm');
    const input = document.getElementById('studentManagementSearch');
    const results = document.getElementById('studentManagementResults');
    if (!form || !input || !results) return;

    let debounceTimer;
    let controller;
    let requestNumber = 0;

    async function updateResults(page = 1) {
        if (controller) controller.abort();
        controller = new AbortController();
        const thisRequest = ++requestNumber;
        const url = new URL(form.action, window.location.href);
        url.searchParams.set('page', 'student_management');
        url.searchParams.set('search', input.value);
        url.searchParams.set('student_page', String(page));
        results.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url.toString(), {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Search request failed');
            const html = await response.text();
            if (thisRequest !== requestNumber) return;
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextResults = doc.getElementById('studentManagementResults');
            if (!nextResults) throw new Error('Search results were not found');
            results.innerHTML = nextResults.innerHTML;
            window.history.replaceState(null, '', url.toString());
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Student Management live search:', error);
                // The standard GET form remains usable if AJAX fails.
            }
        } finally {
            if (thisRequest === requestNumber) results.removeAttribute('aria-busy');
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => updateResults(1), 300);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        updateResults(1);
    });

    results.addEventListener('click', (event) => {
        const link = event.target.closest('.admin-student-enrollment-pagination a');
        if (!link || link.classList.contains('disabled')) return;
        event.preventDefault();
        const url = new URL(link.href, window.location.href);
        updateResults(Number(url.searchParams.get('student_page')) || 1);
    });
})();
</script>


<script>


const addStudentButton =
    document.getElementById('addStudentButton');

const addStudentModal =
    document.getElementById('addStudentModal');

const closeAddStudentModal =
    document.getElementById('closeAddStudentModal');

const cancelAddStudent =
    document.getElementById('cancelAddStudent');

const addStudentForm =
    document.getElementById('addStudentForm');

const addStudentMessage =
    document.getElementById('addStudentMessage');


function openAddStudentModal() {

    addStudentModal.classList.add('is-open');

}


function closeAddStudentModalWindow() {

    addStudentModal.classList.remove('is-open');

    addStudentForm.reset();

    addStudentMessage.className =
        'student-form-message';

    addStudentMessage.textContent = '';

}


addStudentButton.addEventListener(
    'click',
    openAddStudentModal
);


closeAddStudentModal.addEventListener(
    'click',
    closeAddStudentModalWindow
);


cancelAddStudent.addEventListener(
    'click',
    closeAddStudentModalWindow
);


addStudentModal.addEventListener(
    'click',
    function (event) {

        if (event.target === addStudentModal) {
            closeAddStudentModalWindow();
        }

    }
);

addStudentForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();

        addStudentMessage.className =
            'student-form-message';

        addStudentMessage.textContent = '';

        const saveButton =
            document.getElementById('saveStudentButton');

        saveButton.disabled = true;

        saveButton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

        const formData = new FormData(addStudentForm);

        const data = {
            student_id: formData.get('student_id'),
            student_number: formData.get('student_number'),
            password: formData.get('password'),
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            program: formData.get('program'),
            year_level: formData.get('year_level'),
            section: formData.get('section'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            is_active: formData.get('is_active')
        };

        try {

            const response = await fetch(
                'backend/api/add_student.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            );

            const result = await response.json();

            if (!result.success) {

                addStudentMessage.className =
                    'student-form-message is-error';

                addStudentMessage.textContent =
                    result.message;

                return;
            }

            addStudentMessage.className =
                'student-form-message is-success';

            addStudentMessage.textContent =
                result.message;

            setTimeout(function () {
                window.location.reload();
            }, 800);

        } catch (error) {

            addStudentMessage.className =
                'student-form-message is-error';

            addStudentMessage.textContent =
                'Something went wrong. Please try again.';

        } finally {

            saveButton.disabled = false;

            saveButton.innerHTML =
                '<i class="fa-solid fa-user-plus"></i> Add Student';

        }

    }
);

const editStudentModal =
    document.getElementById('editStudentModal');

const closeEditStudentModal =
    document.getElementById('closeEditStudentModal');

const cancelEditStudent =
    document.getElementById('cancelEditStudent');

const editStudentForm =
    document.getElementById('editStudentForm');

const editStudentMessage =
    document.getElementById('editStudentMessage');


function closeEditStudentModalWindow() {

    editStudentModal.classList.remove('is-open');

    editStudentForm.reset();

    editStudentMessage.className =
        'student-form-message';

    editStudentMessage.textContent = '';

}


document.addEventListener('click', async function (event) {
    const editButton = event.target.closest('.student-management-edit-button');
    if (!editButton) return;
    const studentId = editButton.dataset.studentId;

                editStudentMessage.className =
                    'student-form-message';

                editStudentMessage.textContent = '';

                try {

                    const response = await fetch(
                        'backend/api/get_student.php?id=' +
                        encodeURIComponent(studentId)
                    );

                    const result =
                        await response.json();

                    if (!result.success) {

                        alert(result.message);

                        return;
                    }

                    const student =
                        result.student;

                    document.getElementById(
                        'editStudentId'
                    ).value = student.student_id;

                    document.getElementById(
                        'editStudentNumber'
                    ).value = student.student_number;

                    document.getElementById(
                        'editStudentStatus'
                    ).value = student.is_active;

                    document.getElementById(
                        'editStudentFirstName'
                    ).value = student.first_name;

                    document.getElementById(
                        'editStudentMiddleName'
                    ).value = student.middle_name || '';

                    document.getElementById(
                        'editStudentLastName'
                    ).value = student.last_name;

                    document.getElementById(
                        'editStudentProgram'
                    ).value = student.program;

                    document.getElementById(
                        'editStudentYearLevel'
                    ).value = student.year_level;

                    document.getElementById(
                        'editStudentSection'
                    ).value = student.section;

                    document.getElementById(
                        'editStudentEmail'
                    ).value = student.email;

                    document.getElementById(
                        'editStudentPhone'
                    ).value = student.phone || '';

                    editStudentModal.classList.add(
                        'is-open'
                    );

                } catch (error) {

                    alert(
                        'Unable to load student information.'
                    );

                }

});


closeEditStudentModal.addEventListener(
    'click',
    closeEditStudentModalWindow
);


cancelEditStudent.addEventListener(
    'click',
    closeEditStudentModalWindow
);


editStudentModal.addEventListener(
    'click',
    function (event) {

        if (event.target === editStudentModal) {
            closeEditStudentModalWindow();
        }

    }
);


editStudentForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();

        const updateButton =
            document.getElementById(
                'updateStudentButton'
            );

        editStudentMessage.className =
            'student-form-message';

        editStudentMessage.textContent = '';

        updateButton.disabled = true;

        updateButton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        const formData =
            new FormData(editStudentForm);

        const data = {
            student_id: formData.get('student_id'),
            student_number: formData.get('student_number'),
            password: formData.get('password'),
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            program: formData.get('program'),
            year_level: formData.get('year_level'),
            section: formData.get('section'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            is_active: parseInt(
                formData.get('is_active'),
                10
            )
        };

        try {

            const response = await fetch(
                'backend/api/update_student.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            );

            const result =
                await response.json();

            if (!result.success) {

                editStudentMessage.className =
                    'student-form-message is-error';

                editStudentMessage.textContent =
                    result.message;

                return;
            }

            editStudentMessage.className =
                'student-form-message is-success';

            editStudentMessage.textContent =
                result.message;

            setTimeout(function () {

                window.location.reload();

            }, 800);

        } catch (error) {

            editStudentMessage.className =
                'student-form-message is-error';

            editStudentMessage.textContent =
                'Something went wrong. Please try again.';

        } finally {

            updateButton.disabled = false;

            updateButton.innerHTML =
                '<i class="fa-solid fa-floppy-disk"></i> Save Changes';

        }

    }
);

</script>

</body>
</html>