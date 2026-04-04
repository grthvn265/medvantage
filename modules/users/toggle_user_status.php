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

$userId = (int) ($_POST['user_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 0);

if ($userId <= 0) {
    setFlash('flash_error', 'Invalid user.');
    header('Location: users.php');
    exit;
}

$userStmt = $pdo->prepare('
    SELECT u.user_id, u.username, r.role_key
    FROM users u
    INNER JOIN roles r ON r.role_id = u.role_id
    WHERE u.user_id = ?
    LIMIT 1
');
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    setFlash('flash_error', 'User does not exist.');
    header('Location: users.php');
    exit;
}

if ($user['username'] === 'owner' || $user['role_key'] === 'super_admin') {
    setFlash('flash_error', 'Protected account cannot be deactivated.');
    header('Location: users.php');
    exit;
}

$nextStatus = $currentStatus === 1 ? 0 : 1;
$update = $pdo->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
$update->execute([$nextStatus, $userId]);

setFlash('flash_success', $nextStatus === 1 ? 'Account activated.' : 'Account deactivated.');
header('Location: users.php');
exit;
