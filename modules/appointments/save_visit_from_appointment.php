<?php
header('Content-Type: application/json; charset=utf-8');

require '../../components/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

/* ==============================
   VALIDATION HELPER FUNCTIONS
============================== */

function sanitizeText($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDate($date) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        return false;
    }
    return true;
}

function validateHour($hour) {
    $hour = (int)$hour;
    return $hour >= 10 && $hour <= 18;
}

/* ==============================
   1. SANITIZE INPUTS
============================== */

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$visit_date = isset($_POST['visit_date']) ? sanitizeText($_POST['visit_date']) : '';
$visit_time = isset($_POST['visit_time']) ? sanitizeText($_POST['visit_time']) : '';
$nature_of_visit = isset($_POST['nature_of_visit']) ? sanitizeText($_POST['nature_of_visit']) : '';
$affected_area = isset($_POST['affected_area']) ? sanitizeText($_POST['affected_area']) : '';
$symptoms = isset($_POST['symptoms']) ? sanitizeText($_POST['symptoms']) : '';
$observation = isset($_POST['observation']) ? sanitizeText($_POST['observation']) : '';
$procedure_done = isset($_POST['procedure_done']) ? sanitizeText($_POST['procedure_done']) : '';
$meds_prescribed = isset($_POST['meds_prescribed']) ? sanitizeText($_POST['meds_prescribed']) : '';
$instruction_to_patient = isset($_POST['instruction_to_patient']) ? sanitizeText($_POST['instruction_to_patient']) : '';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

$errors = [];

/* ==============================
   2. VALIDATE APPOINTMENT ID
============================== */

if (empty($appointment_id) || $appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Verify appointment exists and get patient/doctor info
$stmt = $pdo->prepare("SELECT appointment_id, patient_id, doctor_id FROM appointments WHERE appointment_id = ?");
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit;
}

/* ==============================
   3. VALIDATE REQUIRED FIELDS
============================== */

if (empty($visit_date)) {
    $errors['visit_date'] = "Visit date is required";
} else {
    if (!validateDate($visit_date)) {
        $errors['visit_date'] = "Invalid date format";
    }
}

if (empty($visit_time)) {
    $errors['visit_time'] = "Visit time is required";
} else {
    if (!validateHour($visit_time)) {
        $errors['visit_time'] = "Invalid time";
    }
}

if (empty($nature_of_visit)) {
    $errors['nature_of_visit'] = "Nature of visit is required";
} else {
    if (strlen($nature_of_visit) > 500) {
        $errors['nature_of_visit'] = "Nature of visit must not exceed 500 characters";
    }
}

/* ==============================
   4. VALIDATE OPTIONAL FIELDS
============================== */

if (!empty($affected_area) && strlen($affected_area) > 200) {
    $errors['affected_area'] = "Affected area must not exceed 200 characters";
}

if (!empty($symptoms) && strlen($symptoms) > 1000) {
    $errors['symptoms'] = "Symptoms must not exceed 1000 characters";
}

if (!empty($observation) && strlen($observation) > 1000) {
    $errors['observation'] = "Observation must not exceed 1000 characters";
}

if (!empty($procedure_done) && strlen($procedure_done) > 1000) {
    $errors['procedure_done'] = "Procedure done must not exceed 1000 characters";
}

if (!empty($meds_prescribed) && strlen($meds_prescribed) > 1000) {
    $errors['meds_prescribed'] = "Medications prescribed must not exceed 1000 characters";
}

if (!empty($instruction_to_patient) && strlen($instruction_to_patient) > 1000) {
    $errors['instruction_to_patient'] = "Instructions to patient must not exceed 1000 characters";
}

if (!empty($remarks) && strlen($remarks) > 500) {
    $errors['remarks'] = "Remarks must not exceed 500 characters";
}

/* ==============================
   5. RETURN ERRORS OR SAVE
============================== */

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Create visit datetime
    $visit_datetime = $visit_date . ' ' . str_pad($visit_time, 2, '0', STR_PAD_LEFT) . ':00:00';

    // Insert visit
    $stmt = $pdo->prepare("
        INSERT INTO visits (
            patient_id,
            doctor_id,
            visit_datetime,
            nature_of_visit,
            affected_area,
            symptoms,
            observation,
            procedure_done,
            meds_prescribed,
            instruction_to_patient,
            remarks
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $appointment['patient_id'],
        $appointment['doctor_id'],
        $visit_datetime,
        $nature_of_visit,
        !empty($affected_area) ? $affected_area : null,
        !empty($symptoms) ? $symptoms : null,
        !empty($observation) ? $observation : null,
        !empty($procedure_done) ? $procedure_done : null,
        !empty($meds_prescribed) ? $meds_prescribed : null,
        !empty($instruction_to_patient) ? $instruction_to_patient : null,
        !empty($remarks) ? $remarks : null
    ]);

    // Update appointment status to Completed
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);

    // Create billing record for the completed appointment
    $invoice_id = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    
    $stmt = $pdo->prepare("
        INSERT INTO billing (patient_id, doctor_id, amount, status, description, invoice_date, invoice_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $appointment['patient_id'],
        $appointment['doctor_id'],
        0.00, // Default amount to 0, to be updated manually
        'Unpaid',
        null,
        $visit_date, // Use the visit date as the invoice date
        $invoice_id
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Visit recorded and appointment completed successfully'
    ]);
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit;
}
