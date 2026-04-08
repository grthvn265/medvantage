<?php
require '../../components/db.php';

header('Content-Type: application/json');

$currentUser = getCurrentUser($pdo);
if (!$currentUser || $currentUser['role_key'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized request.'
    ]);
    exit;
}

$email = trim((string) ($_GET['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Enter a valid email address.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->execute([$email]);
    $exists = (bool) $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'message' => $exists ? 'Email is already in use.' : 'Email is available.'
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Unable to check email availability right now.'
    ]);
}
