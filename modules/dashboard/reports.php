<?php
require '../../components/db.php';

header('Content-Type: application/json; charset=utf-8');

function sendJsonResponse($payload, $statusCode = 200) {
    http_response_code($statusCode);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    if ($json === false) {
        http_response_code(500);
        $fallback = json_encode([
            'error' => 'Failed to encode response data: ' . json_last_error_msg()
        ]);
        echo $fallback !== false ? $fallback : '{"error":"Failed to encode response data"}';
        return;
    }

    echo $json;
}

$action = $_GET['action'] ?? '';
$reportType = $_GET['type'] ?? '';

// Define available fields for each module
$availableFields = [
    'patients' => [
        'patient_id' => 'Patient ID',
        'last_name' => 'Last Name',
        'first_name' => 'First Name',
        'middle_initial' => 'Middle Initial',
        'suffix' => 'Suffix',
        'date_of_birth' => 'Date of Birth',
        'age' => 'Age',
        'sex' => 'Sex',
        'address' => 'Address',
        'contact_number' => 'Contact Number',
        'email' => 'Email',
        'emergency_contact_person' => 'Emergency Contact',
        'emergency_contact_number' => 'Emergency Number',
        'emergency_email' => 'Emergency Email',
        'registered_date' => 'Registered Date',
        'total_visits' => 'Total Visits',
        'total_appointments' => 'Total Appointments'
    ],
    'doctors' => [
        'doctor_id' => 'Doctor ID',
        'last_name' => 'Last Name',
        'first_name' => 'First Name',
        'middle_initial' => 'Middle Initial',
        'suffix' => 'Suffix',
        'date_of_birth' => 'Date of Birth',
        'age' => 'Age',
        'sex' => 'Sex',
        'address' => 'Address',
        'contact_number' => 'Contact Number',
        'email' => 'Email',
        'emergency_contact_person' => 'Emergency Contact',
        'emergency_contact_number' => 'Emergency Number',
        'created_at' => 'Registration Date',
        'total_appointments' => 'Total Appointments',
        'completed_appointments' => 'Completed Appointments'
    ],
    'appointments' => [
        'appointment_id' => 'Appointment ID',
        'patient_name' => 'Patient Name',
        'doctor_name' => 'Doctor Name',
        'appointment_date' => 'Appointment Date',
        'appointment_time' => 'Appointment Time',
        'status' => 'Status',
        'reason' => 'Reason',
        'contact_number' => 'Patient Contact',
        'patient_email' => 'Patient Email',
        'created_at' => 'Created Date'
    ],
    'billing' => [
        'billing_id' => 'Bill ID',
        'invoice_id' => 'Invoice ID',
        'patient_name' => 'Patient Name',
        'doctor_name' => 'Doctor Name',
        'description' => 'Description',
        'amount' => 'Amount',
        'status' => 'Status',
        'invoice_date' => 'Invoice Date',
        'created_at' => 'Created Date',
        'updated_at' => 'Updated Date'
    ],
    'visits' => [
        'visit_id' => 'Visit ID',
        'patient_name' => 'Patient Name',
        'doctor_name' => 'Doctor Name',
        'visit_datetime' => 'Visit Date & Time',
        'nature_of_visit' => 'Nature of Visit',
        'affected_area' => 'Affected Area',
        'symptoms' => 'Symptoms',
        'observation' => 'Observation',
        'procedure_done' => 'Procedure Done',
        'meds_prescribed' => 'Medications',
        'instruction_to_patient' => 'Instructions',
        'remarks' => 'Remarks'
    ]
];

// Get available fields for a report type
if ($action === 'get_fields') {
    $type = $_GET['type'] ?? '';
    $fields = $availableFields[$type] ?? [];
    sendJsonResponse(['fields' => $fields]);
    exit;
}

// Generate report with selected fields
if ($action === 'generate') {
    $type = $_GET['type'] ?? '';
    $selectedFields = json_decode($_GET['fields'] ?? '[]', true);
    
    if (empty($selectedFields)) {
        // Default to all fields if none selected
        $selectedFields = array_keys($availableFields[$type] ?? []);
    }
    
    $data = getReportData($type, $selectedFields, $pdo);
    sendJsonResponse($data);
    exit;
}

function getReportData($type, $fields, $pdo) {
    switch($type) {
        case 'patients':
            return getPatientsReport($fields, $pdo);
        case 'doctors':
            return getDoctorsReport($fields, $pdo);
        case 'appointments':
            return getAppointmentsReport($fields, $pdo);
        case 'billing':
            return getBillingReport($fields, $pdo);
        case 'visits':
            return getVisitsReport($fields, $pdo);
        default:
            return ['error' => 'Invalid report type'];
    }
}

