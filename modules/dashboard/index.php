<?php
require '../../components/db.php';

$reportUser = getCurrentUser($pdo);
$reportGeneratedBy = $reportUser['full_name'] ?? $reportUser['username'] ?? 'System User';
$reportLogoUrl = appUrl('/components/logo.png');
$reportSystemName = 'MedVantage';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Information System - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
        }
        .report-section {
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .bg-gradient {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        /* Enhanced Reports Styling */
        #reportTable thead th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px !important;
            border: none !important;
            color: white;
        }
        
        #reportTable tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }
        
        #reportTable tbody tr {
            transition: background-color 0.2s ease;
        }
        
        #reportTable tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.08) !important;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-input {
            border-color: #ddd;
            cursor: pointer;
        }
        
        .form-check-label {
            margin-left: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            color: #333;
            user-select: none;
        }
        
        .form-check-label:hover {
            color: #667eea;
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
        }
        
        .btn-group .btn {
            transition: all 0.3s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        #tableMetricsContainer .card {
            cursor: default;
        }
        
        #tableMetricsContainer .card:hover {
            border-color: rgba(102, 126, 234, 0.3) !important;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15) !important;
            transition: all 0.3s ease;
        }

        .report-branding-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 22px 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(10, 125, 125, 0.14);
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(7, 31, 38, 0.04) 0%, rgba(10, 125, 125, 0.08) 100%);
        }

        .report-branding-summary {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .report-branding-summary img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .report-branding-copy h5 {
            margin-bottom: 6px;
            color: #071f26;
        }

        .report-branding-copy p {
            margin-bottom: 0;
            color: #4f5d69;
        }

        .report-branding-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(150px, 1fr));
            gap: 12px 18px;
        }

        .report-branding-meta-item {
            padding: 10px 14px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.75);
            box-shadow: inset 0 0 0 1px rgba(102, 126, 234, 0.08);
        }

        .report-branding-meta-label {
            display: block;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #5c6f7f;
            margin-bottom: 4px;
        }

        .report-branding-meta-value {
            display: block;
            font-size: 1rem;
            font-weight: 600;
            color: #071f26;
        }

        @media (max-width: 991.98px) {
            .report-branding-panel {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-branding-meta {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .report-branding-summary {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-branding-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<!-- Content wrapper -->
<div class="content-wrapper p-4">
    <div class="container-fluid">

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="container-fluid">
                <h2 class="fw-bold mb-2"><i class="bi bi-graph-up"></i> Management Dashboard</h2>
                <p class="mb-0">Real-time system analytics and monitoring</p>
            </div>
        </div>

        <?php

        // Dashboard Metrics
        $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
        $totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

        $scheduled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Scheduled'")->fetchColumn();
        $completed = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Completed'")->fetchColumn();
        $cancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Cancelled'")->fetchColumn();

        $revenue = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Paid'")->fetchColumn();
        $revenue = $revenue ? $revenue : 0;
        $pendingBilling = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Pending'")->fetchColumn();
        $pendingBilling = $pendingBilling ? $pendingBilling : 0;

        $today = date('Y-m-d');
        $stmtToday = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
        $stmtToday->execute([$today]);
        $todayAppointments = $stmtToday->fetchColumn();

        // Report Data for Charts
        $appointmentsByStatus = [
            'Scheduled' => $scheduled,
            'Completed' => $completed,
            'Cancelled' => $cancelled
        ];

        // Revenue by Month (Last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $stmt = $pdo->prepare("SELECT SUM(amount) FROM billing WHERE status='Paid' AND DATE_FORMAT(created_at,'%Y-%m') = ?");
            $stmt->execute([$month]);
            $amount = $stmt->fetchColumn();
            $monthlyRevenue[date('M Y', strtotime($month . '-01'))] = $amount ? $amount : 0;
        }

        // Appointments by Doctor (Last 30 days)
        $appointmentsByDoctor = [];
        $stmt = $pdo->query("
            SELECT d.doctor_id, d.first_name, d.last_name, COUNT(a.appointment_id) as count
            FROM doctors d
            LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY d.doctor_id
            ORDER BY count DESC
            LIMIT 5
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $appointmentsByDoctor[$row['first_name'] . ' ' . $row['last_name']] = $row['count'];
        }
        ?>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase fw-bold opacity-75">Total Patients</h6>
                                <h3 class="fw-bold mt-2"><?= $totalPatients ?></h3>
                            </div>
                            <i class="bi bi-people-fill fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card bg-success text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase fw-bold opacity-75">Total Doctors</h6>
                                <h3 class="fw-bold mt-2"><?= $totalDoctors ?></h3>
                            </div>
                            <i class="bi bi-hospital fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase fw-bold opacity-75">Appointments</h6>
                                <h3 class="fw-bold mt-2"><?= $totalAppointments ?></h3>
                            </div>
                            <i class="bi bi-calendar-check-fill fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-uppercase fw-bold opacity-75">Revenue (Paid)</h6>
                                <h3 class="fw-bold mt-2">₱<?= number_format($revenue, 0) ?></h3>
                            </div>
                            <i class="bi bi-cash-flow fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow stat-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted fw-bold">SCHEDULED</h6>
                        <h4 class="text-info fw-bold mt-2"><?= $scheduled ?></h4>
                        <small class="text-muted">Pending appointments</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow stat-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted fw-bold">COMPLETED</h6>
                        <h4 class="text-success fw-bold mt-2"><?= $completed ?></h4>
                        <small class="text-muted">Finished visits</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow stat-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted fw-bold">CANCELLED</h6>
                        <h4 class="text-danger fw-bold mt-2"><?= $cancelled ?></h4>
                        <small class="text-muted">Cancelled appointments</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Revenue Alert -->
        <?php if ($pendingBilling > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                <strong>Pending Billing:</strong> ₱<?= number_format($pendingBilling, 2) ?> awaiting payment
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- REPORTS HEADER -->
        <div class="text-white p-4 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);">
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports & Analytics Center</h4>
            <p class="mb-0 mt-2 opacity-75">Generate and analyze data across all modules</p>
        </div>

        <!-- REPORT SECTION -->
        <div class="report-section">
            <div class="card shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 25px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-bar-graph"></i> Generate Reports</h5>
                            <select class="form-select" id="reportTypeSelect" style="width: 280px; background-color: rgba(255,255,255,0.95); padding: 10px 12px; font-size: 15px; border-radius: 6px; border: 2px solid rgba(255,255,255,0.3);" onchange="handleReportChange()">
                                <option value="patients">Patients Report</option>
                                <option value="doctors">Doctors Report</option>
                                <option value="appointments">Appointments Report</option>
                                <option value="billing">Billing Report</option>
                                <option value="visits">Visits Report</option>
                            </select>
                        </div>
                        <div class="btn-group" role="group" style="flex-wrap: wrap;">
                            <button type="button" class="btn btn-light fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#fieldSelectorModal" title="Select Fields">
                                <i class="bi bi-sliders"></i> Configure
                            </button>
                            <button type="button" class="btn btn-light fw-bold px-3 py-2" onclick="printReport()" title="Print Report">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <button type="button" class="btn btn-light fw-bold px-3 py-2" onclick="exportToPDF()" title="Export to PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-light fw-bold px-3 py-2" onclick="exportToCSV()" title="Export to CSV">
                                <i class="bi bi-file-earmark-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4" style="background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05) 0%, rgba(255, 255, 255, 0.5) 100%);">

                    <div class="report-branding-panel">
                        <div class="report-branding-summary">
                            <img src="<?= htmlspecialchars($reportLogoUrl) ?>" alt="<?= htmlspecialchars($reportSystemName) ?> Logo">
                            <div class="report-branding-copy">
                                <h5 class="fw-bold mb-1" id="reportBrandingTitle">Patients Report</h5>
                                <p class="small" id="reportBrandingSubtitle">Official <?= htmlspecialchars($reportSystemName) ?> report output.</p>
                            </div>
                        </div>
                        <div class="report-branding-meta">
                            <div class="report-branding-meta-item">
                                <span class="report-branding-meta-label">Generated By</span>
                                <span class="report-branding-meta-value" id="reportGeneratedByValue"><?= htmlspecialchars($reportGeneratedBy) ?></span>
                            </div>
                            <div class="report-branding-meta-item">
                                <span class="report-branding-meta-label">Generated On</span>
                                <span class="report-branding-meta-value" id="reportGeneratedOnValue">-</span>
                            </div>
                            <div class="report-branding-meta-item">
                                <span class="report-branding-meta-label">System</span>
                                <span class="report-branding-meta-value"><?= htmlspecialchars($reportSystemName) ?></span>
                            </div>
                            <div class="report-branding-meta-item">
                                <span class="report-branding-meta-label">Total Records</span>
                                <span class="report-branding-meta-value" id="reportTotalRecordsValue">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Spinner -->
                    <div id="reportLoader" class="text-center d-none mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3">Loading report data...</p>
                    </div>

                    <!-- Report Table -->
                    <div id="tableContainer">
                        <div class="row g-4 mb-4">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-speedometer2"></i> Key Metrics</h6>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;" id="tableMetricsContainer">
                                    <!-- Metrics will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="table-responsive" style="border-radius: 8px; overflow: hidden;">
                            <table id="reportTable" class="table table-striped table-hover table-bordered align-middle" style="margin-bottom: 0;">
                                <thead class="table-dark" id="tableHeaders" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
                                    <!-- Headers will be generated dynamically -->
                                </thead>
                                <tbody id="tableBody">
                                    <!-- Table data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>



    </div>
</div>

<!-- FIELD SELECTOR MODAL -->
<div class="modal fade" id="fieldSelectorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none;">
            <div class="modal-header text-white fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 25px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2"></i>Configure Report Fields</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <p class="text-muted mb-4"><i class="bi bi-info-circle me-2"></i>Select which fields to include in the report and export:</p>
                        <div id="fieldCheckboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                            <!-- Field checkboxes will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: rgba(102, 126, 234, 0.05); padding: 20px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllFields()"><i class="bi bi-x-circle me-1"></i> Clear All</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFields()"><i class="bi bi-check-circle me-1"></i> Select All</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="loadReportData()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;"><i class="bi bi-arrow-right me-1"></i> Apply & Generate</button>
            </div>
        </div>
    </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    let reportTable = null;
    let currentReportData = null;
    const fieldStorage = {};
    const reportBranding = <?= json_encode([
        'systemName' => $reportSystemName,
        'logoUrl' => $reportLogoUrl,
        'generatedBy' => $reportGeneratedBy,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;

    const fieldDefinitions = {
        'patients': {
            'patient_id': 'Patient ID',
            'last_name': 'Last Name',
            'first_name': 'First Name',
            'middle_initial': 'Middle Initial',
            'suffix': 'Suffix',
            'date_of_birth': 'Date of Birth',
            'age': 'Age',
            'sex': 'Sex',
            'address': 'Address',
            'contact_number': 'Contact Number',
            'email': 'Email',
            'emergency_contact_person': 'Emergency Contact',
            'emergency_contact_number': 'Emergency Number',
            'emergency_email': 'Emergency Email',
            'registered_date': 'Registered Date',
            'total_visits': 'Total Visits',
            'total_appointments': 'Total Appointments'
        },
        'doctors': {
            'doctor_id': 'Doctor ID',
            'last_name': 'Last Name',
            'first_name': 'First Name',
            'middle_initial': 'Middle Initial',
            'suffix': 'Suffix',
            'date_of_birth': 'Date of Birth',
            'age': 'Age',
            'sex': 'Sex',
            'address': 'Address',
            'contact_number': 'Contact Number',
            'email': 'Email',
            'emergency_contact_person': 'Emergency Contact',
            'emergency_contact_number': 'Emergency Number',
            'created_at': 'Registration Date',
            'total_appointments': 'Total Appointments',
            'completed_appointments': 'Completed Appointments'
        },
        'appointments': {
            'appointment_id': 'Appointment ID',
            'patient_name': 'Patient Name',
            'doctor_name': 'Doctor Name',
            'appointment_date': 'Appointment Date',
            'appointment_time': 'Appointment Time',
            'status': 'Status',
            'reason': 'Reason',
            'contact_number': 'Patient Contact',
            'patient_email': 'Patient Email',
            'created_at': 'Created Date'
        },
        'billing': {
            'billing_id': 'Bill ID',
            'invoice_id': 'Invoice ID',
            'patient_name': 'Patient Name',
            'doctor_name': 'Doctor Name',
            'description': 'Description',
            'amount': 'Amount',
            'status': 'Status',
            'invoice_date': 'Invoice Date',
            'created_at': 'Created Date',
            'updated_at': 'Updated Date'
        },
        'visits': {
            'visit_id': 'Visit ID',
            'patient_name': 'Patient Name',
            'doctor_name': 'Doctor Name',
            'visit_datetime': 'Visit Date & Time',
            'nature_of_visit': 'Nature of Visit',
            'affected_area': 'Affected Area',
            'symptoms': 'Symptoms',
            'observation': 'Observation',
            'procedure_done': 'Procedure Done',
            'meds_prescribed': 'Medications',
            'instruction_to_patient': 'Instructions',
            'remarks': 'Remarks'
        }
    };

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const initialType = document.getElementById('reportTypeSelect').value;
        loadFieldSelector(initialType);
        loadReportData();
    });

    function handleReportChange() {
        const type = document.getElementById('reportTypeSelect').value;
        loadFieldSelector(type);
        loadReportData();
    }

    function loadFieldSelector(type) {
        const fields = fieldDefinitions[type] || {};
        const container = document.getElementById('fieldCheckboxes');
        container.innerHTML = '';

        // Get stored selection or select first 5 fields by default
        let selectedFields = fieldStorage[type] || Object.keys(fields).slice(0, 5);

        Object.entries(fields).forEach(([key, label]) => {
            const isChecked = selectedFields.includes(key);
            const html = `
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox" id="field_${key}" value="${key}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="field_${key}">
                        ${label}
                    </label>
                </div>
            `;
            container.innerHTML += html;
        });
    }

    function getSelectedFields() {
        const checkboxes = document.querySelectorAll('.field-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function selectAllFields() {
        document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = true);
    }

    function deselectAllFields() {
        document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = false);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatReportValue(field, value) {
        if (value === null || value === undefined) {
            return '';
        }

        if (field === 'amount') {
            const numericValue = Number(value);
            if (!Number.isNaN(numericValue)) {
                return 'PHP ' + numericValue.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }

        return String(value);
    }

    function getReportTimestamp() {
        return new Date().toLocaleString('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getReportFilename(extension) {
        return `${currentReportData.type}_report_${new Date().toISOString().split('T')[0]}.${extension}`;
    }

    function updateReportBranding(data) {
        document.getElementById('reportBrandingTitle').textContent = data.title;
        document.getElementById('reportBrandingSubtitle').textContent = `Official ${reportBranding.systemName} report output.`;
        document.getElementById('reportGeneratedByValue').textContent = reportBranding.generatedBy;
        document.getElementById('reportGeneratedOnValue').textContent = getReportTimestamp();
        document.getElementById('reportTotalRecordsValue').textContent = data.total_records;
    }

    function buildReportTableMarkup() {
        const fieldLabels = currentReportData.fields.map(field => fieldDefinitions[currentReportData.type][field] || field);

        const tableHeader = fieldLabels.map(label => `<th>${escapeHtml(label)}</th>`).join('');
        const tableRows = currentReportData.data.map(row => {
            const cells = currentReportData.fields.map(field => {
                const formattedValue = formatReportValue(field, row[field]);
                return `<td>${escapeHtml(formattedValue)}</td>`;
            }).join('');

            return `<tr>${cells}</tr>`;
        }).join('');

        return `
            <table class="report-document-table">
                <thead>
                    <tr>${tableHeader}</tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        `;
    }

    function getReportDocumentStyles() {
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

    function waitForImages(container) {
        const images = Array.from(container.querySelectorAll('img'));

        if (images.length === 0) {
            return Promise.resolve();
        }

        return Promise.all(images.map(image => new Promise(resolve => {
            if (image.complete) {
                resolve();
                return;
            }

            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', resolve, { once: true });
        })));
    }

    function buildReportDocumentMarkup() {
        const generatedAt = getReportTimestamp();

        return `
            <div class="report-document">
                <div class="report-document-header">
                    <div class="report-document-brand">
                        <img src="${escapeHtml(reportBranding.logoUrl)}" alt="${escapeHtml(reportBranding.systemName)} Logo">
                        <div>
                            <h1>${escapeHtml(currentReportData.title)}</h1>
                            <p>${escapeHtml(reportBranding.systemName)} official report document</p>
                        </div>
                    </div>
                    <div class="report-document-meta">
                        <p><span>Generated on:</span> ${escapeHtml(generatedAt)}</p>
                        <p><span>Generated by:</span> ${escapeHtml(reportBranding.generatedBy)}</p>
                        <p><span>System:</span> ${escapeHtml(reportBranding.systemName)}</p>
                    </div>
                </div>

                <div class="report-document-summary">
                    <div class="report-document-summary-card">
                        <small>Total Records</small>
                        <strong>${escapeHtml(currentReportData.total_records)}</strong>
                    </div>
                    <div class="report-document-summary-card">
                        <small>Fields Selected</small>
                        <strong>${escapeHtml(currentReportData.fields.length)}</strong>
                    </div>
                    <div class="report-document-summary-card">
                        <small>Report Type</small>
                        <strong>${escapeHtml(currentReportData.title)}</strong>
                    </div>
                </div>

                ${buildReportTableMarkup()}

                <div class="report-document-footer">
                    Generated by ${escapeHtml(reportBranding.systemName)}. This document reflects the data available at the time of report generation.
                </div>
            </div>
        `;
    }

    async function loadReportData() {
        const reportType = document.getElementById('reportTypeSelect').value;
        // Ensure checkboxes exist before validating selected fields.
        if (document.querySelectorAll('.field-checkbox').length === 0) {
            loadFieldSelector(reportType);
        }

        const selectedFields = getSelectedFields();

        if (selectedFields.length === 0) {
            alert('Please select at least one field');
            return;
        }

        // Store selection
        fieldStorage[reportType] = selectedFields;

        document.getElementById('reportLoader').classList.remove('d-none');

        try {
            const fieldsParam = encodeURIComponent(JSON.stringify(selectedFields));
            const response = await fetch(`reports.php?action=generate&type=${reportType}&fields=${fieldsParam}`);
            const data = await response.json();

            if (data.error) {
                alert('Error loading report: ' + data.error);
                return;
            }

            currentReportData = data;
            displayTableReport(data);

        } catch (error) {
            console.error('Error loading report:', error);
            alert('Failed to load report data');
        } finally {
            document.getElementById('reportLoader').classList.add('d-none');
        }
    }

    function displayTableReport(data) {
        // Destroy existing DataTable instance BEFORE modifying the DOM to avoid
        // DataTables TN/18 "Incorrect column count" — DataTables caches column
        // structure internally, so teardown must happen before thead/tbody are rebuilt.
        if (reportTable) {
            reportTable.destroy();
            reportTable = null;
        }

        const tbody = document.getElementById('tableBody');
        const thead = document.getElementById('tableHeaders');
        tbody.innerHTML = '';
        thead.innerHTML = '';

        // Build headers
        let headerRow = '<tr>';
        data.fields.forEach(field => {
            const label = fieldDefinitions[data.type][field] || field;
            headerRow += `<th>${escapeHtml(label)}</th>`;
        });
        headerRow += '</tr>';
        thead.innerHTML = headerRow;

        // Build rows
        data.data.forEach(row => {
            let rowHtml = '<tr>';
            data.fields.forEach(field => {
                const value = formatReportValue(field, row[field]);
                rowHtml += `<td>${escapeHtml(value)}</td>`;
            });
            rowHtml += '</tr>';
            tbody.innerHTML += rowHtml;
        });

        // Initialize DataTable
        reportTable = $('#reportTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            "paging": true,
            "info": true,
            "lengthChange": true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">'
        });

        // Update metrics
        updateTableMetrics(data);
        updateReportBranding(data);
    }

    function updateTableMetrics(data) {
        const container = document.getElementById('tableMetricsContainer');
        container.innerHTML = '';

        const metrics = {
            'Total Records': data.total_records,
            'Report Type': data.title,
            'Fields Selected': data.fields.length,
            'Generated': new Date().toLocaleDateString('en-PH')
        };

        Object.entries(metrics).forEach(([label, value]) => {
            const html = `
                <div class="card text-center p-4 shadow-sm" style="border: 2px solid rgba(102, 126, 234, 0.1); border-radius: 10px; transition: all 0.3s ease;">
                    <h6 class="text-muted mb-2 fw-bold" style="font-size: 0.9rem;">${label}</h6>
                    <h3 class="fw-bold" style="color: #667eea; font-size: 1.8rem;">${value}</h3>
                </div>
            `;
            container.innerHTML += html;
        });
    }

    function exportToCSV() {
        if (!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            alert('No data to export. Generate a report first.');
            return;
        }

        const data = currentReportData.data;
        const fields = currentReportData.fields;
        const fieldLabels = fields.map(f => fieldDefinitions[currentReportData.type][f] || f);

        // Build CSV
        let csv = fieldLabels.join(',') + '\n';
        data.forEach(row => {
            const values = fields.map(field => {
                let value = formatReportValue(field, row[field]);
                if (typeof value === 'string' && (value.includes(',') || value.includes('"') || value.includes('\n'))) {
                    value = '"' + value.replace(/"/g, '""') + '"';
                }
                return value;
            });
            csv += values.join(',') + '\n';
        });

        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', getReportFilename('csv'));
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function printReport() {
        if (!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            alert('No data to print. Generate a report first.');
            return;
        }

        const printWindow = window.open('', '', 'height=700,width=1200');
        if (!printWindow) {
            alert('Unable to open the print preview window. Please allow pop-ups and try again.');
            return;
        }

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>${escapeHtml(currentReportData.title)} - Print View</title>
                ${getReportDocumentStyles()}
            </head>
            <body>
                ${buildReportDocumentMarkup()}
            </body>
            </html>
        `;

        printWindow.document.write(printContent);
        printWindow.document.close();

        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    function exportToPDF() {
        if (!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            alert('No data to export. Generate a report first.');
            return;
        }

        if (typeof html2pdf === 'undefined') {
            alert('PDF export library failed to load. Refresh the page and try again.');
            return;
        }

        const exportStyle = document.createElement('style');
        exportStyle.textContent = getReportDocumentStyles()
            .replace('<style>', '')
            .replace('</style>', '');
        document.head.appendChild(exportStyle);

        const exportHost = document.createElement('div');
        exportHost.style.position = 'fixed';
        exportHost.style.top = '0';
        exportHost.style.left = '50%';
        exportHost.style.transform = 'translateX(-50%)';
        exportHost.style.width = '1120px';
        exportHost.style.padding = '0';
        exportHost.style.margin = '0';
        exportHost.style.pointerEvents = 'none';
        exportHost.style.zIndex = '2147483647';
        exportHost.style.background = '#ffffff';
        exportHost.innerHTML = buildReportDocumentMarkup();
        document.body.appendChild(exportHost);

        const reportDocument = exportHost.querySelector('.report-document');

        if (!reportDocument) {
            document.body.removeChild(exportHost);
            document.head.removeChild(exportStyle);
            alert('Failed to prepare the report for PDF export.');
            return;
        }

        reportDocument.style.opacity = '1';
        reportDocument.style.visibility = 'visible';
        reportDocument.style.background = '#ffffff';

        const opt = {
            margin: 10,
            filename: getReportFilename('pdf'),
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0,
                windowWidth: 1120
            },
            jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
        };

        waitForImages(reportDocument)
            .then(() => new Promise(resolve => requestAnimationFrame(() => resolve())))
            .then(() => html2pdf().set(opt).from(reportDocument).save())
            .then(() => {
                document.body.removeChild(exportHost);
                document.head.removeChild(exportStyle);
            })
            .catch(error => {
                console.error('PDF export failed:', error);
                document.body.removeChild(exportHost);
                document.head.removeChild(exportStyle);
                alert('Failed to export the report as PDF.');
            });
    }
</script>

</body>
</html>