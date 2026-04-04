-- ============================================
-- ALTER PATIENTS TABLE SCHEMA
-- Update fields to match new structure
-- ============================================

-- Step 1: Drop foreign key constraints if they exist
ALTER TABLE appointments DROP FOREIGN KEY appointments_ibfk_1;
ALTER TABLE billing DROP FOREIGN KEY billing_ibfk_1;
ALTER TABLE visits DROP FOREIGN KEY visits_ibfk_1;

-- Step 2: Modify existing columns and add new ones
ALTER TABLE patients
-- Rename columns
CHANGE COLUMN patient_number contact_number VARCHAR(11),
CHANGE COLUMN email_address email VARCHAR(100),
-- Drop columns no longer needed
DROP COLUMN IF EXISTS civil_status,
DROP COLUMN IF EXISTS nationality,
DROP COLUMN IF EXISTS archived_at,
-- Modify is_archived to status
MODIFY COLUMN is_archived ENUM('active', 'archive') DEFAULT 'active',
-- Add new column for emergency email
ADD COLUMN emergency_email VARCHAR(100) AFTER emergency_contact_number;

-- Step 3: Update existing data - convert is_archived to status
UPDATE patients SET is_archived = 'active' WHERE is_archived = 0;
UPDATE patients SET is_archived = 'archive' WHERE is_archived = 1;

-- Step 4: Rename the column from is_archived to status
ALTER TABLE patients CHANGE COLUMN is_archived status ENUM('active', 'archive') DEFAULT 'active';

-- Step 5: Re-add foreign key constraints
ALTER TABLE appointments ADD CONSTRAINT appointments_ibfk_1 FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE;
ALTER TABLE billing ADD CONSTRAINT billing_ibfk_1 FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE;
ALTER TABLE visits ADD CONSTRAINT visits_ibfk_1 FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE;

-- Step 6: Final table structure (for reference)
-- SELECT * FROM patients LIMIT 1;
