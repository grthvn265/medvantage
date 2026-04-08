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
        <div class="bg-dark text-white p-3 mb-4" style="border-radius: 5px;">
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Reports & Analytics</h4>
        </div>

        <!-- REPORT SECTION -->
        <div class="report-section">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-bar-graph"></i> Reports</h5>
                            <select class="form-select" id="reportTypeSelect" style="width: 280px; background-color: rgba(255,255,255,0.9); padding: 10px 12px; font-size: 15px;" onchange="handleReportChange()">
                                <option value="patients">Patients Report</option>
                                <option value="doctors">Doctors Report</option>
                                <option value="appointments">Appointments Report</option>
                                <option value="billing">Billing Report</option>
                                <option value="visits">Visits Report</option>
                            </select>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#fieldSelectorModal" title="Select Fields">
                                <i class="bi bi-sliders"></i> Configure Fields
                            </button>
                            <button type="button" class="btn btn-light fw-bold" onclick="exportToPDF()" title="Export to PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-light fw-bold" onclick="exportToCSV()" title="Export to CSV">
                                <i class="bi bi-file-earmark-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">

                    <!-- Loading Spinner -->
                    <div id="reportLoader" class="text-center d-none mb-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div id="tableContainer">
                        <div class="row g-4 mb-4">
                            <div class="col-lg-12">
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold text-dark">Key Metrics</h6>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;" id="tableMetricsContainer">
                                    <!-- Metrics will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="reportTable" class="table table-striped table-hover table-bordered align-middle">
                                <thead class="table-dark" id="tableHeaders">
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
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Select Report Fields</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <p class="text-muted mb-3">Select which fields to include in the report and export:</p>
                        <div id="fieldCheckboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                            <!-- Field checkboxes will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllFields()">Select All</button>
                <button type="button" class="btn btn-sm btn-warning" onclick="deselectAllFields()">Deselect All</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="loadReportData()">Apply & Generate Report</button>
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
        if (reportTable) reportTable.destroy();
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
            'Generate Date': new Date().toLocaleDateString('en-PH')
        };

        Object.entries(metrics).forEach(([label, value]) => {
            const html = `
                <div class="card text-center p-3 shadow-sm">
                    <h6 class="text-muted mb-2">${label}</h6>
                    <h3 class="fw-bold text-primary">${value}</h3>
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

    function exportToPDF() {
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