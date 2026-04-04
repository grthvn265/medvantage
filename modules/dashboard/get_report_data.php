<?php
header('Content-Type: application/json; charset=utf-8');

require '../../components/db.php';

$reportType = isset($_GET['type']) ? $_GET['type'] : 'appointments';

$response = [];

try {
    switch ($reportType) {
        case 'appointments':
            // Appointment Status Distribution
            $scheduled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Scheduled'")->fetchColumn();
            $completed = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Completed'")->fetchColumn();
            $cancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Cancelled'")->fetchColumn();
            
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
            $paid = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Paid'")->fetchColumn() ?: 0;
            $pending = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Pending'")->fetchColumn() ?: 0;
            $cancelled = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Cancelled'")->fetchColumn() ?: 0;
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
            $monthlyBilling = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stmt = $pdo->prepare("SELECT SUM(amount) FROM billing WHERE status='Paid' AND DATE_FORMAT(created_at,'%Y-%m') = ?");
                $stmt->execute([$month]);
                $amount = $stmt->fetchColumn() ?: 0;
                $monthlyBilling[date('M Y', strtotime($month . '-01'))] = $amount;
            }
            $response['secondaryChart']['labels'] = array_keys($monthlyBilling);
            $response['secondaryChart']['data'] = array_values($monthlyBilling);
            $response['secondaryChart']['borderColor'] = '#667eea';
            break;

        case 'revenue':
            // Revenue Trend
            $monthlyRevenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stmt = $pdo->prepare("SELECT SUM(amount) FROM billing WHERE status='Paid' AND DATE_FORMAT(created_at,'%Y-%m') = ?");
                $stmt->execute([$month]);
                $amount = $stmt->fetchColumn() ?: 0;
                $monthlyRevenue[date('M Y', strtotime($month . '-01'))] = $amount;
            }
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
            $paid = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Paid'")->fetchColumn() ?: 0;
            $pending = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Pending'")->fetchColumn() ?: 0;
            $cancelled = $pdo->query("SELECT SUM(amount) FROM billing WHERE status='Cancelled'")->fetchColumn() ?: 0;
            
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
            $visitsByMonth = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE DATE_FORMAT(visit_datetime,'%Y-%m') = ?");
                $stmt->execute([$month]);
                $count = $stmt->fetchColumn();
                $visitsByMonth[date('M Y', strtotime($month . '-01'))] = $count;
            }
            
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
