<?php
require '../../components/db.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || $currentUser['role_key'] !== 'super_admin') {
    header('Location: ' . appUrl('/modules/dashboard/index.php?denied=1'));
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

$actionBadgeClass = [
    'CREATE'              => 'bg-success',
    'UPDATE'              => 'bg-primary',
    'DELETE'              => 'bg-danger',
    'ARCHIVE'             => 'bg-secondary',
    'RESTORE'             => 'bg-info text-dark',
    'PERMANENTLY_DELETED' => 'bg-dark',
    'LOGIN'               => 'bg-teal text-white',
    'LOGOUT'              => 'bg-warning text-dark',
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
        .filter-bar { background: #f8f9fa; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
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

        <div class="d-flex align-items-center mb-3 mt-3">
            <i class="bi bi-journal-text fs-3 me-2 text-teal" style="color:#0a7d7d"></i>
            <h4 class="mb-0 fw-bold">Audit Log</h4>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-bar row g-2 align-items-end">
            <div class="col-md-2">
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
            <div class="col-md-2">
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
            <div class="col-md-3">
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
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filterDate) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="audit_log.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table id="auditTable" class="table table-sm table-hover mb-0 w-100">
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

        <?php if (empty($logs)): ?>
            <div class="text-center text-muted mt-4">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No audit log entries found.
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#auditTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: [6] }],
            language: { search: 'Search logs:' }
        });
    });
</script>
</body>
</html>
