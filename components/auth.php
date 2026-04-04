<?php

declare(strict_types=1);

const OWNER_DEFAULT_USERNAME = 'owner';
const OWNER_DEFAULT_PASSWORD = 'Owner@12345';

function startAuthSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function getAppBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $trimmed = trim($scriptName, '/');

    if ($trimmed === '') {
        return '';
    }

    $segments = explode('/', $trimmed);
    return '/' . $segments[0];
}

function appUrl(string $path): string
{
    $base = rtrim(getAppBasePath(), '/');
    return $base . '/' . ltrim($path, '/');
}

function currentRequestPath(): string
{
    $raw = str_replace('\\', '/', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    $base = getAppBasePath();

    if ($base !== '' && str_starts_with($raw, $base)) {
        $raw = substr($raw, strlen($base));
    }

    return '/' . ltrim($raw, '/');
}

function getDefaultModules(): array
{
    return [
        [
            'module_key' => 'dashboard',
            'module_label' => 'Dashboard',
            'route_path' => '/modules/dashboard/index.php',
            'icon_class' => 'bi-speedometer2',
            'sort_order' => 1,
        ],
        [
            'module_key' => 'patients',
            'module_label' => 'Patients',
            'route_path' => '/modules/patients/patients.php',
            'icon_class' => 'bi-people',
            'sort_order' => 2,
        ],
        [
            'module_key' => 'doctors',
            'module_label' => 'Doctors',
            'route_path' => '/modules/doctors/doctors.php',
            'icon_class' => 'bi-person-badge',
            'sort_order' => 3,
        ],
        [
            'module_key' => 'appointments',
            'module_label' => 'Appointments',
            'route_path' => '/modules/appointments/appointment.php',
            'icon_class' => 'bi-calendar-check',
            'sort_order' => 4,
        ],
        [
            'module_key' => 'billing',
            'module_label' => 'Billing',
            'route_path' => '/modules/billing/billing.php',
            'icon_class' => 'bi-receipt',
            'sort_order' => 5,
        ],
        [
            'module_key' => 'users',
            'module_label' => 'Accounts',
            'route_path' => '/modules/users/users.php',
            'icon_class' => 'bi-shield-lock',
            'sort_order' => 6,
        ],
    ];
}

function authTablesExist(PDO $pdo): bool
{
    static $checked = false;
    static $ready = false;

    if ($checked) {
        return $ready;
    }

    $required = ['roles', 'users', 'user_module_access', 'app_modules'];

    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ');
    foreach ($required as $table) {
        $stmt->execute([$table]);
        if ((int) $stmt->fetchColumn() === 0) {
            $checked = true;
            $ready = false;
            return false;
        }
    }

    $checked = true;
    $ready = true;
    return true;
}

function syncAppModules(PDO $pdo): void
{
    $modules = getDefaultModules();

    $sql = '
        INSERT INTO app_modules (module_key, module_label, route_path, icon_class, sort_order, is_enabled)
        VALUES (:module_key, :module_label, :route_path, :icon_class, :sort_order, 1)
        ON DUPLICATE KEY UPDATE
            module_label = VALUES(module_label),
            route_path = VALUES(route_path),
            icon_class = VALUES(icon_class),
            sort_order = VALUES(sort_order),
            is_enabled = 1
    ';

    $stmt = $pdo->prepare($sql);
    foreach ($modules as $module) {
        $stmt->execute($module);
    }
}

function ensureBaseRoles(PDO $pdo): void
{
    $roles = [
        ['role_key' => 'super_admin', 'role_name' => 'Super Admin'],
        ['role_key' => 'staff', 'role_name' => 'Staff'],
    ];

    $sql = '
        INSERT INTO roles (role_key, role_name)
        VALUES (:role_key, :role_name)
        ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)
    ';

    $stmt = $pdo->prepare($sql);
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
}

