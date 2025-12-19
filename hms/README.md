
<p><img src="https://track.infyom.com/assets/img/logo-red-black.png"></p>

# Hospital Management System (HMS Heagle)

A comprehensive Hospital Management System built with Laravel, featuring service-oriented architecture, RESTful API endpoints, and OpenAPI/Swagger documentation.

## Demo

**Live Demo:** [https://project-review-app-tunnel-46pyclwb.devinapps.com](https://user:4646dfbd8ce08360e9f74effcf84af50@project-review-app-tunnel-46pyclwb.devinapps.com)

**Admin Login Credentials:**
- Email: `admin@hms.com`
- Password: `123456789`

**API Documentation:** Access Swagger UI at `/api/documentation` after running the application.

## Features

- **Patient Management** - Patient registration, smart cards, cases, admissions
- **Appointment System** - Scheduling, calendar view, status tracking
- **IPD (Inpatient)** - Admissions, bed assignments, prescriptions, billing
- **OPD (Outpatient)** - Visits, diagnoses, prescriptions
- **Doctor Management** - Profiles, schedules, departments, OPD charges
- **Billing & Invoicing** - Bills, invoices, payments, payment gateway integration
- **Bed Management** - Bed types, availability, assignments
- **Blood Bank** - Blood groups, donations, issues
- **Pharmacy** - Medicine catalog, stock management
- **Pathology & Radiology** - Test management, reports
- **User Management** - Role-based access control with Spatie Laravel Permission
- **Live Consultations** - Zoom/Google Meet integration

## Technology Stack

- **Backend:** Laravel 10.x, PHP 8.1+
- **Database:** MySQL 8.0+
- **Frontend:** Blade templates, Livewire, Bootstrap 5
- **API:** RESTful API with OpenAPI/Swagger documentation
- **Authentication:** JWT (JSON Web Tokens)
- **Payment Gateways:** Stripe, PayPal, Razorpay

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/01fe23bcs183/hms_heagle.git
   cd hms_heagle/hms
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

5. **Configure database**
   - Create a MySQL database
   - Update `.env` file with your database credentials:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=hms_heagle
     DB_USERNAME=your_username
     DB_PASSWORD=your_password
     ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Compile frontend assets**
   ```bash
   npm run dev
   ```

8. **Generate Swagger documentation**
   ```bash
   php artisan l5-swagger:generate
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
    - Web Application: http://localhost:8000
    - API Documentation: http://localhost:8000/api/documentation

## Documentation

### Architecture Documentation

- [Service Architecture](docs/architecture/SERVICE_ARCHITECTURE.md) - Detailed explanation of the service-oriented architecture pattern used in this project

### API Documentation

- [API Reference](docs/api/API_DOCUMENTATION.md) - Complete API endpoint documentation with request/response examples
- **Swagger UI** - Interactive API documentation available at `/api/documentation`

### Database Documentation

- [Database Schema](docs/database/DATABASE_DOCUMENTATION.md) - Database structure, relationships, and entity descriptions

## API Overview

The API follows RESTful conventions and is organized into the following modules:

| Module | Endpoint Prefix | Description |
|--------|----------------|-------------|
| Authentication | `/api/v1/auth` | Login, logout, token refresh |
| Patients | `/api/v1/patients` | Patient CRUD operations |
| Appointments | `/api/v1/appointments` | Appointment management |
| Doctors | `/api/v1/doctors` | Doctor profiles and schedules |
| IPD | `/api/v1/ipd` | Inpatient department |
| OPD | `/api/v1/opd` | Outpatient department |
| Billing | `/api/v1/bills` | Bills and invoices |
| Beds | `/api/v1/beds` | Bed management |
| Blood Bank | `/api/v1/blood-bank` | Blood inventory |
| Pharmacy | `/api/v1/medicines` | Medicine catalog |
| Pathology | `/api/v1/pathology` | Pathology tests |
| Radiology | `/api/v1/radiology` | Radiology tests |
| Users | `/api/v1/users` | User management |

### Authentication

All API endpoints (except login) require Bearer token authentication:

```bash
# Login to get token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@hms.com", "password": "123456789"}'

# Use token in subsequent requests
curl http://localhost:8000/api/v1/patients \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Project Structure

```
hms/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/V1/          # API Controllers with Swagger annotations
│   ├── Services/                 # Business logic services
│   │   ├── Core/
│   │   │   └── BaseService.php  # Abstract base service
│   │   ├── Patients/
│   │   ├── Appointments/
│   │   ├── Doctors/
│   │   └── ...
│   └── Traits/
│       └── ApiResponse.php      # Standardized API responses
├── docs/
│   ├── api/                     # API documentation
│   ├── architecture/            # Architecture documentation
│   └── database/                # Database documentation
├── routes/
│   └── api.php                  # API routes
└── storage/
    └── api-docs/                # Generated Swagger JSON
```

## User Roles

The system supports multiple user roles with different permissions:

- **Super Admin** - Full system access
- **Admin** - Administrative functions
- **Doctor** - Patient care, prescriptions, appointments
- **Nurse** - Patient care assistance
- **Pharmacist** - Medicine dispensing
- **Lab Technician** - Pathology/Radiology tests
- **Receptionist** - Appointments, patient registration
- **Accountant** - Billing, payments, reports
- **Patient** - View own records, book appointments

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For support and inquiries, please contact the development team.

---

**Version:** 14.8.2  
**Last Updated:** December 2025

