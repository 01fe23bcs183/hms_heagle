<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * @OA\Info(
 *     title="HMS Heagle API",
 *     version="1.0.0",
 *     description="Hospital Management System API - A comprehensive RESTful API for managing hospital operations including patients, appointments, IPD/OPD, billing, pharmacy, pathology, radiology, and more.",
 *     @OA\Contact(
 *         email="admin@hms.com",
 *         name="HMS Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="HMS API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Patients",
 *     description="API endpoints for patient management"
 * )
 * @OA\Tag(
 *     name="Appointments",
 *     description="API endpoints for appointment management"
 * )
 * @OA\Tag(
 *     name="Doctors",
 *     description="API endpoints for doctor management"
 * )
 * @OA\Tag(
 *     name="IPD",
 *     description="API endpoints for Inpatient Department management"
 * )
 * @OA\Tag(
 *     name="OPD",
 *     description="API endpoints for Outpatient Department management"
 * )
 * @OA\Tag(
 *     name="Billing",
 *     description="API endpoints for billing and payments"
 * )
 * @OA\Tag(
 *     name="Beds",
 *     description="API endpoints for bed management"
 * )
 * @OA\Tag(
 *     name="Blood Bank",
 *     description="API endpoints for blood bank management"
 * )
 * @OA\Tag(
 *     name="Pharmacy",
 *     description="API endpoints for pharmacy and medicine management"
 * )
 * @OA\Tag(
 *     name="Pathology",
 *     description="API endpoints for pathology tests"
 * )
 * @OA\Tag(
 *     name="Radiology",
 *     description="API endpoints for radiology tests"
 * )
 * @OA\Tag(
 *     name="Inventory",
 *     description="API endpoints for inventory management"
 * )
 * @OA\Tag(
 *     name="Documents",
 *     description="API endpoints for document management"
 * )
 * @OA\Tag(
 *     name="Live Consultations",
 *     description="API endpoints for live consultations"
 * )
 * @OA\Tag(
 *     name="Vaccinations",
 *     description="API endpoints for vaccination management"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="API endpoints for reports and dashboard"
 * )
 * @OA\Tag(
 *     name="Settings",
 *     description="API endpoints for system settings"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="API endpoints for user management"
 * )
 */
class ApiController extends Controller
{
    use ApiResponse;
}
