<?php

declare(strict_types=1);

require_once __DIR__ . '/components/db.php';

$user = getCurrentUser($pdo);
$target = $user ? getLandingRouteForUser($pdo, $user) : '/login';

if ($target === '') {
    $target = '/login';
}

header('Location: ' . appUrl($target), true, 302);
exit;
