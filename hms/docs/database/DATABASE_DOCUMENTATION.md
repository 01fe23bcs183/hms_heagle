# HMS Heagle Database Documentation

## Overview

HMS Heagle uses MySQL as its primary database. The database schema is designed to support all hospital management operations including patient management, appointments, billing, inventory, and more.

## Entity Relationship Diagram

```
+------------------+       +------------------+       +------------------+
|      users       |       |     patients     |       |     doctors      |
+------------------+       +------------------+       +------------------+
| id (PK)          |<----->| id (PK)          |       | id (PK)          |
| first_name       |       | user_id (FK)     |       | user_id (FK)     |
| last_name        |       | patient_unique_id|       | doctor_department|
| email            |       | blood_group      |       | specialist       |
| password         |       | address          |       | qualification    |
| phone            |       | ...              |       | ...              |
| ...              |       +--------+---------+       +--------+---------+
+--------+---------+                |                          |
         |                          |                          |
         |                          v                          v
         |                 +------------------+       +------------------+
         |                 |   appointments   |       |    schedules     |
         |                 +------------------+       +------------------+
         |                 | id (PK)          |       | id (PK)          |
         |                 | patient_id (FK)  |       | doctor_id (FK)   |
         |                 | doctor_id (FK)   |       | available_on     |
         |                 | department_id    |       | available_from   |
         |                 | opd_date         |       | available_to     |
         |                 | problem          |       | ...              |
         |                 | ...              |       +------------------+
         |                 +--------+---------+
         |                          |
         v                          v
+------------------+       +------------------+       +------------------+
|      bills       |       | ipd_patient_dept |       | opd_patient_dept |
+------------------+       +------------------+       +------------------+
| id (PK)          |       | id (PK)          |       | id (PK)          |
| patient_id (FK)  |       | patient_id (FK)  |       | patient_id (FK)  |
| bill_id          |       | case_id (FK)     |       | case_id (FK)     |
| amount           |       | doctor_id (FK)   |       | doctor_id (FK)   |
| payment_mode     |       | bed_id (FK)      |       | appointment_date |
| ...              |       | admission_date   |       | ...              |
+------------------+       | discharge_date   |       +------------------+
                           | ...              |
                           +--------+---------+
                                    |
                                    v
+------------------+       +------------------+       +------------------+
|      beds        |       |   bed_assigns    |       |    bed_types     |
+------------------+       +------------------+       +------------------+
| id (PK)          |       | id (PK)          |       | id (PK)          |
| bed_type_id (FK) |       | bed_id (FK)      |       | title            |
| name             |       | ipd_patient_id   |       | description      |
| charge           |       | assign_date      |       | ...              |
| is_available     |       | discharge_date   |       +------------------+
| ...              |       | ...              |
+------------------+       +------------------+

+------------------+       +------------------+       +------------------+
|    medicines     |       |   blood_banks    |       | pathology_tests  |
+------------------+       +------------------+       +------------------+
| id (PK)          |       | id (PK)          |       | id (PK)          |
| category_id (FK) |       | blood_group      |       | patient_id (FK)  |
| brand_id (FK)    |       | remained_bags    |       | doctor_id (FK)   |
| name             |       | ...              |       | category_id (FK) |
| selling_price    |       +------------------+       | test_name        |
| buying_price     |                                  | ...              |
| quantity         |       +------------------+       +------------------+
| ...              |       | radiology_tests  |
+------------------+       +------------------+
                           | id (PK)          |
                           | patient_id (FK)  |
                           | doctor_id (FK)   |
                           | category_id (FK) |
                           | test_name        |
                           | ...              |
                           +------------------+
```

## Core Tables

### Users Table

The central user table for all system users.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| first_name | varchar(191) | User's first name |
| last_name | varchar(191) | User's last name |
| email | varchar(191) | Unique email address |
| email_verified_at | timestamp | Email verification timestamp |
| password | varchar(191) | Hashed password |
| phone | varchar(191) | Phone number |
| gender | tinyint | Gender (0=male, 1=female, 2=other) |
| qualification | varchar(191) | User's qualification |
| blood_group | varchar(191) | Blood group |
| designation | varchar(191) | Job designation |
| dob | date | Date of birth |
| status | tinyint | Account status (0=inactive, 1=active) |
| language | varchar(191) | Preferred language |
| profile_image | varchar(191) | Profile image path |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Patients Table

Patient-specific information linked to users.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| patient_unique_id | varchar(191) | Unique patient identifier |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Doctors Table

Doctor-specific information linked to users.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| doctor_department_id | bigint | Foreign key to departments |
| specialist | varchar(191) | Specialization |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Appointments Table

Patient appointments with doctors.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| doctor_id | bigint | Foreign key to doctors |
| department_id | bigint | Foreign key to departments |
| opd_date | datetime | Appointment date/time |
| problem | text | Patient's problem description |
| is_completed | tinyint | Completion status |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### IPD Patient Department Table

Inpatient admissions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| case_id | bigint | Foreign key to patient cases |
| doctor_id | bigint | Foreign key to doctors |
| bed_id | bigint | Foreign key to beds |
| ipd_number | varchar(191) | Unique IPD number |
| height | varchar(191) | Patient height |
| weight | varchar(191) | Patient weight |
| bp | varchar(191) | Blood pressure |
| symptoms | text | Symptoms description |
| notes | text | Additional notes |
| admission_date | datetime | Admission date/time |
| discharge_date | datetime | Discharge date/time |
| bill_status | tinyint | Billing status |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### OPD Patient Department Table

