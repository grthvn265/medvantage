<?php
require '../../components/db.php';
require '../../components/audit_log.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$doctor_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$doctor_id) {
    header("Location: doctors.php?error=Invalid doctor");
    exit;
}

try {
    if ($action === 'archive') {
        // Archive the doctor
        $stmt = $pdo->prepare("UPDATE doctors SET is_archived = 1, archived_at = NOW() WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        logAudit($pdo, 'ARCHIVE', 'doctors', $doctor_id, 'Archived doctor record');
        header("Location: doctors.php?archived=1");
        exit;
    } elseif ($action === 'restore') {
        // Restore the doctor
        $stmt = $pdo->prepare("UPDATE doctors SET is_archived = 0, archived_at = NULL WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        logAudit($pdo, 'RESTORE', 'doctors', $doctor_id, 'Restored doctor record');
        header("Location: doctors.php?restored=1&show_archived=1");
        exit;
    } elseif ($action === 'permanently_delete') {
        // Permanently delete the doctor and related records
        $pdo->beginTransaction();
        try {
            // Delete doctor availability records
            $stmt = $pdo->prepare("DELETE FROM doctor_unavailable_days WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            
            $stmt = $pdo->prepare("DELETE FROM doctor_available_times WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            
            // Delete the doctor
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            
            $pdo->commit();
            logAudit($pdo, 'PERMANENTLY_DELETED', 'doctors', $doctor_id, 'Permanently deleted doctor record');
            header("Location: doctors.php?permanently_deleted=1&show_archived=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: doctors.php?error=Unknown action");
exit;
