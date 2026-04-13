<?php
require '../../components/db.php';
require '../../components/audit_log.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || $currentUser['role_key'] !== 'super_admin') {
    header('Location: ' . appUrl('/modules/dashboard/index.php?denied=1'));
    exit;
}

if (($_POST['action'] ?? '') === 'log_print') {
    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($requestMethod !== 'POST') {
        http_response_code(405);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');

    $userId = (int) ($currentUser['user_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid session user']);
        exit;
    }

    $rawDescription = $_POST['description'] ?? $_GET['description'] ?? '';
    $description = trim((string) $rawDescription) !== ''
        ? trim((string) $rawDescription)
        : 'Printed audit log report';

    $logged = logAudit($pdo, 'PRINT', 'audit_log', null, $description);
    if (!$logged) {
        $exactError = getLastAuditError() ?? 'Unknown audit logging failure';
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write print audit log',
            'error' => $exactError,
        ]);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

$tableExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'audit_logs'");
$tableExistsStmt->execute();
if ((int) $tableExistsStmt->fetchColumn() === 0) {
    echo 'Audit log table is missing. Run setup/audit_log.sql first.';
    exit;
}

// Filters
$filterModule = isset($_GET['module']) ? trim($_GET['module']) : '';
$filterAction = isset($_GET['action']) ? trim($_GET['action']) : '';
$filterUser   = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$filterDate   = isset($_GET['date']) ? trim($_GET['date']) : '';

// Fetch distinct modules and actions for filter dropdowns
$moduleOptions = $pdo->query("SELECT DISTINCT module FROM audit_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
$actionOptions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
$userOptions   = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$userNameMap   = [];

foreach ($userOptions as $userOption) {
    $userNameMap[(int) $userOption['user_id']] = $userOption['full_name'];
}

$currentUserName = 'All Users';
if ($filterUser > 0 && isset($userNameMap[$filterUser])) {
    $currentUserName = $userNameMap[$filterUser];
}

$activeFilters = [];
if ($filterModule !== '') {
    $activeFilters[] = 'Module: ' . $filterModule;
}
if ($filterAction !== '') {
    $activeFilters[] = 'Action: ' . $filterAction;
}
if ($filterUser > 0) {
    $activeFilters[] = 'User: ' . $currentUserName;
}
if ($filterDate !== '') {
    $activeFilters[] = 'Date: ' . $filterDate;
}

$reportUser = $currentUser['full_name'] ?? $currentUser['username'] ?? 'System User';
$reportLogoUrl = appUrl('/components/logo.png');
$reportSystemName = 'MedVantage';

// Build query
$where  = [];
$params = [];

if ($filterModule !== '') {
    $where[]  = 'al.module = ?';
    $params[] = $filterModule;
}
if ($filterAction !== '') {
    $where[]  = 'al.action = ?';
    $params[] = $filterAction;
}
if ($filterUser > 0) {
    $where[]  = 'al.user_id = ?';
    $params[] = $filterUser;
}
if ($filterDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $where[]  = 'DATE(al.created_at) = ?';
    $params[] = $filterDate;
}

$sql = '
    SELECT
        al.log_id,
        al.action,
        al.module,
        al.entity_id,
        al.description,
        al.ip_address,
        al.created_at,
        u.full_name AS performed_by
    FROM audit_logs al
    LEFT JOIN users u ON u.user_id = al.user_id
';

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY al.created_at DESC LIMIT 1000';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$auditLogReport = [
    'total_records'   => count($logs),
    'generated_at'    => date('F j, Y, g:i A'),
    'generated_by'    => $reportUser,
    'system_name'     => $reportSystemName,
    'logo_url'        => $reportLogoUrl,
    'active_filters'  => $activeFilters,
    'filter_summary'  => empty($activeFilters) ? 'No filters applied' : implode(' | ', $activeFilters),
    'module'          => $filterModule !== '' ? $filterModule : 'All Modules',
    'action'          => $filterAction !== '' ? $filterAction : 'All Actions',
    'user'            => $currentUserName,
    'date'            => $filterDate !== '' ? $filterDate : 'All Dates',
];

