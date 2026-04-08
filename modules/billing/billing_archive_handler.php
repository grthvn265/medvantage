<?php
require '../../components/db.php';
require '../../components/audit_log.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$billing_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$billing_id) {
    header("Location: billing.php?error=Invalid billing record");
    exit;
}

try {
    if ($action === 'archive') {
        // Archive the billing record
        $stmt = $pdo->prepare("UPDATE billing SET is_archived = 1, archived_at = NOW() WHERE billing_id = ?");
        $stmt->execute([$billing_id]);
        logAudit($pdo, 'ARCHIVE', 'billing', $billing_id, 'Archived billing record');
        header("Location: billing.php?archived=1");
        exit;
    } elseif ($action === 'restore') {
        // Restore the billing record
        $stmt = $pdo->prepare("UPDATE billing SET is_archived = 0, archived_at = NULL WHERE billing_id = ?");
        $stmt->execute([$billing_id]);
        logAudit($pdo, 'RESTORE', 'billing', $billing_id, 'Restored billing record');
        header("Location: billing.php?restored=1&show_archived=1");
        exit;
    } elseif ($action === 'permanently_delete') {
        // Permanently delete the billing record
        $stmt = $pdo->prepare("DELETE FROM billing WHERE billing_id = ?");
        $stmt->execute([$billing_id]);
        logAudit($pdo, 'PERMANENTLY_DELETED', 'billing', $billing_id, 'Permanently deleted billing record');
        header("Location: billing.php?permanently_deleted=1&show_archived=1");
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: billing.php?error=Unknown action");
exit;
