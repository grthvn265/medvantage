<?php
include '../../components/db.php';


/* ============================
   FILTERS
============================ */

$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

$where = [];
$params = [];

// Default: exclude archived unless specifically viewing archived
if (!$show_archived) {
    $where[] = "b.is_archived = 0";
} else {
    $where[] = "b.is_archived = 1";
}

if ($status_filter) {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($month_filter) {
    $where[] = "MONTH(b.invoice_date) = ?";
    $params[] = $month_filter;
}

$where_sql = "WHERE " . implode(" AND ", $where);

/* ============================
   FETCH BILLING RECORDS
============================ */

$stmt = $pdo->prepare("
    SELECT b.*, 
           p.first_name AS p_fname, p.last_name AS p_lname,
           d.first_name AS d_fname, d.last_name AS d_lname
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    LEFT JOIN doctors d ON b.doctor_id = d.doctor_id
    $where_sql
    ORDER BY b.invoice_date DESC
");
$stmt->execute($params);
$billings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   FETCH PATIENTS & DOCTORS
============================ */

$patients = $pdo->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query("SELECT * FROM doctors ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Billing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        body { font-size: 16px; }
        .table th { font-size: 16px; }
        .table td { font-size: 15px; }
        .btn-sm { padding: 6px 12px; }
        .card { border-radius: 12px; }
        .badge { font-size: 13px; }
        .receipt-container {
            max-width: 800px;
            background: white;
            border: 1px solid #ddd;
            padding: 30px;
            font-family: 'Courier New', monospace;
        }
        #billingTable {
            table-layout: fixed;
            width: 100%;
        }
        #billingTable td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .receipt-section {
            margin-bottom: 20px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .receipt-label {
            font-weight: bold;
        }
        .receipt-total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
            margin: 20px 0;
        }
        .receipt-amount {
            font-size: 18px;
            font-weight: bold;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            border-top: 2px dashed #000;
            padding-top: 15px;
            font-size: 12px;
        }
        .input-error {
            box-shadow: 0 0 0.3rem rgba(220,53,69,0.45) !important;
            border-color: #dc3545 !important;
        }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
<div class="container-fluid">

<div class="card shadow-lg mt-4">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Billing Management</h4>
</div>

<!-- Display Messages -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✓ Billing record created successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['archived'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Billing record archived successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['restored'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Billing record restored successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['permanently_deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ✓ Billing record permanently deleted
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ✓ Billing record deleted
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        ⚠️ <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- FILTERS -->
<div id="filterContainer" class="mb-3">
<form method="GET" class="row g-2 mb-4">

<div class="col-md-auto">
<select name="month" class="form-select form-select-sm">
<option value="">Month</option>
<?php for ($m=1; $m<=12; $m++): ?>
<option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-auto">
<select name="status" class="form-select form-select-sm">
<option value="">Status</option>
<option value="Paid" <?= $status_filter === 'Paid' ? 'selected' : '' ?>>Paid</option>
<option value="Unpaid" <?= $status_filter === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
</select>
</div>

<div class="col-md-auto">
<select name="show_archived" class="form-select form-select-sm">
<option value="0" <?= !$show_archived ? 'selected' : '' ?>>Active</option>
<option value="1" <?= $show_archived ? 'selected' : '' ?>>Archived</option>
</select>
</div>

<div class="col-md-auto">
<button class="btn btn-primary btn-sm" type="submit" style="display:none;">Filter</button>
</div>

<div class="col-md-auto">
<a href="billing.php" class="btn btn-secondary btn-sm">Reset</a>
</div>
</form>
</div>

<!-- BILLING TABLE -->
<table id="billingTable" class="table table-striped table-hover table-bordered align-middle">
    <thead class="table-dark">
        <tr>
            <th>Billing ID</th>
            <th>Date</th>
            <th>Patient</th>
            <th>Total</th>
            <th>Status</th>
            <th width="310">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($billings as $bill): 
            $subtotal = $bill['amount'];
            $vat = $subtotal * 0.12;
            $total = $subtotal + $vat;
        ?>
        <tr>
            <td><strong title="<?= htmlspecialchars($bill['invoice_id']) ?>"><?= htmlspecialchars(substr($bill['invoice_id'], 0, 11)) ?></strong></td>
            <td><?= date('M d, Y', strtotime($bill['invoice_date'])) ?></td>
            <td><?= htmlspecialchars($bill['p_lname'] . ', ' . $bill['p_fname']) ?></td>

            <td>₱<?= number_format($subtotal, 2) ?></td>
            <td>
                <span class="badge <?= $bill['status'] === 'Paid' ? 'bg-success' : 'bg-danger' ?>">
                    <?= htmlspecialchars($bill['status']) ?>
                </span>
            </td>
            <td>
                <?php if (!$show_archived): ?>
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewBillingModal" onclick="loadBillingDetails(<?= $bill['billing_id'] ?>)">
                    View
                </button>
                <button class="btn btn-success btn-sm" onclick="generateReceipt(<?= $bill['billing_id'] ?>)" 
                    <?= $bill['status'] !== 'Paid' ? 'disabled title="Receipt only available for Paid invoices"' : '' ?>>
                    Receipt
                </button>
                     <a href="<?= htmlspecialchars(appUrl('/modules/billing/billing_archive_handler.php?action=archive&id=' . (int) $bill['billing_id'])) ?>" 
                   class="btn btn-secondary btn-sm"
                   onclick="return confirm('Archive this billing record?');">
                   Archive
                </a>
                <?php else: ?>
                     <a href="<?= htmlspecialchars(appUrl('/modules/billing/billing_archive_handler.php?action=restore&id=' . (int) $bill['billing_id'])) ?>" 
                   class="btn btn-info btn-sm"
                   onclick="return confirm('Are you sure you want to restore this billing record?');">
                   Restore
                </a>
                     <a href="<?= htmlspecialchars(appUrl('/modules/billing/billing_archive_handler.php?action=permanently_delete&id=' . (int) $bill['billing_id'])) ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Permanently delete? This cannot be undone.');">
                   Delete
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>
</div>

</div>
</div>

<!-- VIEW BILLING MODAL -->
<div class="modal fade" id="viewBillingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fw-bold">Billing Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="billingDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="editBillingBtn">Edit Billing</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                <!-- Receipt content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="printReceipt()">Print</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable with proper DOM configuration
    const billingTable = $('#billingTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 30, 50],
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, targets: 5 }
        ],
        dom: '<"top d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center"ip>'
    });

    const filterContainer = document.getElementById("filterContainer");
    const dataTableWrapper = document.querySelector("#billingTable_wrapper");

    const filterDiv = dataTableWrapper.querySelector(".top");

    if (filterDiv && filterContainer) {
        filterDiv.insertAdjacentElement("afterend", filterContainer);
    }

    // Auto-submit filter form when any filter dropdown changes
    const filterForm = document.querySelector('#filterContainer form[method="GET"]');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select[name="month"], select[name="status"], select[name="show_archived"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }
});

