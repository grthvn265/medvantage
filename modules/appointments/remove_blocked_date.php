<?php
header('Content-Type: application/json');

require '../../components/db.php';
require '../../components/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $dateStmt = $pdo->prepare("SELECT blocked_date FROM blocked_dates WHERE id = ?");
    $dateStmt->execute([$id]);
    $blockedDate = $dateStmt->fetchColumn();

    // Delete the blocked date
    $stmt = $pdo->prepare("DELETE FROM blocked_dates WHERE id = ?");
    $stmt->execute([$id]);

    logAudit($pdo, 'UNBLOCK_DATE', 'appointments', $id, 'Removed blocked date: ' . ($blockedDate ?: 'unknown'));

    echo json_encode(['success' => true, 'message' => 'Blocked date removed']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
