-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 03:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medvantage_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `is_archived`, `archived_at`, `created_at`) VALUES
(23, 19, 16, '2026-05-24', '11:00:00', '', 'Completed', 0, NULL, '2026-04-03 11:14:39'),
(24, 19, 16, '2026-07-07', '12:00:00', 'Test', 'Completed', 0, NULL, '2026-04-04 11:12:07'),
(25, 19, 16, '2026-04-05', '10:00:00', 'Follow-up check up for Pneumonia\r\n', 'Completed', 0, NULL, '2026-04-04 13:30:56');

-- --------------------------------------------------------

--
-- Table structure for table `app_modules`
--

CREATE TABLE `app_modules` (
  `module_id` int(11) NOT NULL,
  `module_key` varchar(50) NOT NULL,
  `module_label` varchar(100) NOT NULL,
  `route_path` varchar(255) NOT NULL,
  `icon_class` varchar(60) NOT NULL DEFAULT 'bi-grid',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_modules`
--

INSERT INTO `app_modules` (`module_id`, `module_key`, `module_label`, `route_path`, `icon_class`, `sort_order`, `is_enabled`) VALUES
(1, 'dashboard', 'Dashboard', '/modules/dashboard/index.php', 'bi-speedometer2', 1, 1),
(2, 'patients', 'Patients', '/modules/patients/patients.php', 'bi-people', 2, 1),
(3, 'doctors', 'Doctors', '/modules/doctors/doctors.php', 'bi-person-badge', 3, 1),
(4, 'appointments', 'Appointments', '/modules/appointments/appointment.php', 'bi-calendar-check', 4, 1),
(5, 'billing', 'Billing', '/modules/billing/billing.php', 'bi-receipt', 5, 1),
(6, 'users', 'Accounts', '/modules/users/users.php', 'bi-shield-lock', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `invoice_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Paid','Unpaid','Pending') DEFAULT 'Unpaid',
  `description` varchar(255) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `patient_id`, `doctor_id`, `invoice_id`, `amount`, `status`, `description`, `invoice_date`, `created_at`, `updated_at`, `is_archived`, `archived_at`) VALUES
