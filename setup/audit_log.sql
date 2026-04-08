-- Audit Log Table
-- Run this against medvantage_db to enable audit logging.

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)          DEFAULT NULL COMMENT 'NULL for system/unauthenticated actions',
  `action`      varchar(30)  NOT NULL COMMENT 'e.g. CREATE, UPDATE, DELETE, ARCHIVE, RESTORE, LOGIN, LOGOUT',
  `module`      varchar(50)  NOT NULL COMMENT 'e.g. patients, doctors, appointments, billing, users, auth',
  `entity_id`   int(11)          DEFAULT NULL COMMENT 'PK of the affected record',
  `description` text         NOT NULL,
  `ip_address`  varchar(45)      DEFAULT NULL,
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user`    (`user_id`),
  KEY `idx_audit_module`  (`module`),
  KEY `idx_audit_action`  (`action`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Register audit_log module in app_modules
INSERT INTO `app_modules` (`module_key`, `module_label`, `route_path`, `icon_class`, `sort_order`, `is_enabled`)
VALUES ('audit_log', 'Audit Log', '/modules/audit_log/audit_log.php', 'bi-journal-text', 7, 1)
ON DUPLICATE KEY UPDATE
    `module_label` = VALUES(`module_label`),
    `route_path`   = VALUES(`route_path`),
    `icon_class`   = VALUES(`icon_class`),
    `sort_order`   = VALUES(`sort_order`),
    `is_enabled`   = 1;