function getPatientsReport($fields, $pdo) {
    $query = "SELECT p.* FROM patients p WHERE p.status = 'active' ORDER BY p.last_name ASC";
    $stmt = $pdo->query($query);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($patients as $patient) {
        $row = [];
        foreach ($fields as $field) {
            if ($field === 'age') {
                if ($patient['date_of_birth']) {
                    $dob = new DateTime($patient['date_of_birth']);
                    $today = new DateTime();
                    $age = $today->diff($dob)->y;
                    $row[$field] = $age;
                } else {
                    $row[$field] = '';
                }
            } elseif ($field === 'total_visits') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE patient_id = ? AND is_archived = 0");
                $stmt->execute([$patient['patient_id']]);
                $row[$field] = $stmt->fetchColumn();
            } elseif ($field === 'total_appointments') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND is_archived = 0");
                $stmt->execute([$patient['patient_id']]);
                $row[$field] = $stmt->fetchColumn();
            } else {
                $row[$field] = $patient[$field] ?? '';
            }
        }
        $data[] = $row;
    }
    
    return [
        'type' => 'patients',
        'title' => 'Patients Report',
        'fields' => $fields,
        'data' => $data,
        'total_records' => count($data)
    ];
}

function getDoctorsReport($fields, $pdo) {
    $query = "SELECT d.* FROM doctors d WHERE d.is_archived = 0 ORDER BY d.last_name ASC";
    $stmt = $pdo->query($query);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($doctors as $doctor) {
        $row = [];
        foreach ($fields as $field) {
            if ($field === 'age') {
                if ($doctor['date_of_birth']) {
                    $dob = new DateTime($doctor['date_of_birth']);
                    $today = new DateTime();
                    $age = $today->diff($dob)->y;
                    $row[$field] = $age;
                } else {
                    $row[$field] = '';
                }
            } elseif ($field === 'total_appointments') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND is_archived = 0");
                $stmt->execute([$doctor['doctor_id']]);
                $row[$field] = $stmt->fetchColumn();
            } elseif ($field === 'completed_appointments') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Completed' AND is_archived = 0");
                $stmt->execute([$doctor['doctor_id']]);
                $row[$field] = $stmt->fetchColumn();
            } else {
                $row[$field] = $doctor[$field] ?? '';
            }
        }
        $data[] = $row;
    }
    
    return [
        'type' => 'doctors',
        'title' => 'Doctors Report',
        'fields' => $fields,
        'data' => $data,
        'total_records' => count($data)
    ];
}

function getAppointmentsReport($fields, $pdo) {
    $query = "
        SELECT 
            a.appointment_id,
            CONCAT(p.last_name, ', ', p.first_name) AS patient_name,
            p.contact_number,
            p.email AS patient_email,
            CONCAT(d.last_name, ', ', d.first_name) AS doctor_name,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.reason,
            a.created_at
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.is_archived = 0
        ORDER BY a.appointment_date DESC
    ";
    $stmt = $pdo->query($query);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($appointments as $appt) {
        $row = [];
        foreach ($fields as $field) {
            $row[$field] = $appt[$field] ?? '';
        }
        $data[] = $row;
    }
    
    return [
        'type' => 'appointments',
        'title' => 'Appointments Report',
        'fields' => $fields,
        'data' => $data,
        'total_records' => count($data)
    ];
}

function getBillingReport($fields, $pdo) {
    $query = "
        SELECT 
            b.billing_id,
            CONCAT(p.last_name, ', ', p.first_name) AS patient_name,
            CONCAT(d.last_name, ', ', d.first_name) AS doctor_name,
            b.description,
            b.amount,
            b.status,
            b.invoice_date,
            b.invoice_id,
            b.created_at,
            b.updated_at
        FROM billing b
        LEFT JOIN patients p ON b.patient_id = p.patient_id
        LEFT JOIN doctors d ON b.doctor_id = d.doctor_id
        WHERE b.is_archived = 0
        ORDER BY b.created_at DESC
    ";
    $stmt = $pdo->query($query);
    $billing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($billing as $bill) {
        $row = [];
        foreach ($fields as $field) {
            $row[$field] = $bill[$field] ?? '';
        }
        $data[] = $row;
    }
    
    return [
        'type' => 'billing',
        'title' => 'Billing Report',
        'fields' => $fields,
        'data' => $data,
        'total_records' => count($data)
    ];
}

function getVisitsReport($fields, $pdo) {
    $query = "
        SELECT 
            v.visit_id,
            CONCAT(p.last_name, ', ', p.first_name) AS patient_name,
            CONCAT(d.last_name, ', ', d.first_name) AS doctor_name,
            v.visit_datetime,
            v.nature_of_visit,
            v.affected_area,
            v.symptoms,
            v.observation,
            v.procedure_done,
            v.meds_prescribed,
            v.instruction_to_patient,
            v.remarks
        FROM visits v
        JOIN patients p ON v.patient_id = p.patient_id
        JOIN doctors d ON v.doctor_id = d.doctor_id
        WHERE v.is_archived = 0
        ORDER BY v.visit_datetime DESC
    ";
    $stmt = $pdo->query($query);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($visits as $visit) {
        $row = [];
        foreach ($fields as $field) {
            $row[$field] = $visit[$field] ?? '';
        }
        $data[] = $row;
    }
    
    return [
        'type' => 'visits',
        'title' => 'Visits Report',
        'fields' => $fields,
        'data' => $data,
        'total_records' => count($data)
    ];
}

sendJsonResponse(['error' => 'Invalid action'], 400);
