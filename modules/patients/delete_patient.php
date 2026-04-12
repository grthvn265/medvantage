<?php
require '../../components/db.php';
require '../../components/audit_log.php';

if (!isset($_GET['id'])) {
    header('Location: ' . appUrl('/patients?error=' . urlencode('No patient ID provided')));
    exit;
}

$patient_id = (int) $_GET['id'];

if ($patient_id <= 0) {
    header('Location: ' . appUrl('/patients?error=' . urlencode('Invalid patient ID')));
    exit;
}

try {
    // Verify patient exists
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        header('Location: ' . appUrl('/patients?error=' . urlencode('Patient not found')));
        exit;
    }

    // Delete associated visits first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    // Delete the patient
    $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    logAudit($pdo, 'DELETE', 'patients', $patient_id, 'Deleted patient record');

    header('Location: ' . appUrl('/patients?deleted=1'));
    exit;

} catch (PDOException $e) {
    header('Location: ' . appUrl('/patients?error=' . urlencode('Database error. Failed to delete patient.')));
    exit;
}
