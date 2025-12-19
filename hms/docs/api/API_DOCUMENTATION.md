# HMS Heagle API Documentation

## Overview

HMS Heagle provides a RESTful API for managing hospital operations. All API endpoints are prefixed with `/api/v1` and use JSON for request/response bodies.

## Authentication

The API uses Laravel Sanctum for token-based authentication.

### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "admin@hms.com",
    "password": "123456789"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "Admin",
            "last_name": "User",
            "email": "admin@hms.com"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Using the Token

Include the token in the Authorization header for all protected endpoints:

```http
Authorization: Bearer 1|abc123...
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

## API Endpoints

### Patients

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/patients` | List all patients (paginated) |
| POST | `/api/v1/patients` | Create a new patient |
| GET | `/api/v1/patients/{id}` | Get patient details |
| PUT | `/api/v1/patients/{id}` | Update patient |
| DELETE | `/api/v1/patients/{id}` | Delete patient |
| GET | `/api/v1/patients/dropdown` | Get patients for dropdown |
| GET | `/api/v1/patients/{id}/cases` | Get patient's cases |
| GET | `/api/v1/patients/{id}/appointments` | Get patient's appointments |
| GET | `/api/v1/patients/{id}/bills` | Get patient's bills |
| GET | `/api/v1/patients/{id}/documents` | Get patient's documents |

### Appointments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/appointments` | List all appointments |
| POST | `/api/v1/appointments` | Create appointment |
| GET | `/api/v1/appointments/{id}` | Get appointment details |
| PUT | `/api/v1/appointments/{id}` | Update appointment |
| DELETE | `/api/v1/appointments/{id}` | Delete appointment |
| GET | `/api/v1/appointments/today` | Get today's appointments |
| GET | `/api/v1/appointments/upcoming` | Get upcoming appointments |
| GET | `/api/v1/appointments/statistics` | Get appointment statistics |
| POST | `/api/v1/appointments/{id}/cancel` | Cancel appointment |
| POST | `/api/v1/appointments/{id}/complete` | Mark as completed |
| POST | `/api/v1/appointments/{id}/reschedule` | Reschedule appointment |

### Doctors

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/doctors` | List all doctors |
| POST | `/api/v1/doctors` | Create doctor |
| GET | `/api/v1/doctors/{id}` | Get doctor details |
| PUT | `/api/v1/doctors/{id}` | Update doctor |
| DELETE | `/api/v1/doctors/{id}` | Delete doctor |
| GET | `/api/v1/doctors/dropdown` | Get doctors for dropdown |
| GET | `/api/v1/doctors/statistics` | Get doctor statistics |
| GET | `/api/v1/doctors/{id}/schedules` | Get doctor's schedules |
| GET | `/api/v1/doctors/{id}/appointments` | Get doctor's appointments |
| GET | `/api/v1/doctors/{id}/availability` | Check doctor availability |

### IPD (Inpatient Department)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ipd` | List all IPD admissions |
| POST | `/api/v1/ipd` | Create IPD admission |
| GET | `/api/v1/ipd/{id}` | Get IPD details |
| PUT | `/api/v1/ipd/{id}` | Update IPD admission |
| DELETE | `/api/v1/ipd/{id}` | Delete IPD admission |
| GET | `/api/v1/ipd/current` | Get current admissions |
| GET | `/api/v1/ipd/statistics` | Get IPD statistics |
| POST | `/api/v1/ipd/{id}/discharge` | Discharge patient |

### OPD (Outpatient Department)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/opd` | List all OPD visits |
| POST | `/api/v1/opd` | Create OPD visit |
| GET | `/api/v1/opd/{id}` | Get OPD details |
| PUT | `/api/v1/opd/{id}` | Update OPD visit |
| DELETE | `/api/v1/opd/{id}` | Delete OPD visit |
| GET | `/api/v1/opd/today` | Get today's visits |
| GET | `/api/v1/opd/statistics` | Get OPD statistics |

### Billing

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/bills` | List all bills |
| POST | `/api/v1/bills` | Create bill |
| GET | `/api/v1/bills/{id}` | Get bill details |
| PUT | `/api/v1/bills/{id}` | Update bill |
| DELETE | `/api/v1/bills/{id}` | Delete bill |
| GET | `/api/v1/bills/unpaid` | Get unpaid bills |
| GET | `/api/v1/bills/statistics` | Get billing statistics |
| POST | `/api/v1/bills/{id}/pay` | Process payment |

### Beds

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/beds` | List all beds |
| POST | `/api/v1/beds` | Create bed |
| GET | `/api/v1/beds/{id}` | Get bed details |
| PUT | `/api/v1/beds/{id}` | Update bed |
| DELETE | `/api/v1/beds/{id}` | Delete bed |
| GET | `/api/v1/beds/available` | Get available beds |
| GET | `/api/v1/beds/dropdown` | Get beds for dropdown |
| GET | `/api/v1/beds/statistics` | Get bed statistics |

