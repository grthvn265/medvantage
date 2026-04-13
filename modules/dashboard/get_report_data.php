<?php
header('Content-Type: application/json; charset=utf-8');

require '../../components/db.php';

$reportType = isset($_GET['type']) ? $_GET['type'] : 'appointments';

$response = [];

function buildRecentMonthBuckets(int $months = 6): array
{
    $buckets = [];

    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $label = date('M Y', strtotime($month . '-01'));
        $buckets[$label] = [
            'ym' => $month,
            'value' => 0,
        ];
    }

    return $buckets;
}

function fetchPaidBillingByMonth(PDO $pdo, int $months = 6): array
{
    $buckets = buildRecentMonthBuckets($months);
    $ymToLabel = [];

    foreach ($buckets as $label => $bucket) {
        $ymToLabel[$bucket['ym']] = $label;
    }

    $startDate = date('Y-m-01 00:00:00', strtotime('-' . ($months - 1) . ' months'));

    $stmt = $pdo->prepare("\n        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(amount), 0) AS total\n        FROM billing\n        WHERE status = 'Paid' AND created_at >= ?\n        GROUP BY ym\n    ");
    $stmt->execute([$startDate]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ym = (string) ($row['ym'] ?? '');
        if (isset($ymToLabel[$ym])) {
            $buckets[$ymToLabel[$ym]]['value'] = (float) $row['total'];
        }
    }

    return array_map(static fn(array $bucket): float => (float) $bucket['value'], $buckets);
}

function fetchVisitsByMonth(PDO $pdo, int $months = 6): array
{
    $buckets = buildRecentMonthBuckets($months);
    $ymToLabel = [];

    foreach ($buckets as $label => $bucket) {
        $ymToLabel[$bucket['ym']] = $label;
    }

    $startDate = date('Y-m-01 00:00:00', strtotime('-' . ($months - 1) . ' months'));

    $stmt = $pdo->prepare("\n        SELECT DATE_FORMAT(visit_datetime, '%Y-%m') AS ym, COUNT(*) AS total\n        FROM visits\n        WHERE visit_datetime >= ?\n        GROUP BY ym\n    ");
    $stmt->execute([$startDate]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ym = (string) ($row['ym'] ?? '');
        if (isset($ymToLabel[$ym])) {
            $buckets[$ymToLabel[$ym]]['value'] = (int) $row['total'];
        }
    }

    return array_map(static fn(array $bucket): int => (int) $bucket['value'], $buckets);
}

