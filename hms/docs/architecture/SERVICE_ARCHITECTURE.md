# HMS Heagle Service-Oriented Architecture

## Overview

HMS Heagle has been restructured to follow a clean service-oriented architecture pattern. This document explains the architecture, design patterns, and how the different components interact.

## Architecture Diagram

```
+------------------------------------------------------------------+
|                        CLIENT LAYER                               |
|  +------------------+  +------------------+  +------------------+ |
|  |   Web Browser    |  |   Mobile App     |  |   Third Party    | |
|  |   (Blade Views)  |  |   (API Client)   |  |   Integration    | |
|  +--------+---------+  +--------+---------+  +--------+---------+ |
+-----------|----------------------|----------------------|---------+
            |                      |                      |
            v                      v                      v
+------------------------------------------------------------------+
|                      PRESENTATION LAYER                           |
|  +------------------+  +----------------------------------------+ |
|  |  Web Controllers |  |           API Controllers (V1)         | |
|  |  (Blade/HTML)    |  |  +----------+  +----------+  +-------+ | |
|  |                  |  |  | Patients |  | Doctors  |  | IPD   | | |
|  |                  |  |  +----------+  +----------+  +-------+ | |
|  |                  |  |  +----------+  +----------+  +-------+ | |
|  |                  |  |  | OPD      |  | Billing  |  | Beds  | | |
|  |                  |  |  +----------+  +----------+  +-------+ | |
|  |                  |  |  +----------+  +----------+  +-------+ | |
|  |                  |  |  | Pharmacy |  | Pathology|  |Radiol.| | |
|  |                  |  |  +----------+  +----------+  +-------+ | |
|  +--------+---------+  +------------------+---------------------+ |
+-----------|--------------------------------|----------------------+
            |                                |
            +----------------+---------------+
                             |
                             v
+------------------------------------------------------------------+
|                       SERVICE LAYER                               |
|  +------------------------------------------------------------+  |
|  |                      BaseService                           |  |
|  |  - CRUD Operations    - Pagination    - Filtering          |  |
|  |  - Searching          - Eager Loading - Transactions       |  |
|  +------------------------------------------------------------+  |
|                             ^                                     |
|                             |                                     |
|  +----------+  +----------+  +----------+  +----------+          |
|  | Patient  |  | Doctor   |  | IPD      |  | OPD      |          |
|  | Service  |  | Service  |  | Service  |  | Service  |          |
|  +----------+  +----------+  +----------+  +----------+          |
|  +----------+  +----------+  +----------+  +----------+          |
|  | Billing  |  | Bed      |  | Medicine |  | Pathology|          |
|  | Service  |  | Service  |  | Service  |  | Service  |          |
|  +----------+  +----------+  +----------+  +----------+          |
|  +----------+  +----------+  +----------+                        |
|  | Radiology|  | BloodBank|  | User     |                        |
|  | Service  |  | Service  |  | Service  |                        |
|  +----------+  +----------+  +----------+                        |
+------------------------------------------------------------------+
                             |
                             v
+------------------------------------------------------------------+
|                       DATA LAYER                                  |
|  +------------------------------------------------------------+  |
|  |                    Eloquent Models                         |  |
|  |  Patient, Doctor, Appointment, IpdPatientDepartment,       |  |
|  |  OpdPatientDepartment, Bill, Bed, Medicine, PathologyTest, |  |
|  |  RadiologyTest, BloodBank, User, etc.                      |  |
|  +------------------------------------------------------------+  |
|                             |                                     |
|                             v                                     |
|  +------------------------------------------------------------+  |
|  |                    MySQL Database                          |  |
|  +------------------------------------------------------------+  |
+------------------------------------------------------------------+
```

## Design Patterns

### 1. Service Layer Pattern

All business logic is encapsulated in service classes located in `app/Services/`. Each service:

- Extends `BaseService` for common functionality
- Handles a specific domain (Patients, Doctors, IPD, etc.)
- Contains all business logic for that domain
- Uses database transactions for data consistency
- Implements eager loading for performance

### 2. Base Service Pattern

The `BaseService` class (`app/Services/Core/BaseService.php`) provides:

```php
abstract class BaseService
{
    protected string $modelClass;
    protected array $defaultRelations = [];
    protected array $searchableFields = [];
    protected array $filterableFields = [];

    // Common methods
    public function getAll(array $filters = [], int $perPage = 15)
    public function findById(int $id, array $relations = [])
    public function create(array $data)
    public function update(int $id, array $data)
    public function delete(int $id)
    protected function applyFilters($query, array $filters)
    protected function applySearch($query, ?string $search)
}
```