function ensureOwnerAccount(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $superRoleStmt = $pdo->prepare('SELECT role_id FROM roles WHERE role_key = ? LIMIT 1');
    $superRoleStmt->execute(['super_admin']);
    $superRoleId = $superRoleStmt->fetchColumn();

    if (!$superRoleId) {
        return;
    }

    $insertOwner = $pdo->prepare('
        INSERT INTO users (username, password_hash, full_name, email, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ');

    $insertOwner->execute([
        OWNER_DEFAULT_USERNAME,
        password_hash(OWNER_DEFAULT_PASSWORD, PASSWORD_DEFAULT),
        'System Owner',
        'owner@medvantage.local',
        $superRoleId,
    ]);

    startAuthSession();
    if (empty($_SESSION['owner_notice_shown'])) {
        $_SESSION['owner_notice_shown'] = true;
        $_SESSION['flash_success'] = 'Owner account generated. Username: ' . OWNER_DEFAULT_USERNAME . ' | Password: ' . OWNER_DEFAULT_PASSWORD;
    }
}

function getCurrentUser(PDO $pdo): ?array
{
    startAuthSession();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT u.user_id, u.username, u.full_name, u.email, u.is_active, r.role_key, r.role_name
        FROM users u
        INNER JOIN roles r ON r.role_id = u.role_id
        WHERE u.user_id = ?
        LIMIT 1
    ');
    $stmt->execute([(int) $_SESSION['user_id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || (int) $user['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function isLoggedIn(PDO $pdo): bool
{
    return getCurrentUser($pdo) !== null;
}

function attemptLogin(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('
        SELECT u.user_id, u.password_hash, u.is_active
        FROM users u
        WHERE u.username = ?
        LIMIT 1
    ');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int) $user['is_active'] !== 1) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    startAuthSession();
    $_SESSION['user_id'] = (int) $user['user_id'];

    $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = ?');
    $update->execute([(int) $user['user_id']]);

    return true;
}

function logoutCurrentUser(): void
{
    startAuthSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function currentModuleKeyFromPath(?string $path = null): ?string
{
    $routePath = $path ?? currentRequestPath();

    if (!str_starts_with($routePath, '/modules/')) {
        return null;
    }

    $segments = explode('/', trim($routePath, '/'));
    if (!isset($segments[1])) {
        return null;
    }

    $moduleCandidate = $segments[1];
    if (in_array($moduleCandidate, ['auth'], true)) {
        return null;
    }

    return $moduleCandidate;
}

function isAuthRoute(): bool
{
    $path = currentRequestPath();
    return str_starts_with($path, '/modules/auth/');
}

function isJsonRequest(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return str_contains(strtolower($accept), 'application/json')
        || strtolower($requestedWith) === 'xmlhttprequest';
}

function userCanAccessModule(PDO $pdo, int $userId, ?string $roleKey, string $moduleKey): bool
{
    if ($roleKey === 'super_admin') {
        return true;
    }

    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM user_module_access uma
        INNER JOIN app_modules am ON am.module_id = uma.module_id
        WHERE uma.user_id = ? AND am.module_key = ? AND am.is_enabled = 1
    ');

    $stmt->execute([$userId, $moduleKey]);
    return (int) $stmt->fetchColumn() > 0;
}

function requireCurrentRouteAccess(PDO $pdo): void
{
    startAuthSession();

    if (!authTablesExist($pdo)) {
        http_response_code(500);
        echo 'RBAC tables are missing. Run setup/ENABLE_RBAC.sql first.';
        exit;
    }

    syncAppModules($pdo);
    ensureBaseRoles($pdo);
    ensureOwnerAccount($pdo);

    if (isAuthRoute()) {
        return;
    }

    $user = getCurrentUser($pdo);
    if (!$user) {
        if (isJsonRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $next = urlencode(currentRequestPath());
        header('Location: ' . appUrl('/modules/auth/login.php?next=' . $next));
        exit;
    }

    $moduleKey = currentModuleKeyFromPath();
    if ($moduleKey === null) {
        return;
    }

    if (!userCanAccessModule($pdo, (int) $user['user_id'], $user['role_key'], $moduleKey)) {
        if (isJsonRequest()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        header('Location: ' . appUrl('/modules/dashboard/index.php?denied=1'));
        exit;
    }
}

function getEnabledModules(PDO $pdo, bool $excludeUsersModule = false): array
{
    $stmt = $pdo->query('
        SELECT module_id, module_key, module_label, route_path, icon_class, sort_order
        FROM app_modules
        WHERE is_enabled = 1
        ORDER BY sort_order ASC, module_label ASC
    ');
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$excludeUsersModule) {
        return $modules;
    }

    return array_values(array_filter($modules, static function (array $module): bool {
        return $module['module_key'] !== 'users';
    }));
}

function getAccessibleModulesForUser(PDO $pdo, ?array $user): array
{
    if ($user === null) {
        return [];
    }

    $modules = getEnabledModules($pdo);
    if ($user['role_key'] === 'super_admin') {
        return $modules;
    }

    $stmt = $pdo->prepare('
        SELECT am.module_key
        FROM user_module_access uma
        INNER JOIN app_modules am ON am.module_id = uma.module_id
        WHERE uma.user_id = ? AND am.is_enabled = 1
    ');
    $stmt->execute([(int) $user['user_id']]);

    $allowedKeys = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'module_key');
    $allowedLookup = array_fill_keys($allowedKeys, true);

    return array_values(array_filter($modules, static function (array $module) use ($allowedLookup): bool {
        return isset($allowedLookup[$module['module_key']]);
    }));
}

function routePathToUrl(string $routePath): string
{
    return appUrl($routePath);
}

function consumeFlash(string $key): ?string
{
    startAuthSession();
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = (string) $_SESSION[$key];
    unset($_SESSION[$key]);
    return $message;
}

function setFlash(string $key, string $message): void
{
    startAuthSession();
    $_SESSION[$key] = $message;
}
