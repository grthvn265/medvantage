<?php
header('Content-Type: application/json');

require '../../components/db.php';
require '../../components/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$blocked_date = isset($_POST['blocked_date']) ? $_POST['blocked_date'] : null;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$blocked_date) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

// Validate reason: optional, max 50 characters, no digits
if ($reason !== '') {
    if (strlen($reason) > 50) {
        echo json_encode(['success' => false, 'message' => 'Reason must be 50 characters or less']);
        exit;
    }
    if (preg_match('/\d/', $reason)) {
        echo json_encode(['success' => false, 'message' => 'Reason cannot contain numbers']);
        exit;
    }
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $blocked_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Validate date is in the future (not today or past)
$today = new DateTime();
$today->setTime(0, 0, 0);
$blockedDateObj = new DateTime($blocked_date);
$blockedDateObj->setTime(0, 0, 0);

if ($blockedDateObj <= $today) {
    echo json_encode(['success' => false, 'message' => 'Only future dates can be blocked']);
    exit;
}

try {
    // Check if date is already blocked
    $check = $pdo->prepare("SELECT id FROM blocked_dates WHERE blocked_date = ?");
    $check->execute([$blocked_date]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This date is already blocked']);
        exit;
    }

    // Insert blocked date
    $stmt = $pdo->prepare("
        INSERT INTO blocked_dates (blocked_date, reason)
        VALUES (?, ?)
    ");
    $stmt->execute([$blocked_date, $reason]);

    $blockedId = (int) $pdo->lastInsertId();
    logAudit($pdo, 'BLOCK_DATE', 'appointments', $blockedId, 'Blocked date: ' . $blocked_date);

    echo json_encode(['success' => true, 'message' => 'Blocked date added']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