// Load and display billing details
function loadBillingDetails(billingId) {
    const row = document.querySelector(`button[onclick*="loadBillingDetails(${billingId})"]`).closest('tr');
    if (!row) return;

    const cells = row.querySelectorAll('td');
    const invoiceId = cells[0].textContent.trim();
    const date = cells[1].textContent.trim();
    const patient = cells[2].textContent.trim();
    const total = cells[3].textContent.trim();
    const status = cells[4].textContent.trim();

    const detailsHtml = `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Invoice ID</label>
                <p class="form-control-plaintext">${invoiceId}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Date</label>
                <p class="form-control-plaintext">${date}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Patient</label>
                <p class="form-control-plaintext">${patient}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Total</label>
                <p class="form-control-plaintext">${total}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <p class="form-control-plaintext">${status}</p>
            </div>
        </div>
    `;

    document.getElementById('billingDetailsContent').innerHTML = detailsHtml;
    
    // Update edit button to redirect to edit page
    const editBtn = document.getElementById('editBillingBtn');
    if (status.includes('Paid')) {
        editBtn.disabled = true;
        editBtn.style.opacity = '0.5';
        editBtn.style.cursor = 'not-allowed';
        editBtn.title = 'Cannot edit a paid billing record';
        editBtn.onclick = null;
    } else {
        editBtn.disabled = false;
        editBtn.style.opacity = '1';
        editBtn.style.cursor = 'pointer';
        editBtn.title = '';
        editBtn.onclick = function() {
            window.location.href = `edit_billing.php?id=${billingId}`;
        };
    }
}

// Generate Receipt Function
async function generateReceipt(billingId) {
    try {
        const receiptEndpoint = "<?= htmlspecialchars(appUrl('/modules/billing/get_receipt.php')) ?>";
        const response = await fetch(`${receiptEndpoint}?id=${encodeURIComponent(billingId)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        let data;

        try {
            data = JSON.parse(raw);
        } catch (parseError) {
            throw new Error('Invalid server response while loading receipt.');
        }

        if (!response.ok) {
            throw new Error(data.message || 'Unable to load receipt.');
        }

        if (data.success) {
            let receiptHtml = `
                <div class="receipt-container">
                    <div class="receipt-header">
                        <div class="receipt-title">INVOICE RECEIPT</div>
                        <div>MedVantage Medical Center</div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-row">
                            <span class="receipt-label">Invoice ID:</span>
                            <span>${data.billing.invoice_id}</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Invoice Date:</span>
                            <span>${data.billing.formatted_date}</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Status:</span>
                            <span>${data.billing.status}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div style="font-weight: bold; margin-bottom: 5px;">PATIENT INFORMATION</div>
                        <div class="receipt-row">
                            <span class="receipt-label">Name:</span>
                            <span>${data.billing.patient_name}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div style="font-weight: bold; margin-bottom: 5px;">DOCTOR INFORMATION</div>
                        <div class="receipt-row">
                            <span class="receipt-label">Doctor:</span>
                            <span>${data.billing.doctor_name}</span>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-row">
                            <span class="receipt-label">Amount:</span>
                            <span>₱${parseFloat(data.billing.amount).toFixed(2)}</span>
                        </div>
                        <div class="receipt-total">
                            <div class="receipt-row">
                                <span style="font-weight: bold;">TOTAL:</span>
                                <span class="receipt-amount">₱${parseFloat(data.billing.amount).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-footer">
                        <div>Thank you for your business!</div>
                        <div style="margin-top: 10px; font-size: 11px;">Generated: ${new Date().toLocaleString()}</div>
                    </div>
                </div>
            `;

            document.getElementById('receiptContent').innerHTML = receiptHtml;
            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            modal.show();
        } else {
            alert('Error generating receipt: ' + data.message);
        }
    } catch (error) {
        alert('Error loading receipt: ' + error.message);
        console.error(error);
    }
}

// Print Receipt Function
function printReceipt() {
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', 'PRINT', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Invoice Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Courier New", monospace; margin: 0; padding: 20px; }');
    printWindow.document.write('.receipt-container { max-width: 800px; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

</body>
</html>
