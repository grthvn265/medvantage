<?php
if (is_file(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
} elseif (is_file(__DIR__ . '/.env.php')) {
    require_once __DIR__ . '/.env.php';
}
require_once __DIR__ . '/auth.php';

if (is_callable('app_config')) {
    $configBuilder = 'app_config';
    $config = $configBuilder();
} else {
    $config = [
        'db' => [
            'host' => (string) (getenv('DB_HOST') ?: 'localhost'),
            'name' => (string) (getenv('DB_NAME') ?: ''),
            'user' => (string) (getenv('DB_USER') ?: ''),
            'pass' => (string) (getenv('DB_PASS') ?: ''),
            'charset' => (string) (getenv('DB_CHARSET') ?: 'utf8mb4'),
        ],
    ];
}

$host = (string) ($config['db']['host'] ?? 'localhost');
$db = (string) ($config['db']['name'] ?? '');
$user = (string) ($config['db']['user'] ?? '');
$pass = (string) ($config['db']['pass'] ?? '');
$charset = (string) ($config['db']['charset'] ?? 'utf8mb4');

if ($db === '' || $user === '') {
    error_log('Database configuration is incomplete. Check DB_NAME and DB_USER.');
    http_response_code(500);
    exit('Application configuration error.');
}

if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $charset)) {
    $charset = 'utf8mb4';
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if (PHP_SAPI !== 'cli' && is_callable('requireCurrentRouteAccess')) {
        requireCurrentRouteAccess($pdo);
    }
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);

    if (is_callable('app_is_production') && app_is_production()) {
        exit('Service temporarily unavailable. Please try again later.');
    }

    exit('Connection failed: ' . $e->getMessage());
}
?>