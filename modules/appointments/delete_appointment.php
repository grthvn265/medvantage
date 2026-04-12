<?php
require '../../components/db.php';
require '../../components/audit_log.php';

if (!isset($_GET['id'])) {
    header('Location: ' . appUrl('/appointments'));
    exit;
}

$appointment_id = (int) $_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);

    logAudit($pdo, 'DELETE', 'appointments', $appointment_id, 'Deleted appointment');

    header('Location: ' . appUrl('/appointments?deleted=1'));
    exit;

} catch (PDOException $e) {
    die("Error deleting appointment: " . $e->getMessage());
}
