<?php
header('Content-Type: application/json');

require '../../components/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

try {
    // Verify appointment exists
    $stmt = $pdo->prepare("SELECT appointment_id, status FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }

    // Only allow cancelling if status is 'Scheduled'
    if ($appointment['status'] !== 'Scheduled') {
        echo json_encode(['success' => false, 'message' => 'Only scheduled appointments can be cancelled']);
        exit;
    }

    // Update appointment status to Cancelled
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);

    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
