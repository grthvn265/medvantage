<?php
require '../../components/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$patient_id) {
    header("Location: patients.php?error=Invalid patient");
    exit;
}

try {
    if ($action === 'archive') {
        // Archive the patient
        $stmt = $pdo->prepare("UPDATE patients SET status = 'archive' WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        header("Location: patients.php?archived=1");
        exit;
    } elseif ($action === 'restore') {
        // Restore the patient
        $stmt = $pdo->prepare("UPDATE patients SET status = 'active' WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        header("Location: patients.php?restored=1&show_archived=1");
        exit;
    } elseif ($action === 'permanently_delete') {
        // Permanently delete the patient and related records
        $pdo->beginTransaction();
        try {
            // Delete associated appointments first
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            // Delete associated billing records
            $stmt = $pdo->prepare("DELETE FROM billing WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            // Delete associated visits
            $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            // Delete the patient
            $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            
            $pdo->commit();
            header("Location: patients.php?permanently_deleted=1&show_archived=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: patients.php?error=Unknown action");
exit;
