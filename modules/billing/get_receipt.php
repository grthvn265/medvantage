<?php
header('Content-Type: application/json');

require '../../components/db.php';

$billing_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$billing_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Billing ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.first_name AS p_fname, p.last_name AS p_lname,
               d.first_name AS d_fname, d.last_name AS d_lname
        FROM billing b
        LEFT JOIN patients p ON b.patient_id = p.patient_id
        LEFT JOIN doctors d ON b.doctor_id = d.doctor_id
        WHERE b.billing_id = ?
    ");

    $stmt->execute([$billing_id]);
    $billing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$billing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Billing record not found']);
        exit;
    }

    // Calculate total
    $subtotal = floatval($billing['amount']);

    // Format data for receipt
    $receipt_data = [
        'success' => true,
        'billing' => [
            'invoice_id' => htmlspecialchars($billing['invoice_id']),
            'invoice_date' => htmlspecialchars($billing['invoice_date']),
            'formatted_date' => date('M d, Y', strtotime($billing['invoice_date'])),
            'status' => htmlspecialchars($billing['status']),
            'patient_name' => htmlspecialchars($billing['p_lname'] . ', ' . $billing['p_fname']),
            'doctor_name' => 'Dr. ' . htmlspecialchars($billing['d_lname'] . ', ' . $billing['d_fname']),
            'amount' => $subtotal
        ]
    ];

    echo json_encode($receipt_data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
