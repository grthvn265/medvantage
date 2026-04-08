<?php
require '../../components/db.php';
require '../../components/audit_log.php';

$activeUser = getCurrentUser($pdo);
if ($activeUser) {
    logAudit(
        $pdo,
        'LOGOUT',
        'auth',
        (int) $activeUser['user_id'],
        'User logged out: ' . $activeUser['username']
    );
}

logoutCurrentUser();
header('Location: ' . appUrl('/modules/auth/login.php'));
exit;
