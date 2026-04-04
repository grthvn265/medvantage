-- MedVantage RBAC Enable Script
-- Run this against medvantage_db to re-enable authentication and role-based module access.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_role_key` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_active` (`is_active`),
  CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_module_access` (
  `access_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`access_id`),
  UNIQUE KEY `uk_user_module` (`user_id`,`module_id`),
  KEY `idx_access_module` (`module_id`),
  CONSTRAINT `fk_access_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_access_module_id` FOREIGN KEY (`module_id`) REFERENCES `app_modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `roles` (`role_key`, `role_name`) VALUES
('super_admin', 'Super Admin'),
('staff', 'Staff');

INSERT INTO `app_modules` (`module_key`, `module_label`, `route_path`, `icon_class`, `sort_order`, `is_enabled`)
VALUES
('dashboard', 'Dashboard', '/modules/dashboard/index.php', 'bi-speedometer2', 1, 1),
('patients', 'Patients', '/modules/patients/patients.php', 'bi-people', 2, 1),
('doctors', 'Doctors', '/modules/doctors/doctors.php', 'bi-person-badge', 3, 1),
('appointments', 'Appointments', '/modules/appointments/appointment.php', 'bi-calendar-check', 4, 1),
('billing', 'Billing', '/modules/billing/billing.php', 'bi-receipt', 5, 1),
('users', 'Accounts', '/modules/users/users.php', 'bi-shield-lock', 6, 1)
ON DUPLICATE KEY UPDATE
  module_label = VALUES(module_label),
  route_path = VALUES(route_path),
  icon_class = VALUES(icon_class),
  sort_order = VALUES(sort_order),
  is_enabled = VALUES(is_enabled);

COMMIT;
