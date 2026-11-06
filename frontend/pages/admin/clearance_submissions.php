<?php
require_role('ADMIN');

if (empty($_SESSION['csrf_token'])) {
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentPage = 'clearance_submissions';

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'PENDING', 'APPROVED', 'REJECTED'], true)) {
$statusFilter = '';
}

 
$periodResult = $conn->query('SELECT semester, school_year FROM system_settings ORDER BY setting_id DESC LIMIT 1');
$period = $periodResult ? $periodResult->fetch_assoc() : null;
$semester = $period['semester'] ?? '';
$schoolYear = $period['school_year'] ?? '';

$where = ['1=1'];
$types = '';
$params = [];
if ($semester !== '' && $schoolYear !== '') {
$where[] = 'c.semester = ? AND c.school_year = ?';
$types .= 'ss';
$params[] = $semester;
$params[] = $schoolYear;
}
if ($search !== '') {
$where[] = '(s.student_number LIKE ? OR s.first_name LIKE ? OR s.middle_name LIKE ? OR s.last_name LIKE ? OR s.program LIKE ? OR s.section LIKE ?)';
$term = '%' . $search . '%';
$types .= 'ssssss';
array_push($params, $term, $term, $term, $term, $term, $term);
}
if ($statusFilter !== '') {
$where[] = 'c.status = ?';
$types .= 's';
$params[] = $statusFilter;
}
$whereSql = implode(' AND ', $where);

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM clearance_submissions c INNER JOIN students s ON s.student_id = c.student_id WHERE $whereSql");
if ($types !== '') $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalSubmissions = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$perPage = 5;
$totalPages = max(1, (int) ceil($totalSubmissions / $perPage));
$pageNumber = min($totalPages, max(1, (int) ($_GET['clearance_page'] ?? 1)));
$offset = ($pageNumber - 1) * $perPage;

$listSql = "SELECT c.clearance_id, c.student_id, c.semester, c.school_year, c.original_filename, c.submitted_at, c.status, c.rejection_reason,
                   s.student_number, s.first_name, s.middle_name, s.last_name, s.program, s.year_level, s.section
            FROM clearance_submissions c
            INNER JOIN students s ON s.student_id = c.student_id
            WHERE $whereSql
            ORDER BY c.submitted_at DESC, c.clearance_id DESC LIMIT ? OFFSET ?";
$listStmt = $conn->prepare($listSql);
$listTypes = $types . 'ii';
$listParams = array_merge($params, [$perPage, $offset]);
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$submissions = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

