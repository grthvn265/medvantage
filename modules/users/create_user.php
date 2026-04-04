<?php
require '../../components/db.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || $currentUser['role_key'] !== 'super_admin') {
    header('Location: ' . appUrl('/modules/dashboard/index.php?denied=1'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$roleId = (int) ($_POST['role_id'] ?? 0);
$moduleIds = isset($_POST['module_ids']) && is_array($_POST['module_ids'])
    ? array_values(array_unique(array_map('intval', $_POST['module_ids'])))
    : [];

if ($fullName === '' || $username === '' || $password === '' || $roleId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('flash_error', 'Please complete all required fields with valid values.');
    header('Location: users.php');
    exit;
}

if (strlen($fullName) < 2 || strlen($fullName) > 150 || !preg_match("/^[A-Za-z][A-Za-z\\s'\\-]{1,149}$/", $fullName)) {
    setFlash('flash_error', 'Full name must be 2-150 characters and contain letters, spaces, apostrophes, or hyphens only.');
    header('Location: users.php');
    exit;
}

if (strlen($email) > 150) {
    setFlash('flash_error', 'Email must be 150 characters or fewer.');
    header('Location: users.php');
    exit;
}

if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) {
    setFlash('flash_error', 'Username must be 4-30 characters and use letters, numbers, underscore, or dot.');
    header('Location: users.php');
    exit;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/', $password)) {
    setFlash('flash_error', 'Password must be 8-72 characters and include uppercase, lowercase, number, and special character.');
    header('Location: users.php');
    exit;
}

$roleStmt = $pdo->prepare('SELECT role_key FROM roles WHERE role_id = ? LIMIT 1');
$roleStmt->execute([$roleId]);
$roleKey = $roleStmt->fetchColumn();

if (!$roleKey || $roleKey === 'super_admin') {
    setFlash('flash_error', 'Only non-super-admin accounts can be created from this page.');
    header('Location: users.php');
    exit;
}

if (count($moduleIds) === 0) {
    setFlash('flash_error', 'Select at least one module for this account.');
    header('Location: users.php');
    exit;
}

$validModulesStmt = $pdo->query("SELECT module_id FROM app_modules WHERE is_enabled = 1 AND module_key <> 'users'");
$validModuleIds = array_map('intval', array_column($validModulesStmt->fetchAll(PDO::FETCH_ASSOC), 'module_id'));
$validLookup = array_fill_keys($validModuleIds, true);

foreach ($moduleIds as $moduleId) {
    if (!isset($validLookup[$moduleId])) {
        setFlash('flash_error', 'One or more selected modules are invalid.');
        header('Location: users.php');
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $insertUser = $pdo->prepare('
        INSERT INTO users (username, password_hash, full_name, email, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ');

    $insertUser->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $fullName,
        $email,
        $roleId,
    ]);

    $userId = (int) $pdo->lastInsertId();

    $insertAccess = $pdo->prepare('INSERT INTO user_module_access (user_id, module_id) VALUES (?, ?)');
    foreach ($moduleIds as $moduleId) {
        $insertAccess->execute([$userId, $moduleId]);
    }

    $pdo->commit();
    setFlash('flash_success', 'Account created successfully.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (str_contains(strtolower($exception->getMessage()), 'duplicate')) {
        setFlash('flash_error', 'Username or email already exists.');
    } else {
        setFlash('flash_error', 'Unable to create account right now.');
    }
}

header('Location: users.php');
exit;
