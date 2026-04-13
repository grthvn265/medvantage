<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    http_response_code(401);
    exit('Unauthorized');
}

$module = isset($_POST['module']) ? trim((string) $_POST['module']) : '';
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';

$allowedModules = ['dashboard', 'audit_log'];
if (!in_array($module, $allowedModules, true)) {
    http_response_code(400);
    exit('Invalid module');
}

if ($description === '') {
    $description = $module === 'dashboard'
        ? 'Printed dashboard report'
        : 'Printed audit log report';
}

logAudit($pdo, 'PRINT', $module, null, $description);

http_response_code(204);