$counts = ['PENDING' => 0, 'APPROVED' => 0, 'REJECTED' => 0];
$summarySql = 'SELECT status, COUNT(*) AS total FROM clearance_submissions';
$summaryTypes = '';
$summaryParams = [];
if ($semester !== '' && $schoolYear !== '') {
$summarySql .= ' WHERE semester = ? AND school_year = ?';
$summaryTypes = 'ss';
$summaryParams = [$semester, $schoolYear];
}
$summarySql .= ' GROUP BY status';
$summaryStmt = $conn->prepare($summarySql);
if ($summaryTypes !== '') $summaryStmt->bind_param($summaryTypes, ...$summaryParams);
$summaryStmt->execute();
foreach ($summaryStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
if (isset($counts[$row['status']])) $counts[$row['status']] = (int) $row['total'];
}
$summaryStmt->close();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function clearance_status_class(string $status): string {
return match ($status) {
'PENDING' => 'admin-cor-status-pending',
'APPROVED' => 'admin-cor-status-approved',
'REJECTED' => 'admin-cor-status-rejected',
default => ''
};
}
function clearance_url(int $page): string {
return BASE_URL . '?page=clearance_submissions&search=' . urlencode((string) ($_GET['search'] ?? ''))
. '&status=' . urlencode((string) ($_GET['status'] ?? '')) . '&clearance_page=' . $page;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMU | Clearance Submissions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="layout">
    <div class="imglogo"><span>CMU</span></div>
    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>
    <div class="overlay" id="overlay"></div>
    <div class="main">
        <?php require FRONTEND_PATH . 'includes/topbar.php'; ?>
        <div class="content">
            <section class="admin-cor-header">
                <div>
                    <h1>Clearance Submissions</h1>
                    <p>Review and manage submitted student clearance forms.</p>
                    <?php if ($semester !== '' && $schoolYear !== ''): ?>
                        <p><?= e($semester) ?> · <?= e($schoolYear) ?></p>
                    <?php endif; ?>
                </div>
            </section>
            <section class="admin-cor-summary">
                <?php foreach (['PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected'] as $key => $label): ?>
                    <div class="admin-cor-summary-card"><span><?= e($label) ?></span><strong><?= $counts[$key] ?></strong></div>
                <?php endforeach; ?>
            </section>
            <section class="admin-cor-card">
                <div class="admin-cor-card-header">
                    <div><h2>Clearance Submission List</h2><p>View uploaded clearance documents and their review status.</p></div>
                    <form method="GET" action="<?= e(BASE_URL) ?>">
                        <input type="hidden" name="page" value="clearance_submissions">
                        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                        <div class="admin-cor-search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search student..." autocomplete="off">
                        </div>
                    </form>
                </div>
                <div class="admin-cor-toolbar">
                    <form method="GET" action="<?= e(BASE_URL) ?>" class="admin-cor-filter">
                        <input type="hidden" name="page" value="clearance_submissions">
                        <input type="hidden" name="search" value="<?= e($search) ?>">
                        <label for="clearanceStatus">Status</label>
                        <select id="clearanceStatus" name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach (['PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected'] as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="admin-cor-table-info">
                    <span><?= $totalSubmissions ? 'Showing ' . ($offset + 1) . '–' . min($offset + $perPage, $totalSubmissions) . ' of ' . $totalSubmissions . ' submissions' : 'No clearance submissions found' ?></span>
                </div>
                <div class="admin-cor-table-wrapper">
                    <table class="admin-cor-table">
                        <thead><tr><th>Student Number</th><th>Student Name</th><th>Program</th><th>Year</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if (!$submissions): ?>
                            <tr><td colspan="7" class="admin-cor-empty">No clearance submissions found.</td></tr>
                        <?php else: foreach ($submissions as $submission):
$fullName = trim($submission['first_name'] . ' ' . ($submission['middle_name'] ?? '') . ' ' . $submission['last_name']);
?>
                            <tr>
                                <td><?= e($submission['student_number']) ?></td>
                                <td><?= e($fullName) ?></td>
                                <td><?= e($submission['program']) ?></td>
                                <td><?= (int) $submission['year_level'] ?></td>
                                <td><?= e(date('M j, Y', strtotime($submission['submitted_at']))) ?></td>
                                <td><span class="admin-cor-status <?= clearance_status_class($submission['status']) ?>"><?= e(ucfirst(strtolower($submission['status']))) ?></span></td>
                                <td><button type="button" class="admin-cor-view-button" 
                                    data-id="<?= (int) $submission['clearance_id'] ?>"
                                    data-student-number="<?= e($submission['student_number']) ?>"
                                    data-student-name="<?= e($fullName) ?>"
                                    data-program="<?= e($submission['program']) ?>"
                                    data-year="<?= (int) $submission['year_level'] ?>"
                                    data-section="<?= e($submission['section']) ?>"
                                    data-submitted="<?= e(date('M j, Y h:i A', strtotime($submission['submitted_at']))) ?>"
                                    data-status="<?= e($submission['status']) ?>"
                                    data-filename="<?= e($submission['original_filename']) ?>"
                                    data-reason="<?= e($submission['rejection_reason'] ?? '') ?>">View</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="admin-cor-pagination">
                        <a href="<?= e(clearance_url(max(1, $pageNumber - 1))) ?>" class="<?= $pageNumber === 1 ? 'disabled' : '' ?>">Previous</a>
                        <div class="admin-cor-page-numbers">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="<?= e(clearance_url($p)) ?>" class="<?= $p === $pageNumber ? 'active' : '' ?>"><?= $p ?></a>
                            <?php endfor; ?>
                        </div>
                        <a href="<?= e(clearance_url(min($totalPages, $pageNumber + 1))) ?>" class="<?= $pageNumber === $totalPages ? 'disabled' : '' ?>">Next</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<div class="admin-cor-modal" id="clearanceViewModal" aria-hidden="true">
    <div class="admin-cor-modal-overlay" id="clearanceModalOverlay"></div>
    <div class="admin-cor-modal-content" role="dialog" aria-modal="true" aria-labelledby="clearanceModalTitle">
        <div class="admin-cor-modal-header">
            <div><h2 id="clearanceModalTitle">Clearance Submission</h2><p>Review the submitted student clearance form.</p></div>
            <button type="button" class="admin-cor-modal-close" id="clearanceModalClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="admin-cor-modal-info">
            <div><small>Student Number</small><strong id="modalStudentNumber">-</strong></div>
            <div><small>Student Name</small><strong id="modalStudentName">-</strong></div>
            <div><small>Program</small><strong id="modalProgram">-</strong></div>
            <div><small>Year &amp; Section</small><strong id="modalYearSection">-</strong></div>
            <div><small>Submitted</small><strong id="modalSubmitted">-</strong></div>
            <div><small>Status</small><strong id="modalStatus">-</strong></div>
            <div class="admin-cor-review-message" id="clearanceReviewMessage"></div>
        </div>
        <div class="admin-cor-modal-file">
            <div class="admin-cor-modal-file-header"><div><h3>Submitted Clearance</h3><span id="modalFileName">-</span></div></div>
            <div class="admin-cor-file-preview" id="clearanceFilePreview"><p>Select a submission to preview.</p></div>
            <div class="admin-cor-rejection-area" id="clearanceRejectionArea" style="display:none">
                <label for="clearanceRejectionReason">Rejection Reason</label>
                <textarea id="clearanceRejectionReason" rows="4" placeholder="Explain why the clearance was rejected..."></textarea>
            </div>
            <div class="admin-cor-modal-actions" id="clearanceActions">
                <button type="button" class="admin-cor-reject-button" id="clearanceRejectButton"><i class="fa-solid fa-xmark"></i> Reject Clearance</button>
                <button type="button" class="admin-cor-approve-button" id="clearanceApproveButton"><i class="fa-solid fa-check"></i> Approve Clearance</button>
            </div>
        </div>
    </div>
</div>
<div class="admin-cor-image-modal" id="clearanceImageModal" aria-hidden="true">
    <div class="admin-cor-image-overlay" id="clearanceImageOverlay"></div>
    <div class="admin-cor-image-content">
        <button type="button" class="admin-cor-image-close" id="clearanceImageClose" aria-label="Close image"><i class="fa-solid fa-xmark"></i></button>
        <img id="clearanceMaximizedImage" src="" alt="Enlarged clearance document">
    </div>
</div>
<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>
<script>
(() => {
    const baseUrl = <?= json_encode(BASE_URL) ?>;
    const csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
    const modal = document.getElementById('clearanceViewModal');
    const preview = document.getElementById('clearanceFilePreview');
    const message = document.getElementById('clearanceReviewMessage');
    const approve = document.getElementById('clearanceApproveButton');
    const reject = document.getElementById('clearanceRejectButton');
    const rejectionArea = document.getElementById('clearanceRejectionArea');
    const reasonInput = document.getElementById('clearanceRejectionReason');
    const imageModal = document.getElementById('clearanceImageModal');
    const maxImage = document.getElementById('clearanceMaximizedImage');
    let selectedId = null;
    const setText = (id, value) => { document.getElementById(id).textContent = value || '-'; };
    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        preview.replaceChildren();
        selectedId = null;
    }
    function closeImage() {
        imageModal.classList.remove('show');
        imageModal.setAttribute('aria-hidden', 'true');
        maxImage.removeAttribute('src');
    }
    function renderClearancePreview(url, filename) {
        const ext = (filename || '').split('.').pop().toLowerCase();
        const header = document.querySelector('#clearanceViewModal .admin-cor-modal-file-header');
        header.querySelector('.document-open-link')?.remove();
        const link = document.createElement('a');
        link.className = 'document-open-link';
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = 'Open document in new tab';
        link.style.cssText = 'font-size:12px;color:#2563eb;text-decoration:underline;margin-left:auto';
        header.appendChild(link);

        const errorText = () => {
            preview.textContent = 'Preview could not be loaded. Try opening the document in a new tab.';
        };
        if (['jpg', 'jpeg', 'png'].includes(ext)) {
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Submitted clearance';
            img.className = 'admin-cor-preview-image';
            img.style.cursor = 'zoom-in';
            img.onerror = errorText;
            img.addEventListener('click', () => {
                maxImage.src = url;
                imageModal.classList.add('show');
                imageModal.setAttribute('aria-hidden', 'false');
            });
            preview.appendChild(img);
        } else if (ext === 'pdf') {
            const frame = document.createElement('iframe');
            frame.src = url;
            frame.title = 'Submitted clearance PDF';
            frame.style.cssText = 'display:block;width:100%;height:420px;border:0';
            preview.appendChild(frame);
        } else {
            preview.textContent = 'Preview is not available for this file type.';
        }
    }
    document.getElementById('clearanceModalClose').addEventListener('click', closeModal);
    document.getElementById('clearanceModalOverlay').addEventListener('click', closeModal);
    document.getElementById('clearanceImageClose').addEventListener('click', closeImage);
    document.getElementById('clearanceImageOverlay').addEventListener('click', closeImage);
    function bindClearanceViewButtons() {
    document.querySelectorAll('.admin-cor-view-button:not([data-live-bound])').forEach(button => {
        button.dataset.liveBound = '1';
        button.addEventListener('click', () => {
            selectedId = Number(button.dataset.id);
            const status = button.dataset.status;
            const filename = button.dataset.filename;
            setText('modalStudentNumber', button.dataset.studentNumber);
            setText('modalStudentName', button.dataset.studentName);
            setText('modalProgram', button.dataset.program);
            setText('modalYearSection', button.dataset.year + ' Year • ' + button.dataset.section);
            setText('modalSubmitted', button.dataset.submitted);
            setText('modalStatus', status);
            setText('modalFileName', filename);
            message.textContent = status === 'PENDING' ? 'This clearance is waiting for review.' :
                status === 'APPROVED' ? 'This clearance has already been approved.' :
                'This clearance was rejected. ' + (button.dataset.reason || 'The student may resubmit.');
            approve.style.display = status === 'PENDING' ? '' : 'none';
            reject.style.display = status === 'PENDING' ? '' : 'none';
            rejectionArea.style.display = 'none';
            reasonInput.value = '';
            preview.replaceChildren();
            const url = baseUrl + 'backend/api/view_clearance.php?id=' + encodeURIComponent(selectedId);
            renderClearancePreview(url, filename);
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        });
    });
    }
    window.bindClearanceViewButtons = bindClearanceViewButtons;
    bindClearanceViewButtons();
    async function sendReview(action) {
        if (!selectedId) return;
        const reason = reasonInput.value.trim();
        if (action === 'REJECT' && !reason) {
            rejectionArea.style.display = 'block';
            reasonInput.focus();
            return;
        }
        if (action === 'APPROVE' && !confirm('Approve this clearance and unlock the student\'s COR?')) return;
        approve.disabled = reject.disabled = true;
        try {
            const body = new FormData();
            body.append('submission_id', String(selectedId));
            body.append('action', action);
            body.append('rejection_reason', reason);
            body.append('csrf_token', csrfToken);
            const response = await fetch(baseUrl + 'backend/api/review_clearance.php', {
                method: 'POST', body, credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Review failed.');
            alert(result.message || 'Review saved.');
            window.location.reload();
        } catch (error) {
            alert(error.message || 'Unable to review clearance.');
            approve.disabled = reject.disabled = false;
        }
    }
    approve.addEventListener('click', () => sendReview('APPROVE'));
    reject.addEventListener('click', () => {
        if (rejectionArea.style.display === 'none') {
            rejectionArea.style.display = 'block';
            reasonInput.focus();
        } else sendReview('REJECT');
    });
})();
</script>

<script>
(() => {
    const pageName = 'clearance_submissions';
    const pageParam = 'clearance_page';
    const searchForm = document.querySelector('.admin-cor-card-header form');
    const searchInput = searchForm?.querySelector('[name="search"]');
    const statusForm = document.querySelector('.admin-cor-filter');
    const statusSelect = statusForm?.querySelector('[name="status"]');
    const card = document.querySelector('.admin-cor-card');
    if (!searchForm || !searchInput || !statusSelect || !card) return;

    let debounceTimer;
    let requestController;
    let requestNumber = 0;

    async function updateResults(targetPage = 1) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', pageName);
        url.searchParams.set('search', searchInput.value);
        url.searchParams.set('status', statusSelect.value);
        url.searchParams.set(pageParam, String(targetPage));

        if (requestController) requestController.abort();
        requestController = new AbortController();
        const thisRequest = ++requestNumber;
        try {
            const response = await fetch(url.toString(), {
                signal: requestController.signal,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Unable to load submissions.');
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            if (thisRequest !== requestNumber) return;

            for (const selector of [
                '.admin-cor-table-info',
                '.admin-cor-table-wrapper',
                '.admin-cor-pagination'
            ]) {
                const current = card.querySelector(selector);
                const updated = doc.querySelector('.admin-cor-card ' + selector);
                if (current && updated) {
                    current.replaceWith(document.importNode(updated, true));
                } else if (current && !updated) {
                    current.remove();
                } else if (!current && updated) {
                    card.appendChild(document.importNode(updated, true));
                }
            }
            window.bindClearanceViewButtons?.();
            // Keep the browser URL and status form synchronized without navigating.
            statusForm.querySelector('[name="search"]').value = searchInput.value;
            searchForm.querySelector('[name="status"]').value = statusSelect.value;
            window.history.replaceState(null, '', url.toString());
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Live submission search:', error);
                // Existing table remains available; full GET navigation still works.
            }
        }
    }

    searchForm.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        updateResults(1);
    });
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => updateResults(1), 300);
    });
    statusForm.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        updateResults(1);
    });
    statusSelect.removeAttribute('onchange');
    statusSelect.addEventListener('change', () => {
        clearTimeout(debounceTimer);
        updateResults(1);
    });
    card.addEventListener('click', event => {
        const link = event.target.closest('.admin-cor-pagination a');
        if (!link || !card.contains(link)) return;
        event.preventDefault();
        if (link.classList.contains('disabled')) return;
        const url = new URL(link.href, window.location.href);
        const page = parseInt(url.searchParams.get(pageParam) || '1', 10);
        clearTimeout(debounceTimer);
        updateResults(Number.isFinite(page) && page > 0 ? page : 1);
    });
})();
</script>
</body>
</html>
