<?php
require '../../components/db.php';
require '../../components/audit_log.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$appointment_id) {
    header('Location: ' . appUrl('/appointments?error=Invalid%20appointment'));
    exit;
}

try {
    if ($action === 'archive') {
        // Archive the appointment
        $stmt = $pdo->prepare("UPDATE appointments SET is_archived = 1, archived_at = NOW() WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        logAudit($pdo, 'ARCHIVE', 'appointments', $appointment_id, 'Archived appointment');
        header('Location: ' . appUrl('/appointments?archived=1'));
        exit;
    } elseif ($action === 'restore') {
        // Restore the appointment
        $stmt = $pdo->prepare("UPDATE appointments SET is_archived = 0, archived_at = NULL WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        logAudit($pdo, 'RESTORE', 'appointments', $appointment_id, 'Restored appointment');
        header('Location: ' . appUrl('/appointments?restored=1&show_archived=1'));
        exit;
    } elseif ($action === 'permanently_delete') {
        // Permanently delete the appointment
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        logAudit($pdo, 'PERMANENTLY_DELETED', 'appointments', $appointment_id, 'Permanently deleted appointment');
        header('Location: ' . appUrl('/appointments?permanently_deleted=1&show_archived=1'));
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

header('Location: ' . appUrl('/appointments?error=Unknown%20action'));
exit;