### Blood Bank

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/blood-bank` | List blood inventory |
| POST | `/api/v1/blood-bank` | Add blood record |
| GET | `/api/v1/blood-bank/{id}` | Get record details |
| PUT | `/api/v1/blood-bank/{id}` | Update record |
| DELETE | `/api/v1/blood-bank/{id}` | Delete record |
| GET | `/api/v1/blood-bank/statistics` | Get blood bank statistics |
| POST | `/api/v1/blood-bank/{id}/add-bags` | Add blood bags |
| POST | `/api/v1/blood-bank/{id}/remove-bags` | Remove blood bags |

### Pharmacy / Medicines

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/medicines` | List all medicines |
| POST | `/api/v1/medicines` | Create medicine |
| GET | `/api/v1/medicines/{id}` | Get medicine details |
| PUT | `/api/v1/medicines/{id}` | Update medicine |
| DELETE | `/api/v1/medicines/{id}` | Delete medicine |
| GET | `/api/v1/medicines/low-stock` | Get low stock medicines |
| GET | `/api/v1/medicines/dropdown` | Get medicines for dropdown |
| GET | `/api/v1/medicines/statistics` | Get pharmacy statistics |
| POST | `/api/v1/medicines/{id}/update-stock` | Update stock |

### Pathology

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pathology` | List pathology tests |
| POST | `/api/v1/pathology` | Create test |
| GET | `/api/v1/pathology/{id}` | Get test details |
| PUT | `/api/v1/pathology/{id}` | Update test |
| DELETE | `/api/v1/pathology/{id}` | Delete test |
| GET | `/api/v1/pathology/statistics` | Get pathology statistics |

### Radiology

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/radiology` | List radiology tests |
| POST | `/api/v1/radiology` | Create test |
| GET | `/api/v1/radiology/{id}` | Get test details |
| PUT | `/api/v1/radiology/{id}` | Update test |
| DELETE | `/api/v1/radiology/{id}` | Delete test |
| GET | `/api/v1/radiology/statistics` | Get radiology statistics |

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List all users |
| POST | `/api/v1/users` | Create user |
| GET | `/api/v1/users/{id}` | Get user details |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Delete user |
| GET | `/api/v1/users/statistics` | Get user statistics |
| POST | `/api/v1/users/{id}/activate` | Activate user |
| POST | `/api/v1/users/{id}/deactivate` | Deactivate user |

## Query Parameters

### Pagination

All list endpoints support pagination:

```
GET /api/v1/patients?page=1&per_page=15
```

### Filtering

Filter results by field values:

```
GET /api/v1/patients?status=active&gender=male
```

### Searching

Search across searchable fields:

```
GET /api/v1/patients?search=john
```

### Sorting

Sort results by field:

```
GET /api/v1/patients?sort_by=created_at&sort_order=desc
```

### Date Range

Filter by date range:

```
GET /api/v1/appointments?date_from=2024-01-01&date_to=2024-12-31
```

## Response Format

### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "message": "Success",
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 10,
        "last_page_url": "...",
        "next_page_url": "...",
        "path": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 150
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Error details"]
    }
}
```

### Validation Error

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

## Swagger Documentation

Interactive API documentation is available at:

```
/api/documentation
```

This provides a Swagger UI interface where you can:
- Browse all available endpoints
- View request/response schemas
- Test API calls directly
- Download OpenAPI specification

## Rate Limiting

API requests are rate-limited to prevent abuse:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated endpoints

## Examples

### Create a Patient

```bash
curl -X POST /api/v1/patients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "1234567890",
    "gender": "male",
    "dob": "1990-01-15",
    "blood_group": "O+",
    "address": "123 Main St"
  }'
```

### Book an Appointment

```bash
curl -X POST /api/v1/appointments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "doctor_id": 1,
    "department_id": 1,
    "opd_date": "2024-12-20",
    "problem": "General checkup"
  }'
```

### Process a Payment

```bash
curl -X POST /api/v1/bills/1/pay \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500.00,
    "payment_mode": "cash"
  }'
```