try {
    switch ($reportType) {
        case 'appointments':
            // Appointment Status Distribution
            $statusRow = $pdo->query("\n                SELECT\n                    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,\n                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,\n                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled\n                FROM appointments\n            ")->fetch(PDO::FETCH_ASSOC) ?: [];

            $scheduled = (int) ($statusRow['scheduled'] ?? 0);
            $completed = (int) ($statusRow['completed'] ?? 0);
            $cancelled = (int) ($statusRow['cancelled'] ?? 0);
            
            $response = [
                'chartType' => 'doughnut',
                'mainChart' => [
                    'labels' => ['Scheduled', 'Completed', 'Cancelled'],
                    'data' => [$scheduled, $completed, $cancelled],
                    'backgroundColor' => ['#0dcaf0', '#198754', '#dc3545']
                ],
                'secondaryChart' => [
                    'title' => 'Appointments by Doctor (Last 30 days)',
                    'type' => 'bar',
                    'indexAxis' => 'y'
                ],
                'keyMetrics' => [
                    ['label' => 'Scheduled', 'value' => $scheduled, 'color' => 'text-info'],
                    ['label' => 'Completed', 'value' => $completed, 'color' => 'text-success'],
                    ['label' => 'Cancelled', 'value' => $cancelled, 'color' => 'text-danger']
                ]
            ];
            
            // Get doctor performance data
            $stmt = $pdo->query("
                SELECT d.doctor_id, d.first_name, d.last_name, COUNT(a.appointment_id) as count
                FROM doctors d
                LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY d.doctor_id
                ORDER BY count DESC
                LIMIT 5
            ");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $doctorLabels = [];
            $doctorData = [];
            foreach ($doctors as $doctor) {
                $doctorLabels[] = $doctor['first_name'] . ' ' . $doctor['last_name'];
                $doctorData[] = $doctor['count'];
            }
            $response['secondaryChart']['labels'] = $doctorLabels;
            $response['secondaryChart']['data'] = $doctorData;
            $response['secondaryChart']['backgroundColor'] = '#764ba2';
            break;

        case 'billing':
            // Billing Status Distribution
            $billingStatus = $pdo->query("\n                SELECT\n                    COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) AS paid,\n                    COALESCE(SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END), 0) AS pending,\n                    COALESCE(SUM(CASE WHEN status = 'Cancelled' THEN amount ELSE 0 END), 0) AS cancelled\n                FROM billing\n            ")->fetch(PDO::FETCH_ASSOC) ?: [];

            $paid = (float) ($billingStatus['paid'] ?? 0);
            $pending = (float) ($billingStatus['pending'] ?? 0);
            $cancelled = (float) ($billingStatus['cancelled'] ?? 0);
            $total = $paid + $pending + $cancelled;
            
            $response = [
                'chartType' => 'doughnut',
                'mainChart' => [
                    'labels' => ['Paid', 'Pending', 'Cancelled'],
                    'data' => [$paid, $pending, $cancelled],
                    'backgroundColor' => ['#198754', '#ffc107', '#dc3545']
                ],
                'secondaryChart' => [
                    'title' => 'Billing by Month (Last 6 months)',
                    'type' => 'line'
                ],
                'keyMetrics' => [
                    ['label' => 'Paid', 'value' => '₱' . number_format($paid, 0), 'color' => 'text-success'],
                    ['label' => 'Pending', 'value' => '₱' . number_format($pending, 0), 'color' => 'text-warning'],
                    ['label' => 'Total', 'value' => '₱' . number_format($total, 0), 'color' => 'text-primary']
                ]
            ];
            
            // Get billing by month
            $monthlyBilling = fetchPaidBillingByMonth($pdo, 6);
            $response['secondaryChart']['labels'] = array_keys($monthlyBilling);
            $response['secondaryChart']['data'] = array_values($monthlyBilling);
            $response['secondaryChart']['borderColor'] = '#667eea';
            break;

        case 'revenue':
            // Revenue Trend
            $monthlyRevenue = fetchPaidBillingByMonth($pdo, 6);
            $totalRevenue = array_sum($monthlyRevenue);
            $avgRevenue = count($monthlyRevenue) > 0 ? $totalRevenue / count($monthlyRevenue) : 0;
            
            $response = [
                'chartType' => 'line',
                'mainChart' => [
                    'labels' => array_keys($monthlyRevenue),
                    'data' => array_values($monthlyRevenue),
                    'borderColor' => '#667eea',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.1)'
                ],
                'secondaryChart' => [
                    'title' => 'Billing Status Distribution',
                    'type' => 'doughnut'
                ],
                'keyMetrics' => [
                    ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 0), 'color' => 'text-success'],
                    ['label' => 'Avg Monthly', 'value' => '₱' . number_format($avgRevenue, 0), 'color' => 'text-info'],
                    ['label' => 'Current Month', 'value' => '₱' . number_format(end($monthlyRevenue), 0), 'color' => 'text-primary']
                ]
            ];
            
            // Get billing status
            $billingStatus = $pdo->query("\n                SELECT\n                    COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) AS paid,\n                    COALESCE(SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END), 0) AS pending,\n                    COALESCE(SUM(CASE WHEN status = 'Cancelled' THEN amount ELSE 0 END), 0) AS cancelled\n                FROM billing\n            ")->fetch(PDO::FETCH_ASSOC) ?: [];

            $paid = (float) ($billingStatus['paid'] ?? 0);
            $pending = (float) ($billingStatus['pending'] ?? 0);
            $cancelled = (float) ($billingStatus['cancelled'] ?? 0);
            
            $response['secondaryChart']['labels'] = ['Paid', 'Pending', 'Cancelled'];
            $response['secondaryChart']['data'] = [$paid, $pending, $cancelled];
            $response['secondaryChart']['backgroundColor'] = ['#198754', '#ffc107', '#dc3545'];
            break;

        case 'doctors':
            // Doctor Performance
            $stmt = $pdo->query("
                SELECT d.doctor_id, d.first_name, d.last_name, 
                       COUNT(a.appointment_id) as appointments,
                       COUNT(DISTINCT v.visit_id) as visits
                FROM doctors d
                LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                LEFT JOIN visits v ON d.doctor_id = v.doctor_id AND v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY d.doctor_id
                ORDER BY appointments DESC
                LIMIT 5
            ");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $doctorLabels = [];
            $appointmentData = [];
            foreach ($doctors as $doctor) {
                $doctorLabels[] = $doctor['first_name'] . ' ' . $doctor['last_name'];
                $appointmentData[] = $doctor['appointments'];
            }
            
            $response = [
                'chartType' => 'bar',
                'mainChart' => [
                    'labels' => $doctorLabels,
                    'data' => $appointmentData,
                    'backgroundColor' => '#764ba2'
                ],
                'secondaryChart' => [
                    'title' => 'Doctor Workload (Last 30 days)',
                    'type' => 'bar'
                ],
                'keyMetrics' => [
                    ['label' => 'Total Doctors', 'value' => count($doctors), 'color' => 'text-primary'],
                    ['label' => 'Avg Appointments', 'value' => (int)(array_sum($appointmentData) / max(1, count($appointmentData))), 'color' => 'text-info'],
                    ['label' => 'Top Doctor', 'value' => $doctorLabels[0] ?? 'N/A', 'color' => 'text-success']
                ]
            ];
            break;

        case 'visits':
            // Visits Analytics
            $totalVisits = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
            $visitsByMonth = fetchVisitsByMonth($pdo, 6);
            
            $response = [
                'chartType' => 'line',
                'mainChart' => [
                    'labels' => array_keys($visitsByMonth),
                    'data' => array_values($visitsByMonth),
                    'borderColor' => '#667eea',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.1)'
                ],
                'secondaryChart' => [
                    'title' => 'Visits by Doctor',
                    'type' => 'bar'
                ],
                'keyMetrics' => [
                    ['label' => 'Total Visits', 'value' => $totalVisits, 'color' => 'text-primary'],
                    ['label' => 'Current Month', 'value' => end($visitsByMonth), 'color' => 'text-info'],
                    ['label' => 'Avg Monthly', 'value' => (int)(array_sum($visitsByMonth) / count($visitsByMonth)), 'color' => 'text-success']
                ]
            ];
            
            // Get visits by doctor
            $stmt = $pdo->query("
                SELECT d.first_name, d.last_name, COUNT(v.visit_id) as count
                FROM doctors d
                LEFT JOIN visits v ON d.doctor_id = v.doctor_id
                GROUP BY d.doctor_id
                ORDER BY count DESC
                LIMIT 5
            ");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $doctorLabels = [];
            $doctorData = [];
            foreach ($doctors as $doctor) {
                $doctorLabels[] = $doctor['first_name'] . ' ' . $doctor['last_name'];
                $doctorData[] = $doctor['count'];
            }
            $response['secondaryChart']['labels'] = $doctorLabels;
            $response['secondaryChart']['data'] = $doctorData;
            $response['secondaryChart']['backgroundColor'] = '#764ba2';
            break;

        case 'patients':
            // Patients Report
            $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
            $stmt = $pdo->query("
                SELECT p.*, 
                       YEAR(NOW()) - YEAR(p.date_of_birth) AS age,
                       COUNT(v.visit_id) as total_visits
                FROM patients p
                LEFT JOIN visits v ON p.patient_id = v.patient_id
                GROUP BY p.patient_id
                ORDER BY p.last_name ASC
            ");
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $avgAge = 0;
            $maleCount = 0;
            $femaleCount = 0;
            $otherCount = 0;
            
            foreach ($patients as $patient) {
                $avgAge += $patient['age'];
                if ($patient['sex'] === 'Male') $maleCount++;
                elseif ($patient['sex'] === 'Female') $femaleCount++;
                else $otherCount++;
            }
            $avgAge = count($patients) > 0 ? round($avgAge / count($patients), 1) : 0;
            
            $response = [
                'type' => 'table',
                'title' => 'Patients Report',
                'data' => $patients,
                'keyMetrics' => [
                    ['label' => 'Total Patients', 'value' => $totalPatients, 'color' => 'text-primary'],
                    ['label' => 'Average Age', 'value' => $avgAge . ' yrs', 'color' => 'text-info'],
                    ['label' => 'Male', 'value' => $maleCount, 'color' => 'text-success'],
                    ['label' => 'Female', 'value' => $femaleCount, 'color' => 'text-danger']
                ]
            ];
            break;

        default:
            $response = ['error' => 'Invalid report type'];
            break;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
