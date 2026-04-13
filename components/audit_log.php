<?php

declare(strict_types=1);

function setLastAuditError(?string $message): void
{
    $GLOBALS['__last_audit_error'] = $message;
}

function getLastAuditError(): ?string
{
    $value = $GLOBALS['__last_audit_error'] ?? null;
    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * Ensure audit_logs.action accepts PRINT and other dynamic action values.
 */
function ensureAuditActionColumnSupportsPrint(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT DATA_TYPE
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
         LIMIT 1'
    );
    $stmt->execute(['audit_logs', 'action']);
    $dataType = strtolower((string) $stmt->fetchColumn());

    if ($dataType === 'enum' || $dataType === 'set') {
        $pdo->exec(
            "ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(30) NOT NULL COMMENT 'e.g. CREATE, UPDATE, DELETE, ARCHIVE, RESTORE, LOGIN, LOGOUT, PRINT'"
        );
    }

    $checked = true;
}

/**
 * Write a single audit log entry.
 */
function logAudit(PDO $pdo, string $action, string $module, ?int $entityId, string $description): bool
{
    try {
        setLastAuditError(null);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;

        // Keep only the first IP when behind a proxy
        if ($ip !== null && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        // Truncate to column length just in case
        if ($ip !== null && strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }

        ensureAuditActionColumnSupportsPrint($pdo);

        $stmt = $pdo->prepare('
            INSERT INTO audit_logs (user_id, action, module, entity_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([$userId, strtoupper($action), $module, $entityId, $description, $ip]);
        return true;
    } catch (Throwable $e) {
        setLastAuditError(get_class($e) . ': ' . $e->getMessage());
        // Audit failures must never break the main request.
        return false;
    }
}
