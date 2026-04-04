# MedVantage Database Setup (RBAC Enabled)

## Overview
This setup guide is for the MedVantage system with **role-based access control enabled**.

Authentication, login, and account permissions are active again.

## Setup Instructions

### 1. Create Database
```sql
CREATE DATABASE medvantage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE medvantage_db;
```

### 2. Create Core Tables
Use `database_structure.sql` (or the current exported schema) to create the base MedVantage tables.

### 3. Enable RBAC Tables
Run `setup/ENABLE_RBAC.sql` to create and seed:
- ✅ `roles`
- ✅ `users`
- ✅ `user_module_access`
- ✅ `app_modules` updates for current module routes

### 4. Database Credentials
Update `components/db.php` if needed:
```php
$host = 'localhost';
$db   = 'medvantage_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
```

## Active Authentication Features

### Authentication
- ✅ Login page (`modules/auth/login.php`)
- ✅ Logout functionality (`modules/auth/logout.php`)
- ✅ Session-based user tracking
- ✅ Route guards through `components/auth.php`

### Accounts Module
- ✅ Accounts directory (`modules/users/users.php`)
- ✅ Account creation with module checklist
- ✅ Account activation/deactivation controls
- ✅ Super admin-only access for Accounts tab

### Access Control
- ✅ Route-based access control
- ✅ Role-based permission model
- ✅ Per-user module checklist access (`user_module_access`)
- ✅ Permission-aware sidebar navigation

## Owner Account Bootstrap

On first app load after RBAC setup, an Owner super admin account is automatically created:

- Username: `owner`
- Password: `Owner@12345`

Change this password immediately in production deployments.

## Available Modules

✅ All data management modules:
- Patients
- Doctors
- Appointments
- Billing
- Dashboard & Reports

Modules are available based on account role and module checklist permissions.

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

Sessions are required for authenticated access and module authorization checks.

## Security Note

Use strong passwords, enforce HTTPS, and restrict database/network access in production environments.

## Notes

- The Accounts tab is visible only to super admin users.
- New non-super-admin accounts must be assigned module access via checklist.
- Unauthorized requests now receive redirect (HTML) or 401/403 (JSON/XHR) responses.

---

**Last Updated**: April 4, 2026