$actionBadgeClass = [
    'CREATE'              => 'bg-success',
    'UPDATE'              => 'bg-primary',
    'DELETE'              => 'bg-danger',
    'ARCHIVE'             => 'bg-secondary',
    'RESTORE'             => 'bg-info text-dark',
    'PERMANENTLY_DELETED' => 'bg-dark',
    'LOGIN'               => 'bg-teal text-white',
    'LOGOUT'              => 'bg-warning text-dark',
    'PRINT'               => 'bg-info text-dark',
    'ACTIVATE'            => 'bg-success',
    'DEACTIVATE'          => 'bg-danger',
    'CANCEL'              => 'bg-warning text-dark',
    'BLOCK_DATE'          => 'bg-danger',
    'UNBLOCK_DATE'        => 'bg-success',
];

function actionBadge(string $action, array $map): string
{
    $cls = $map[$action] ?? 'bg-secondary';
    return '<span class="badge ' . htmlspecialchars($cls, ENT_QUOTES) . '">'
        . htmlspecialchars($action, ENT_QUOTES)
        . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log</title>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .badge.bg-teal { background-color: #0d6efd; }
        #auditTable td { font-size: 13px; vertical-align: middle; }
        .module-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            background: #e9f5f5;
            color: #095c5c;
            font-size: 12px;
        }

    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-body">

                <!-- FILTERS -->
                <div id="filterContainer" class="mb-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-auto">
                        <label class="form-label small fw-semibold mb-1">Module</label>
                        <select name="module" class="form-select form-select-sm">
                            <option value="">All Modules</option>
                            <?php foreach ($moduleOptions as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>"<?= $filterModule === $m ? ' selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label class="form-label small fw-semibold mb-1">Action</label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="">All Actions</option>
                            <?php foreach ($actionOptions as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"<?= $filterAction === $a ? ' selected' : '' ?>>
                                    <?= htmlspecialchars($a) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label class="form-label small fw-semibold mb-1">User</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="0">All Users</option>
                            <?php foreach ($userOptions as $u): ?>
                                <option value="<?= (int) $u['user_id'] ?>"<?= $filterUser === (int) $u['user_id'] ? ' selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label class="form-label small fw-semibold mb-1">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filterDate) ?>">
                    </div>
                    <div class="col-md-auto d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary" style="display:none;">Filter</button>
                    </div>
                    <div class="col-md-auto d-flex align-items-end">
                        <a href="<?= htmlspecialchars(appUrl('/modules/audit_log/audit_log.php')) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                    <div class="col-md-auto d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="printAuditLogReport()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </form>
                </div>

                <table id="auditTable" class="table table-striped table-hover table-bordered align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                            <th>Performed By</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record ID</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= (int) $log['log_id'] ?></td>
                            <td style="white-space:nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                            <td><?= $log['performed_by'] ? htmlspecialchars($log['performed_by']) : '<span class="text-muted">System</span>' ?></td>
                            <td><?= actionBadge($log['action'], $actionBadgeClass) ?></td>
                            <td><span class="module-pill"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['module']))) ?></span></td>
                            <td><?= $log['entity_id'] !== null ? (int) $log['entity_id'] : '<span class="text-muted">—</span>' ?></td>
                            <td><?= htmlspecialchars($log['description']) ?></td>
                            <td><?= $log['ip_address'] ? htmlspecialchars($log['ip_address']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const auditLogRows = <?= json_encode($logs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const auditLogReport = <?= json_encode($auditLogReport, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getAuditLogPrintStyles() {
    return `
        <style>
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #1f2933;
                background: #ffffff;
            }
            .report-document {
                padding: 28px;
            }
            .report-document-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 24px;
                padding-bottom: 20px;
                border-bottom: 3px solid #0a7d7d;
                margin-bottom: 24px;
            }
            .report-document-brand {
                display: flex;
                align-items: center;
                gap: 18px;
            }
            .report-document-brand img {
                width: 78px;
                height: 78px;
                object-fit: contain;
            }
            .report-document-brand h1 {
                margin: 0 0 4px;
                font-size: 28px;
                color: #071f26;
            }
            .report-document-brand p {
                margin: 0;
                color: #52606d;
                font-size: 14px;
            }
            .report-document-meta {
                min-width: 240px;
                padding: 16px 18px;
                border-radius: 12px;
                background: #f4fbfb;
                border: 1px solid rgba(10, 125, 125, 0.16);
            }
            .report-document-meta p {
                margin: 0 0 8px;
                font-size: 13px;
                color: #52606d;
            }
            .report-document-meta p:last-child {
                margin-bottom: 0;
            }
            .report-document-meta span {
                font-weight: 700;
                color: #071f26;
            }
            .report-document-summary {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            .report-document-summary-card {
                padding: 16px 18px;
                border-radius: 12px;
                background: linear-gradient(135deg, rgba(7, 31, 38, 0.03) 0%, rgba(10, 125, 125, 0.08) 100%);
                border: 1px solid rgba(7, 31, 38, 0.08);
            }
            .report-document-summary-card small {
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #52606d;
                font-weight: 700;
            }
            .report-document-summary-card strong {
                color: #071f26;
                font-size: 18px;
            }
            .report-document-table {
                width: 100%;
                border-collapse: collapse;
            }
            .report-document-table thead {
                background: linear-gradient(135deg, #071f26 0%, #0a7d7d 100%);
            }
            .report-document-table th {
                padding: 12px;
                font-size: 12px;
                text-align: left;
                color: #ffffff;
                border: 1px solid #d9e2ec;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
            .report-document-table td {
                padding: 11px 12px;
                border: 1px solid #d9e2ec;
                font-size: 13px;
                vertical-align: top;
            }
            .report-document-table tbody tr:nth-child(even) {
                background: #f8fbfd;
            }
            .report-document-footer {
                margin-top: 22px;
                padding-top: 16px;
                border-top: 1px solid #d9e2ec;
                font-size: 12px;
                color: #52606d;
                text-align: center;
            }
            .badge-print {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 999px;
                background: #e9f5f5;
                color: #095c5c;
                font-size: 12px;
                font-weight: 700;
            }
            .badge-print.badge-danger {
                background: #fdecec;
                color: #a61b1b;
            }
            .badge-print.badge-success {
                background: #eaf7ef;
                color: #137333;
            }
            .badge-print.badge-warning {
                background: #fff4db;
                color: #8a5b00;
            }
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }
                .report-document {
                    padding: 0;
                }
            }
        </style>
    `;
}

function getAuditLogBadgeClass(action) {
    const normalized = String(action || '').toUpperCase();
    if (['CREATE', 'ACTIVATE', 'UNBLOCK_DATE'].includes(normalized)) return 'badge-success';
    if (['DELETE', 'DEACTIVATE', 'BLOCK_DATE', 'PERMANENTLY_DELETED'].includes(normalized)) return 'badge-danger';
    if (['UPDATE', 'ARCHIVE', 'RESTORE', 'LOGIN', 'LOGOUT', 'CANCEL'].includes(normalized)) return 'badge-warning';
    return '';
}

function buildAuditLogTableMarkup() {
    const rows = auditLogRows.map((log) => {
        const actionClass = getAuditLogBadgeClass(log.action);
        const actionBadge = `<span class="badge-print ${actionClass}">${escapeHtml(log.action)}</span>`;
        const performedBy = log.performed_by ? escapeHtml(log.performed_by) : '<span class="text-muted">System</span>';
        const moduleLabel = escapeHtml(String(log.module || '').replace(/_/g, ' '));
        const recordId = log.entity_id !== null && log.entity_id !== '' ? escapeHtml(log.entity_id) : '—';
        const description = escapeHtml(log.description || '');
        const ipAddress = log.ip_address ? escapeHtml(log.ip_address) : '—';

        return `
            <tr>
                <td>${escapeHtml(log.log_id)}</td>
                <td style="white-space:nowrap">${escapeHtml(log.created_at)}</td>
                <td>${performedBy}</td>
                <td>${actionBadge}</td>
                <td>${moduleLabel}</td>
                <td>${recordId}</td>
                <td>${description}</td>
                <td>${ipAddress}</td>
            </tr>
        `;
    }).join('');

    return `
        <table class="report-document-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Timestamp</th>
                    <th>Performed By</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
            </tbody>
        </table>
    `;
}

function buildAuditLogDocumentMarkup() {
    const filterSummary = auditLogReport.filter_summary || 'No filters applied';

    return `
        <div class="report-document">
            <div class="report-document-header">
                <div class="report-document-brand">
                    <img src="${escapeHtml(auditLogReport.logo_url)}" alt="${escapeHtml(auditLogReport.system_name)} Logo">
                    <div>
                        <h1>Audit Log Report</h1>
                        <p>${escapeHtml(auditLogReport.system_name)}</p>
                    </div>
                </div>
                <div class="report-document-meta">
                    <p><span>Generated on:</span> ${escapeHtml(auditLogReport.generated_at)}</p>
                    <p><span>Generated by:</span> ${escapeHtml(auditLogReport.generated_by)}</p>
                    <p><span>System:</span> ${escapeHtml(auditLogReport.system_name)}</p>
                </div>
            </div>

            <div class="report-document-summary">
                <div class="report-document-summary-card">
                    <small>Total Records</small>
                    <strong>${escapeHtml(auditLogReport.total_records)}</strong>
                </div>
                <div class="report-document-summary-card">
                    <small>Active Filters</small>
                    <strong>${escapeHtml(auditLogReport.active_filters.length)}</strong>
                </div>
                <div class="report-document-summary-card">
                    <small>Filter Scope</small>
                    <strong>${escapeHtml(filterSummary)}</strong>
                </div>
            </div>

            ${buildAuditLogTableMarkup()}

            <div class="report-document-footer">
                Generated by ${escapeHtml(auditLogReport.system_name)}. This document reflects the audit log data available at the time of report generation.
            </div>
        </div>
    `;
}

async function logAuditLogPrintAction() {
    const printLogEndpoint = "<?= htmlspecialchars(appUrl('/modules/audit_log/audit_log.php')) ?>";
    const printLogPayload = new URLSearchParams({
        action: 'log_print',
        module: 'audit_log',
        description: 'Printed audit log report'
    });

    try {
        const printLogResponse = await fetch(printLogEndpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: printLogPayload,
            credentials: 'same-origin'
        });

        if (!printLogResponse.ok) {
            const rawBody = await printLogResponse.text();
            let parsedBody = rawBody;
            try {
                parsedBody = JSON.parse(rawBody);
            } catch (_) {
                // Keep raw body when response is not JSON.
            }

            console.error('Failed to log audit log print action (EXACT)', {
                status: printLogResponse.status,
                statusText: printLogResponse.statusText,
                rawBody,
                parsedBody
            });
        }
    } catch (error) {
        console.error('Network error while logging audit log print action', error);
    }
}

async function printAuditLogReport() {
    if (!auditLogRows || auditLogRows.length === 0) {
        alert('No data to print.');
        return;
    }

    const printWindow = window.open('', '', 'height=700,width=1200');
    if (!printWindow) {
        alert('Unable to open the print preview window. Please allow pop-ups and try again.');
        return;
    }

    // Trigger audit log write from the source window to avoid missing logs
    // when browser print lifecycle events behave inconsistently.
    logAuditLogPrintAction();

    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Audit Log Report - Print View</title>
            ${getAuditLogPrintStyles()}
        </head>
        <body>
            ${buildAuditLogDocumentMarkup()}
            <script>
                window.addEventListener('afterprint', function () {
                    window.close();
                });

                window.addEventListener('load', function () {
                    setTimeout(function () {
                        window.print();
                    }, 250);
                });
            <\/script>
        </body>
        </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();
}

$(document).ready(function () {
    const auditTable = $('#auditTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50],
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [6] }],
        dom: '<"top d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center"ip>'
    });

    const filterContainer = document.getElementById('filterContainer');
    const dataTableWrapper = document.querySelector('#auditTable_wrapper');
    const filterDiv = dataTableWrapper.querySelector('.top');

    if (filterDiv && filterContainer) {
        filterDiv.insertAdjacentElement('afterend', filterContainer);
    }

    const filterForm = filterContainer ? filterContainer.querySelector('form[method="GET"]') : null;
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select[name="module"], select[name="action"], select[name="user_id"]');
        filterSelects.forEach(function (select) {
            select.addEventListener('change', function () {
                filterForm.submit();
            });
        });
        const dateInput = filterForm.querySelector('input[name="date"]');
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                filterForm.submit();
            });
        }
    }
});
</script>
</body>
</html>
