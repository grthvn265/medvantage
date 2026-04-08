<?php
require '../../components/db.php';
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
            headerRow += `<th>${label}</th>`;
        });
        headerRow += '</tr>';
        thead.innerHTML = headerRow;

        // Build rows
        data.data.forEach(row => {
            let rowHtml = '<tr>';
            data.fields.forEach(field => {
                let value = row[field] || '';
                // Format currency
                if (field === 'amount' || field === 'due_date' || field === 'paid_date') {
                    if (field === 'amount') {
                        value = '₱' + (typeof value === 'number' ? value.toLocaleString('en-PH', {minimumFractionDigits: 2}) : value);
                    }
                }
                rowHtml += `<td>${value}</td>`;
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
                let value = row[field] || '';
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
        link.setAttribute('download', `${currentReportData.type}_report_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printReport() {
        if (!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            alert('No data to print. Generate a report first.');
            return;
        }

        // Create a new window for printing
        const printWindow = window.open('', '', 'height=700,width=1200');
        const htmlContent = document.documentElement.innerHTML;
        
        // Get CSS styles
        const styles = `
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    color: #333;
                    background: white;
                    padding: 20px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #667eea;
                    padding-bottom: 20px;
                }
                .print-header h2 {
                    color: #667eea;
                    font-size: 28px;
                    margin-bottom: 5px;
                }
                .print-header p {
                    color: #666;
                    font-size: 14px;
                }
                .print-info {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                }
                .print-info-item {
                    padding: 10px;
                }
                .print-info-label {
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 5px;
                    font-weight: 600;
                }
                .print-info-value {
                    font-size: 18px;
                    color: #667eea;
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    background: white;
                }
                thead {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }
                th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    border: 1px solid #ddd;
                }
                td {
                    padding: 12px 15px;
                    border: 1px solid #ddd;
                }
                tbody tr:nth-child(odd) {
                    background: #f9f9f9;
                }
                tbody tr:hover {
                    background: #f0f0f0;
                }
                .print-footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #999;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                }
                @media print {
                    body {
                        padding: 0;
                    }
                    @page {
                        size: A4 landscape;
                        margin: 10mm;
                    }
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        `;

        const data = currentReportData.data;
        const fields = currentReportData.fields;
        const fieldLabels = fields.map(f => fieldDefinitions[currentReportData.type][f] || f);

        // Build table rows
        let tableRows = '';
        data.forEach(row => {
            tableRows += '<tr>';
            fields.forEach(field => {
                let value = row[field] || '';
                if (field === 'amount') {
                    value = '₱' + (typeof value === 'number' ? value.toLocaleString('en-PH', {minimumFractionDigits: 2}) : value);
                }
                tableRows += `<td>${value}</td>`;
            });
            tableRows += '</tr>';
        });

        const reportDate = new Date().toLocaleDateString('en-PH', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>${currentReportData.type}_report_${new Date().toISOString().split('T')[0]}</title>
                ${styles}
            </head>
            <body>
                <div class="print-header">
                    <h2><i>📊</i> ${currentReportData.title}</h2>
                    <p>Generated on ${reportDate}</p>
                </div>

                <div class="print-info">
                    <div class="print-info-item">
                        <div class="print-info-label">Total Records</div>
                        <div class="print-info-value">${currentReportData.total_records}</div>
                    </div>
                    <div class="print-info-item">
                        <div class="print-info-label">Report Type</div>
                        <div class="print-info-value">${currentReportData.title}</div>
                    </div>
                    <div class="print-info-item">
                        <div class="print-info-label">Fields Selected</div>
                        <div class="print-info-value">${fields.length}</div>
                    </div>
                    <div class="print-info-item">
                        <div class="print-info-label">Generated Date</div>
                        <div class="print-info-value">${new Date().toLocaleDateString('en-PH')}</div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            ${fieldLabels.map(label => `<th>${label}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>

                <div class="print-footer">
                    <p>This is an auto-generated report from the Patient Information System.</p>
                    <p style="margin-top: 10px;">© ${new Date().getFullYear()} MedVantage - All Rights Reserved</p>
                </div>
            </body>
            </html>
        `;

        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load before printing
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    function exportToCSV() {
        if (!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            alert('No data to export. Generate a report first.');
            return;
        }

        const element = document.getElementById('reportTable');
        const opt = {
            margin: 10,
            filename: `${currentReportData.type}_report_${new Date().toISOString().split('T')[0]}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
        };

        html2pdf().set(opt).from(element).save();
    }
</script>

</body>
</html>