(12, 19, 16, 'INV-20260403-0FE2D787', 300.00, 'Paid', NULL, '2026-05-24', '2026-04-03 11:15:17', '2026-04-03 11:21:03', 0, NULL),
(13, 19, 16, 'INV-20260404-E724730A', 1000.00, 'Paid', NULL, '2026-07-07', '2026-04-04 11:12:24', '2026-04-04 13:33:13', 0, NULL),
(14, 19, 16, 'INV-20260404-1EF56CA7', 0.00, 'Unpaid', NULL, '2026-04-05', '2026-04-04 13:31:42', '2026-04-04 13:31:42', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_dates`
--

CREATE TABLE `blocked_dates` (
  `id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `emergency_contact_person` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(11) DEFAULT NULL,
  `emergency_email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `unavailable_days` varchar(255) DEFAULT NULL,
  `available_times` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `last_name`, `first_name`, `middle_initial`, `suffix`, `date_of_birth`, `sex`, `address`, `email`, `emergency_contact_person`, `emergency_contact_number`, `emergency_email`, `contact_number`, `created_at`, `is_archived`, `archived_at`, `unavailable_days`, `available_times`) VALUES
(16, 'Lebron', 'James', 'K', NULL, '1980-03-08', 'Male', 'akron ohi0', 'hahayw@gmail.com', 'Savannah James', '09348382828', 'savannah@gmail.com', '09348372727', '2026-04-03 19:14:12', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_available_times`
--

CREATE TABLE `doctor_available_times` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `time_slot` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_available_times`
--

INSERT INTO `doctor_available_times` (`id`, `doctor_id`, `time_slot`) VALUES
(71, 16, '10:00:00'),
(72, 16, '11:00:00'),
(73, 16, '12:00:00'),
(74, 16, '13:00:00'),
(75, 16, '14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_unavailable_days`
--

CREATE TABLE `doctor_unavailable_days` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `emergency_contact_person` varchar(150) DEFAULT NULL,
  `emergency_contact_number` varchar(50) DEFAULT NULL,
  `emergency_email` varchar(100) DEFAULT NULL,
  `registered_date` datetime DEFAULT current_timestamp(),
  `status` enum('active','archive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `last_name`, `first_name`, `middle_initial`, `suffix`, `date_of_birth`, `sex`, `address`, `contact_number`, `email`, `emergency_contact_person`, `emergency_contact_number`, `emergency_email`, `registered_date`, `status`) VALUES
(19, 'Haya', 'Louis Angelo', 'A', NULL, '2002-03-24', 'Male', '3m cristobal street', '09239273277', 'haya@gmail.com', 'Chelys', '09328383883', 'chelys@gmail.com', '2026-04-03 19:13:04', 'active'),
(20, 'Aareva', 'Awdawdawd', 'A', 'Jr.', '2025-01-02', 'Male', 'asdasdasdas', '09999999999', '11111111111111111111111111111111111111111@gmail.com', 'Asdasd', '09999999999', 'asdasd@gmail.com', '2026-04-04 19:01:37', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_key`, `role_name`, `created_at`) VALUES
(1, 'super_admin', 'Super Admin', '2026-04-04 21:18:00'),
(2, 'staff', 'Staff', '2026-04-04 21:18:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role_id`, `is_active`, `last_login_at`, `created_at`) VALUES
(1, 'owner', '$2y$10$uEXyNfs120wckuEPLLHPP.qSm3tYy5kigOhRjkL7hpvDKUmDOR4R.', 'System Owner', 'owner@medvantage.local', 1, 1, '2026-04-04 21:37:23', '2026-04-04 21:19:17'),
(4, 'test_staff', '$2y$10$Yis.izbWplwP49hZ0.Q0qOrDpcvxgZospEPbEaVsmAzIMco1ETSEu', 'Aaron Carl Asuncion Arevalo', 'test@gmail.com', 2, 1, '2026-04-04 21:27:59', '2026-04-04 21:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_module_access`
--

CREATE TABLE `user_module_access` (
  `access_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_module_access`
--

INSERT INTO `user_module_access` (`access_id`, `user_id`, `module_id`, `created_at`) VALUES
(4, 4, 1, '2026-04-04 21:27:48'),
(5, 4, 3, '2026-04-04 21:27:48'),
(6, 4, 5, '2026-04-04 21:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `visit_datetime` datetime NOT NULL,
  `nature_of_visit` varchar(255) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `affected_area` varchar(255) DEFAULT NULL,
  `observation` text DEFAULT NULL,
  `procedure_done` text DEFAULT NULL,
  `meds_prescribed` text DEFAULT NULL,
  `instruction_to_patient` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`visit_id`, `patient_id`, `doctor_id`, `visit_datetime`, `nature_of_visit`, `symptoms`, `affected_area`, `observation`, `procedure_done`, `meds_prescribed`, `instruction_to_patient`, `remarks`, `created_at`, `is_archived`, `archived_at`) VALUES
(17, 19, 16, '2026-05-24 11:00:00', 'wbu', 'ubwdui', 'bdwu', 'buwdbu', 'bwdu', 'bu', 'bdwububd', 'bwud', '2026-04-03 19:15:17', 0, NULL),
(18, 19, 16, '2026-07-07 12:00:00', 'Test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 19:12:24', 0, NULL),
(19, 19, 16, '2026-04-05 11:00:00', 'Test', 'Test', 'Test', 'TestTest', 'Test', 'Test', 'Test', 'Test', '2026-04-04 21:31:42', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `unique_schedule` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_archived` (`is_archived`);

--
-- Indexes for table `app_modules`
--
ALTER TABLE `app_modules`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `module_key` (`module_key`),
  ADD UNIQUE KEY `route_path` (`route_path`),
  ADD KEY `idx_module_enabled` (`is_enabled`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`),
  ADD UNIQUE KEY `invoice_id` (`invoice_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `status` (`status`),
  ADD KEY `invoice_date` (`invoice_date`),
  ADD KEY `idx_archived` (`is_archived`);

--
-- Indexes for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blocked_date` (`blocked_date`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD KEY `idx_doctor_name` (`last_name`,`first_name`),
  ADD KEY `idx_archived` (`is_archived`);

--
-- Indexes for table `doctor_available_times`
--
ALTER TABLE `doctor_available_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_unavailable_days`
--
ALTER TABLE `doctor_unavailable_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `idx_name` (`last_name`,`first_name`),
  ADD KEY `idx_registered` (`registered_date`),
  ADD KEY `idx_sex` (`sex`),
  ADD KEY `idx_archived` (`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uk_role_key` (`role_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_users_username` (`username`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_role_id` (`role_id`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- Indexes for table `user_module_access`
--
ALTER TABLE `user_module_access`
  ADD PRIMARY KEY (`access_id`),
  ADD UNIQUE KEY `uk_user_module` (`user_id`,`module_id`),
  ADD KEY `idx_access_module` (`module_id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_visit_datetime` (`visit_datetime`),
  ADD KEY `idx_archived` (`is_archived`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `app_modules`
--
ALTER TABLE `app_modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3169;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `doctor_available_times`
--
ALTER TABLE `doctor_available_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `doctor_unavailable_days`
--
ALTER TABLE `doctor_unavailable_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1055;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_module_access`
--
ALTER TABLE `user_module_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_available_times`
--
ALTER TABLE `doctor_available_times`
  ADD CONSTRAINT `doctor_available_times_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_unavailable_days`
--
ALTER TABLE `doctor_unavailable_days`
  ADD CONSTRAINT `doctor_unavailable_days_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `user_module_access`
--
ALTER TABLE `user_module_access`
  ADD CONSTRAINT `fk_access_module_id` FOREIGN KEY (`module_id`) REFERENCES `app_modules` (`module_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

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
