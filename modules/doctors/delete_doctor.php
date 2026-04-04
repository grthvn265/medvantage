<?php
require '../../components/db.php';

if (!isset($_GET['id'])) {
    header("Location: doctors.php");
    exit;
}

$doctor_id = (int) $_GET['id'];

$pdo->beginTransaction();

try {
    // Delete related availability records first
    $pdo->prepare("DELETE FROM doctor_unavailable_days WHERE doctor_id = ?")
        ->execute([$doctor_id]);

    $pdo->prepare("DELETE FROM doctor_available_times WHERE doctor_id = ?")
        ->execute([$doctor_id]);

    // Delete the doctor
    $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);

    $pdo->commit();

    header("Location: doctors.php?deleted=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting doctor: " . $e->getMessage());
}