Outpatient visits.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| case_id | bigint | Foreign key to patient cases |
| doctor_id | bigint | Foreign key to doctors |
| opd_number | varchar(191) | Unique OPD number |
| height | varchar(191) | Patient height |
| weight | varchar(191) | Patient weight |
| bp | varchar(191) | Blood pressure |
| symptoms | text | Symptoms description |
| notes | text | Additional notes |
| appointment_date | datetime | Visit date/time |
| standard_charge | decimal | Standard charge |
| payment_mode | tinyint | Payment mode |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Bills Table

Patient billing records.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| bill_id | varchar(191) | Unique bill identifier |
| bill_date | date | Bill date |
| amount | decimal | Total amount |
| payment_mode | tinyint | Payment mode |
| status | tinyint | Payment status |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Beds Table

Hospital bed inventory.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| bed_type_id | bigint | Foreign key to bed types |
| name | varchar(191) | Bed name/number |
| description | text | Description |
| charge | decimal | Daily charge |
| is_available | tinyint | Availability status |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Medicines Table

Pharmacy inventory.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_id | bigint | Foreign key to categories |
| brand_id | bigint | Foreign key to brands |
| name | varchar(191) | Medicine name |
| selling_price | decimal | Selling price |
| buying_price | decimal | Buying price |
| quantity | int | Stock quantity |
| available_quantity | int | Available quantity |
| salt_composition | varchar(191) | Salt composition |
| side_effects | text | Side effects |
| description | text | Description |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Blood Banks Table

Blood inventory.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| blood_group | varchar(191) | Blood group type |
| remained_bags | int | Available bags |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Pathology Tests Table

Pathology test records.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| doctor_id | bigint | Foreign key to doctors |
| category_id | bigint | Foreign key to categories |
| test_name | varchar(191) | Test name |
| short_name | varchar(191) | Short name |
| test_type | varchar(191) | Test type |
| subcategory | varchar(191) | Subcategory |
| method | varchar(191) | Test method |
| report_days | int | Days for report |
| charge | decimal | Test charge |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

### Radiology Tests Table

Radiology test records.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| patient_id | bigint | Foreign key to patients |
| doctor_id | bigint | Foreign key to doctors |
| category_id | bigint | Foreign key to categories |
| test_name | varchar(191) | Test name |
| short_name | varchar(191) | Short name |
| test_type | varchar(191) | Test type |
| subcategory | varchar(191) | Subcategory |
| report_days | int | Days for report |
| charge | decimal | Test charge |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

## Relationships

### One-to-One Relationships

- User -> Patient (user_id)
- User -> Doctor (user_id)

### One-to-Many Relationships

- Patient -> Appointments
- Patient -> Bills
- Patient -> IPD Admissions
- Patient -> OPD Visits
- Doctor -> Appointments
- Doctor -> Schedules
- Bed Type -> Beds
- Medicine Category -> Medicines

### Many-to-Many Relationships

- Users <-> Roles (via model_has_roles)
- Roles <-> Permissions (via role_has_permissions)

## Indexes

Key indexes for performance optimization:

```sql
-- Users table
CREATE INDEX users_email_index ON users(email);
CREATE INDEX users_status_index ON users(status);

-- Patients table
CREATE INDEX patients_user_id_index ON patients(user_id);
CREATE INDEX patients_patient_unique_id_index ON patients(patient_unique_id);

-- Appointments table
CREATE INDEX appointments_patient_id_index ON appointments(patient_id);
CREATE INDEX appointments_doctor_id_index ON appointments(doctor_id);
CREATE INDEX appointments_opd_date_index ON appointments(opd_date);

-- IPD table
CREATE INDEX ipd_patient_id_index ON ipd_patient_departments(patient_id);
CREATE INDEX ipd_doctor_id_index ON ipd_patient_departments(doctor_id);
CREATE INDEX ipd_admission_date_index ON ipd_patient_departments(admission_date);

-- Bills table
CREATE INDEX bills_patient_id_index ON bills(patient_id);
CREATE INDEX bills_status_index ON bills(status);
```

## Database Migrations

All database changes are managed through Laravel migrations located in `database/migrations/`. Key migrations include:

- `create_users_table.php` - Core users table
- `create_patients_table.php` - Patient records
- `create_doctors_table.php` - Doctor records
- `create_appointments_table.php` - Appointments
- `create_ipd_patient_departments_table.php` - IPD admissions
- `create_opd_patient_departments_table.php` - OPD visits
- `create_bills_table.php` - Billing records
- `create_beds_table.php` - Bed inventory
- `create_medicines_table.php` - Pharmacy inventory
- `create_blood_banks_table.php` - Blood inventory
- `create_pathology_tests_table.php` - Pathology tests
- `create_radiology_tests_table.php` - Radiology tests

## Seeders

Database seeders for initial data are in `database/seeders/`:

- `DatabaseSeeder.php` - Main seeder
- `DefaultUserSeeder.php` - Default admin user
- `DefaultRoleSeeder.php` - Default roles
- `SettingTableSeeder.php` - System settings

## Backup and Recovery

Recommended backup strategy:

1. **Daily backups**: Full database dump
2. **Transaction logs**: Enable binary logging for point-in-time recovery
3. **Retention**: Keep 30 days of backups

```bash
# Backup command
mysqldump -u root -p hms_heagle > backup_$(date +%Y%m%d).sql

# Restore command
mysql -u root -p hms_heagle < backup_20241219.sql
```

## Performance Considerations

1. **Indexing**: Ensure proper indexes on frequently queried columns
2. **Query optimization**: Use eager loading to prevent N+1 queries
3. **Connection pooling**: Configure appropriate connection pool size
4. **Caching**: Use Redis/Memcached for frequently accessed data
5. **Partitioning**: Consider partitioning large tables by date
