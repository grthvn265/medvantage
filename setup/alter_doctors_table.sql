-- Add new fields to doctors table to match the patient form structure
ALTER TABLE doctors ADD COLUMN date_of_birth DATE AFTER suffix;
ALTER TABLE doctors ADD COLUMN sex VARCHAR(10) AFTER date_of_birth;
ALTER TABLE doctors ADD COLUMN address VARCHAR(200) AFTER sex;
ALTER TABLE doctors ADD COLUMN email VARCHAR(100) AFTER address;
ALTER TABLE doctors ADD COLUMN emergency_contact_person VARCHAR(100) AFTER email;
ALTER TABLE doctors ADD COLUMN emergency_contact_number VARCHAR(11) AFTER emergency_contact_person;
ALTER TABLE doctors ADD COLUMN emergency_email VARCHAR(100) AFTER emergency_contact_number;

-- Note: The following fields are already present or managed separately:
-- - doctor_id (primary key)
-- - last_name, first_name, middle_initial, suffix (already exist)
-- - contact_number (already exists)
-- - is_archived, archived_at (already exist)
-- - created_at for registered_date (already exists)
-- - date_available and time_available are stored in doctor_unavailable_days and doctor_available_times tables
