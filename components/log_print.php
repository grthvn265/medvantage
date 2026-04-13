<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function printLogResponse(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    printLogResponse(405, ['success' => false, 'message' => 'Method Not Allowed']);
}

$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    printLogResponse(401, ['success' => false, 'message' => 'Unauthorized']);
}

$requestData = $_POST;
if (empty($requestData)) {
    $rawInput = file_get_contents('php://input');
    if (is_string($rawInput) && $rawInput !== '') {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $requestData = $decoded;
        } else {
            parse_str($rawInput, $parsed);
            if (is_array($parsed)) {
                $requestData = $parsed;
            }
        }
    }
}

$module = isset($requestData['module']) ? trim((string) $requestData['module']) : '';
$description = isset($requestData['description']) ? trim((string) $requestData['description']) : '';

$allowedModules = ['dashboard', 'audit_log'];
if (!in_array($module, $allowedModules, true)) {
    printLogResponse(400, ['success' => false, 'message' => 'Invalid module']);
}

if ($description === '') {
    $description = $module === 'dashboard'
        ? 'Printed dashboard report'
        : 'Printed audit log report';
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : (int) ($currentUser['user_id'] ?? 0);
if ($userId <= 0) {
    printLogResponse(401, ['success' => false, 'message' => 'Invalid session user']);
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? null;

if (is_string($ip) && str_contains($ip, ',')) {
    $ip = trim(explode(',', $ip)[0]);
}

if (is_string($ip) && strlen($ip) > 45) {
    $ip = substr($ip, 0, 45);
}

try {
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, module, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, 'PRINT', $module, null, $description, $ip]);
    printLogResponse(200, ['success' => true]);
} catch (Throwable $e) {
    error_log('Print logging failed: ' . $e->getMessage());
    printLogResponse(500, ['success' => false, 'message' => 'Failed to write print audit log']);
}