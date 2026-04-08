<?php
header('Content-Type: application/json');

require '../../components/db.php';
require '../../components/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$billing_id = isset($_POST['billing_id']) ? (int)$_POST['billing_id'] : null;
$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : 'Unpaid';
$invoice_date = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d');

// Validate required fields
if (!$billing_id || !$patient_id || !$doctor_id || $amount === null) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate amount is positive
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit;
}

// Validate status is either Paid or Unpaid
$allowed_status = ['Paid', 'Unpaid'];
if (!in_array($status, $allowed_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Validate patient exists
$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
    exit;
}

// Validate doctor exists
$stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Doctor not found']);
    exit;
}

// Verify billing record exists
$stmt = $pdo->prepare("SELECT billing_id FROM billing WHERE billing_id = ?");
$stmt->execute([$billing_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Billing record not found']);
    exit;
}

try {
    // Update billing record
    $stmt = $pdo->prepare("
        UPDATE billing 
        SET patient_id = ?, doctor_id = ?, amount = ?, status = ?, invoice_date = ?
        WHERE billing_id = ?
    ");

    $stmt->execute([
        $patient_id,
        $doctor_id,
        $amount,
        $status,
        $invoice_date,
        $billing_id
    ]);

    logAudit($pdo, 'UPDATE', 'billing', $billing_id, 'Updated billing record');

    echo json_encode([
        'success' => true,
        'message' => 'Billing record updated successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