### 3. API Response Trait

The `ApiResponse` trait (`app/Traits/ApiResponse.php`) standardizes all API responses:

```php
trait ApiResponse
{
    protected function success($data, string $message = 'Success', int $code = 200)
    protected function error(string $message, int $code = 400, $errors = null)
    protected function notFound(string $message = 'Resource not found')
    protected function validationError($errors)
    protected function unauthorized(string $message = 'Unauthorized')
    protected function forbidden(string $message = 'Forbidden')
}
```

### 4. Repository Pattern (via Eloquent)

Models serve as repositories, with services abstracting data access:

```
Controller -> Service -> Model -> Database
```

## Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               ├── ApiController.php      # Base API controller
│               ├── Auth/
│               │   └── AuthController.php
│               ├── Patients/
│               │   └── PatientController.php
│               ├── Appointments/
│               │   └── AppointmentController.php
│               ├── Doctors/
│               │   └── DoctorController.php
│               ├── IPD/
│               │   └── IpdController.php
│               ├── OPD/
│               │   └── OpdController.php
│               ├── Billing/
│               │   └── BillController.php
│               ├── Beds/
│               │   └── BedController.php
│               ├── BloodBank/
│               │   └── BloodBankController.php
│               ├── Pharmacy/
│               │   └── MedicineController.php
│               ├── Pathology/
│               │   └── PathologyController.php
│               ├── Radiology/
│               │   └── RadiologyController.php
│               └── Users/
│                   └── UserController.php
├── Services/
│   ├── Core/
│   │   └── BaseService.php
│   ├── Patients/
│   │   └── PatientService.php
│   ├── Appointments/
│   │   └── AppointmentService.php
│   ├── Doctors/
│   │   └── DoctorService.php
│   ├── IPD/
│   │   └── IpdAdmissionService.php
│   ├── OPD/
│   │   └── OpdVisitService.php
│   ├── Billing/
│   │   └── BillService.php
│   ├── Beds/
│   │   └── BedService.php
│   ├── BloodBank/
│   │   └── BloodBankService.php
│   ├── Pharmacy/
│   │   └── MedicineService.php
│   ├── Pathology/
│   │   └── PathologyService.php
│   ├── Radiology/
│   │   └── RadiologyService.php
│   └── Users/
│       └── UserService.php
└── Traits/
    └── ApiResponse.php
```

## Request Flow

### API Request Flow

```
1. HTTP Request
       |
       v
2. Route (routes/api.php)
       |
       v
3. Middleware (auth:sanctum, role checks)
       |
       v
4. API Controller
   - Validates request
   - Calls service method
       |
       v
5. Service Layer
   - Contains business logic
   - Uses transactions
   - Calls model methods
       |
       v
6. Model (Eloquent)
   - Database operations
       |
       v
7. Database
       |
       v
8. Response (via ApiResponse trait)
```

### Example: Creating a Patient

```php
// 1. Route: POST /api/v1/patients
Route::apiResource('patients', PatientController::class);

// 2. Controller receives request
public function store(Request $request)
{
    $validated = $request->validate([...]);
    $patient = $this->patientService->createPatient($validated);
    return $this->success($patient, 'Patient created', 201);
}

// 3. Service handles business logic
public function createPatient(array $data): Patient
{
    return DB::transaction(function () use ($data) {
        $user = User::create([...]);
        $patient = Patient::create([...]);
        return $patient->load($this->defaultRelations);
    });
}
```

## Authentication & Authorization

### Authentication

- Uses Laravel Sanctum for API token authentication
- Tokens are issued on login and revoked on logout
- All protected routes require `auth:sanctum` middleware

### Authorization (Spatie Laravel Permission)

- Role-based access control using Spatie Laravel Permission
- Roles: Super Admin, Doctor, Nurse, Pharmacist, Lab Technician, Receptionist, Accountant, Patient
- Permissions can be assigned to roles or directly to users

## API Documentation

API documentation is generated using L5-Swagger (OpenAPI 3.0):

- Swagger UI available at: `/api/documentation`
- OpenAPI spec at: `/docs/api-docs.json`
- All controllers have full Swagger annotations

## Benefits of This Architecture

1. **Separation of Concerns**: Controllers handle HTTP, services handle business logic
2. **Testability**: Services can be unit tested independently
3. **Reusability**: Services can be used by both web and API controllers
4. **Maintainability**: Clear structure makes code easier to understand and modify
5. **Scalability**: New features can be added without affecting existing code
6. **Consistency**: BaseService and ApiResponse ensure consistent behavior
7. **Documentation**: Swagger annotations provide automatic API documentation
