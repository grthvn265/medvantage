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
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Reports</h4>
        </div>

        <!-- REPORT SECTION -->
        <div class="report-section">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-bar-graph"></i> Reports & Analytics</h5>
                            <select class="form-select" id="reportTypeSelect" style="width: 280px; background-color: rgba(255,255,255,0.9); padding: 10px 12px; font-size: 15px;" onchange="loadReport()">
                                <option value="appointments">Appointment Analytics</option>
                                <option value="billing">Billing Status</option>
                                <option value="revenue">Revenue Analysis</option>
                                <option value="doctors">Doctor Performance</option>
                                <option value="visits">Visits Analytics</option>
                                <option value="patients">Patients Report</option>
                            </select>
                        </div>
                        <div class="btn-group" role="group">
                            </button>
                            <button type="button" class="btn btn-light fw-bold" onclick="exportToPDF()" title="Export to PDF" style="padding: 10px 16px; font-size: 15px;">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-light fw-bold" onclick="exportToCSV()" title="Export to CSV" style="padding: 10px 16px; font-size: 15px;">
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

                    <!-- Charts Row -->
                    <div id="chartsContainer" class="d-none">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold text-dark" id="mainChartTitle">Appointment Status</h6>
                                </div>
                                <div class="chart-container">
                                    <canvas id="mainChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold text-dark" id="secondaryChartTitle">Doctor Performance</h6>
                                </div>
                                <div class="chart-container">
                                    <canvas id="secondaryChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold text-dark">Key Metrics</h6>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;" id="metricsContainer">
                                    <!-- Metrics will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Display -->
                    <div id="tableContainer" class="d-none">
                        <div class="row g-4 mb-4">
                            <div class="col-lg-12">
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold text-dark">Key Metrics</h6>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;" id="tableMetricsContainer">
                                    <!-- Table metrics will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="reportTable" class="table table-striped table-hover table-bordered align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Total Visits</th>
                                    </tr>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    let mainChart = null;
    let secondaryChart = null;
    let reportTable = null;

    async function loadReport() {
        const reportType = document.getElementById('reportTypeSelect').value;
        
        // Show loader
        document.getElementById('reportLoader').classList.remove('d-none');
        
        try {
            const response = await fetch(`get_report_data.php?type=${reportType}`);
            const data = await response.json();
            
            if (data.error) {
                alert('Error loading report: ' + data.error);
                return;
            }
            
            // Destroy existing charts
            if (mainChart) mainChart.destroy();
            if (secondaryChart) secondaryChart.destroy();
            if (reportTable) reportTable.destroy();
            
            // Check if this is a table-type report
            if (data.type === 'table') {
                displayTableReport(data);
            } else {
                displayChartReport(data);
            }
            
        } catch (error) {
            console.error('Error loading report:', error);
            alert('Failed to load report data');
        } finally {
            // Hide loader
            document.getElementById('reportLoader').classList.add('d-none');
        }
    }

    function displayChartReport(data) {
        // Hide table, show charts
        document.getElementById('chartsContainer').classList.remove('d-none');
        document.getElementById('tableContainer').classList.add('d-none');
        
        // Update chart titles
        document.getElementById('mainChartTitle').textContent = data.mainChart.title || 'Main Chart';
        document.getElementById('secondaryChartTitle').textContent = data.secondaryChart.title || 'Secondary Chart';
        
        // Create main chart
        const mainCtx = document.getElementById('mainChart').getContext('2d');
        mainChart = new Chart(mainCtx, createChartConfig(data.chartType, data.mainChart));
        
        // Create secondary chart
        const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
        const secondaryType = data.secondaryChart.type || 'bar';
        secondaryChart = new Chart(secondaryCtx, createChartConfig(secondaryType, data.secondaryChart));
        
        // Update metrics
        updateMetrics(data.keyMetrics);
    }

    function displayTableReport(data) {
        // Hide charts, show table
        document.getElementById('chartsContainer').classList.add('d-none');
        document.getElementById('tableContainer').classList.remove('d-none');
        
        // Update metrics
        updateTableMetrics(data.keyMetrics);
        
        // Populate table
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';
        
        data.data.forEach(patient => {
            const row = `
                <tr>
                    <td><strong>#${patient.patient_id}</strong></td>
                    <td>${patient.last_name}, ${patient.first_name}</td>
                    <td><span class="badge bg-primary">${patient.age} yrs</span></td>
                    <td>${patient.sex}</td>
                    <td>${patient.patient_number || 'N/A'}</td>
                    <td>${patient.email_address || 'N/A'}</td>
                    <td><span class="badge bg-info">${patient.total_visits}</span></td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
        
        // Initialize DataTable
        if (reportTable) reportTable.destroy();
        reportTable = $('#reportTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            "lengthChange": true
        });
    }

    function createChartConfig(chartType, chartData) {
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 11, weight: 'bold' }
                    }
                }
            }
        };

        if (chartType === 'doughnut') {
            return {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.backgroundColor || ['#0dcaf0', '#198754', '#dc3545'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: commonOptions
            };
        } else if (chartType === 'line') {
            return {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: chartData.label || 'Trend',
                        data: chartData.data,
                        borderColor: chartData.borderColor || '#667eea',
                        backgroundColor: chartData.backgroundColor || 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: chartData.borderColor || '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (chartData.isCurrency) {
                                        return '₱' + value.toLocaleString();
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                }
            };
        } else if (chartType === 'bar') {
            return {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: chartData.label || 'Count',
                        data: chartData.data,
                        backgroundColor: chartData.backgroundColor || '#764ba2',
                        borderColor: '#667eea',
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: chartData.indexAxis || 'x',
                    scales: {
                        x: {
                            beginAtZero: true
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
        }
    }

    function updateMetrics(metrics) {
        const metricsContainer = document.getElementById('metricsContainer');
        metricsContainer.innerHTML = '';
        
        const colorMap = {
            'text-primary': 'linear-gradient(135deg, #667eea15 0%, #764ba215 100%)',
            'text-success': 'linear-gradient(135deg, #28a74515 0%, #20c99715 100%)',
            'text-info': 'linear-gradient(135deg, #0dcaf015 0%, #0099ff15 100%)',
            'text-warning': 'linear-gradient(135deg, #ffc10715 0%, #ff952515 100%)',
            'text-danger': 'linear-gradient(135deg, #dc354515 0%, #ff6b6b15 100%)'
        };
        
        metrics.forEach(metric => {
            const gradient = colorMap[metric.color] || colorMap['text-primary'];
            const metricHtml = `
                <div class="text-center p-3" style="background: ${gradient}; border-radius: 10px;">
                    <h4 class="fw-bold ${metric.color} mb-1">${metric.value}</h4>
                    <small class="text-muted">${metric.label}</small>
                </div>
            `;
            metricsContainer.innerHTML += metricHtml;
        });
    }

    function updateTableMetrics(metrics) {
        const metricsContainer = document.getElementById('tableMetricsContainer');
        metricsContainer.innerHTML = '';
        
        const colorMap = {
            'text-primary': 'linear-gradient(135deg, #667eea15 0%, #764ba215 100%)',
            'text-success': 'linear-gradient(135deg, #28a74515 0%, #20c99715 100%)',
            'text-info': 'linear-gradient(135deg, #0dcaf015 0%, #0099ff15 100%)',
            'text-warning': 'linear-gradient(135deg, #ffc10715 0%, #ff952515 100%)',
            'text-danger': 'linear-gradient(135deg, #dc354515 0%, #ff6b6b15 100%)'
        };
        
        metrics.forEach(metric => {
            const gradient = colorMap[metric.color] || colorMap['text-primary'];
            const metricHtml = `
                <div class="text-center p-3" style="background: ${gradient}; border-radius: 10px;">
                    <h4 class="fw-bold ${metric.color} mb-1">${metric.value}</h4>
                    <small class="text-muted">${metric.label}</small>
                </div>
            `;
            metricsContainer.innerHTML += metricHtml;
        });
    }

    // Export Functions
    function exportToExcel() {
        const reportType = document.getElementById('reportTypeSelect').value;
        const timestamp = new Date().toISOString().split('T')[0];
        
        if (reportType === 'patients') {
            // Export patients table
            const table = document.getElementById('reportTable');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Patients");
            XLSX.writeFile(wb, `${reportType}_Report_${timestamp}.xlsx`);
        } else {
            // Export metrics as structured data for chart reports
            const metrics = [];
            document.querySelectorAll('#metricsContainer > div').forEach(metric => {
                const label = metric.querySelector('small').textContent;
                const value = metric.querySelector('h4').textContent;
                metrics.push({ 'Metric': label, 'Value': value });
            });
            
            const ws = XLSX.utils.json_to_sheet(metrics);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Report");
            XLSX.writeFile(wb, `${reportType}_Report_${timestamp}.xlsx`);
        }
    }

    function exportToCSV() {
        const reportType = document.getElementById('reportTypeSelect').value;
        const timestamp = new Date().toISOString().split('T')[0];
        
        let csv = '';
        
        if (reportType === 'patients') {
            csv = 'Patient ID,Name,Age,Gender,Contact,Email,Total Visits\n';
            document.querySelectorAll('#reportTable tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const patientId = cells[0].textContent.replace(/[#\s]/g, '');
                const name = cells[1].textContent.replace(/"/g, '""');
                const age = cells[2].textContent.replace(/[^\d]/g, '');
                const gender = cells[3].textContent;
                const contact = cells[4].textContent;
                const email = cells[5].textContent;
                const visits = cells[6].textContent.replace(/[^\d]/g, '');
                csv += `"${patientId}","${name}","${age}","${gender}","${contact}","${email}","${visits}"\n`;
            });
        } else {
            csv = 'Report Type,Metric,Value\n';
            document.querySelectorAll('#metricsContainer > div').forEach(metric => {
                const label = metric.querySelector('small').textContent;
                const value = metric.querySelector('h4').textContent;
                csv += `"${reportType}","${label}","${value}"\n`;
            });
        }
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", `${reportType}_Report_${timestamp}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF() {
        const reportType = document.getElementById('reportTypeSelect').value;
        const timestamp = new Date().toISOString().split('T')[0];
        
        let element;
        if (reportType === 'patients') {
            element = document.getElementById('tableContainer');
        } else {
            element = document.getElementById('chartsContainer');
        }
        
        const opt = {
            margin: 10,
            filename: `${reportType}_Report_${timestamp}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };
        
        html2pdf().set(opt).from(element).save();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadReport(); // Load default report
    });
</script>

</body>
</html>