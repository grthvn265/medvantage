# MedVantage Database Setup (Authentication Disabled)

## Overview
This setup guide is for the MedVantage system with **user authentication completely disabled**.

⚠️ **IMPORTANT**: All user management, login, and role-based access control have been removed from the system.

## Setup Instructions

### 1. Create Database
```sql
CREATE DATABASE medvantage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE medvantage_db;
```

### 2. Create Tables
Use the `database_structure.sql` file to create all necessary tables.

**Note**: The following user-related tables have been removed:
- ❌ `users` - User accounts
- ❌ `user_roles` - User role assignments
- ❌ `user_module_overrides` - User permissions
- ❌ `roles` - Roles definition
- ❌ `role_modules` - Role permissions
- ❌ `app_modules` - Module registry

### 3. Database Credentials
Update `components/db.php` if needed:
```php
$host = 'localhost';
$db   = 'medvantage_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
```

## Removed Features

### Authentication
- ❌ Login page (`modules/auth/login.php`)
- ❌ Logout functionality
- ❌ CSRF token validation (kept for compatibility but not enforced)
- ❌ Session-based user tracking

### User Management Module
- ❌ User management interface (`modules/users/`)
- ❌ User creation/editing/deletion
- ❌ Role assignment
- ❌ Permission management

### Access Control
- ❌ Route-based access control
- ❌ Role-based permissions
- ❌ Module visibility restrictions
- ❌ User-specific access overrides

## What's Still Available

✅ All data management modules:
- Patients
- Doctors
- Appointments
- Billing
- Dashboard & Reports

All modules are now **publicly accessible** without authentication.

## Database Tables Remaining

Only these core tables are needed:

### Patients
- `patients` - Patient records
- Contains: name, DOB, contact info, registration date, archive status

### Doctors
- `doctors` - Doctor records
- `doctor_available_times` - Doctor availability
- `doctor_unavailable_days` - Blocked dates for doctors

### Appointments
- `appointments` - Appointment records
- `blocked_dates` - System-wide blocked dates

### Visits
- `visits` - Medical visit records

### Billing
- `billing` - Billing/payment records

## Session Handling

Sessions are still initialized for CSRF token functionality, but:
- No user validation
- No role checking
- No access restrictions

All routes are directly accessible.

## Security Note

⚠️ **This configuration is suitable for:**
- Closed/local network installations
- Development environments
- Single-user systems
- Trusted environments only

⚠️ **NOT recommended for:**
- Public-facing systems
- Multi-user environments
- Systems handling sensitive data
- Internet-exposed applications

## Reverting Changes

If you want to re-enable authentication:
1. Use the original `database_structure.sql` file
2. Restore the original `components/auth.php`
3. Restore `modules/auth/` and `modules/users/` folders
4. Update sidebar and create login redirects

---

**Last Updated**: April 3, 2026
