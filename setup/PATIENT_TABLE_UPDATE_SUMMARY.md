# Patient Table Updates Summary

## Overview
Updated the patients table schema and all related forms to match the new field structure.

## SQL Changes (Run `alter_patients_table.sql`)

### Fields Removed
- `civil_status` - No longer needed
- `nationality` - No longer needed  
- `archived_at` - Replaced with status field

### Fields Renamed
- `patient_number` → `contact_number`
- `email_address` → `email`
- `is_archived` (0/1) → `status` (enum: 'active', 'archive')

### Fields Added
- `emergency_email` - New field for emergency contact email

## Final Patient Table Fields
1. `patient_id` (PRIMARY KEY, AUTO_INCREMENT)
2. `last_name` (VARCHAR)
3. `first_name` (VARCHAR)
4. `middle_initial` (CHAR)
5. `suffix` (VARCHAR)
6. `date_of_birth` (DATE)
7. `sex` (ENUM: 'Male', 'Female', 'Other')
8. `address` (TEXT)
9. `contact_number` (VARCHAR 11)
10. `email` (VARCHAR 100)
11. `emergency_contact_person` (VARCHAR)
12. `emergency_contact_number` (VARCHAR 11)
13. `emergency_email` (VARCHAR 100) - NEW
14. `registered_date` (DATETIME)
15. `status` (ENUM: 'active', 'archive') - CHANGED

## Files Updated

### Backend Handlers
- ✅ `modules/patients/add_patient.php` - Updated field names and removed validation for deleted fields
- ✅ `modules/patients/update_patient.php` - Updated field names and removed validation for deleted fields
- ✅ `modules/patients/patient_archive_handler.php` - Changed `is_archived` to `status` queries

### Frontend Views
- ✅ `modules/patients/patients.php` - Updated form fields and table queries
- ✅ `modules/patients/view_patient.php` - Updated form fields and display
- ✅ All modal forms updated with new field names

### Forms Changes
Removed Fields from All Forms:
- Civil Status dropdown
- Nationality text input

Renamed Fields in All Forms:
- `patient_number` → `contact_number`
- `email_address` → `email`

Added Fields to All Forms:
- `emergency_email` - New email input field

## Database Migration Steps

1. **Backup your database first!**
   ```sql
   -- Create backup
   CREATE TABLE patients_backup AS SELECT * FROM patients;
   ```

2. **Run the SQL migration:**
   ```sql
   -- Execute the alter_patients_table.sql file
   -- This will:
   -- - Drop foreign key constraints
   -- - Modify existing columns
   -- - Drop unwanted columns
   -- - Add new columns
   -- - Update existing data
   -- - Rename columns
   -- - Re-add foreign key constraints
   ```

3. **Test the application** to ensure all forms and displays work correctly

## Form Field Organization

### Personal Information
- Full Name (Last, First, Middle Initial, Suffix)
- Age (calculated)
- Sex
- Date of Birth
- Address

### Contact Information
- Patient Contact Number
- Email Address
- Emergency Contact Person
- Emergency Contact Number
- Emergency Email

### System Fields
- Patient ID
- Registered Date
- Status (active/archive)

## Notes
- All validation rules remain the same
- Contact number format: 11 digits starting with 09
- Email validation using HTML5 email input
- All forms include real-time validation feedback
- Data for deleted fields will be lost during migration
