<?php
require '../../components/db.php';

if (!isset($_GET['id'])) {
    header("Location: appointment.php");
    exit;
}

$appointment_id = (int) $_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);

    header("Location: appointment.php?deleted=1");
    exit;

} catch (PDOException $e) {
    die("Error deleting appointment: " . $e->getMessage());
}
