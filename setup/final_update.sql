-- ============================================
-- FINAL UPDATE: Doctor Time Ranges
-- Changes individual time slots to time ranges
-- ============================================

-- Step 1: Create new table for doctor working hours (time ranges)
CREATE TABLE IF NOT EXISTS `doctor_working_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_day_per_doctor` (`doctor_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 2: Drop old doctor_available_times table (if migrating existing data, comment this out)
-- Uncomment this line only if you want to remove the old table after data migration
-- DROP TABLE IF EXISTS `doctor_available_times`;

-- Step 3: Update appointments table to ensure proper time storage
-- (appointments table already uses appointment_time as TIME, so no changes needed)

-- Step 4: Create index for faster queries
CREATE INDEX IF NOT EXISTS `idx_doctor_id` ON `doctor_working_hours` (`doctor_id`);
CREATE INDEX IF NOT EXISTS `idx_day_of_week` ON `doctor_working_hours` (`day_of_week`);

-- ============================================
-- Sample Data: Insert example doctor working hours
-- (Uncomment to populate test data)
-- ============================================
-- INSERT INTO doctor_working_hours (doctor_id, day_of_week, start_time, end_time) VALUES
-- (16, 'Monday', '09:00:00', '17:00:00'),
-- (16, 'Tuesday', '09:00:00', '17:00:00'),
-- (16, 'Wednesday', '09:00:00', '17:00:00'),
-- (16, 'Thursday', '09:00:00', '17:00:00'),
-- (16, 'Friday', '09:00:00', '17:00:00');
