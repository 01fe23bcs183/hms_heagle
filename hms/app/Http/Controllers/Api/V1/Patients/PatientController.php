<?php

namespace App\Http\Controllers\Api\V1\Patients;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Patients\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Patient Controller
 *
 * Handles HTTP requests for patient management.
 * All business logic is delegated to PatientService.
 */
class PatientController extends ApiController
{
    protected PatientService $service;

    public function __construct(PatientService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/patients",
     *     operationId="getPatients",
     *     tags={"Patients"},
     *     summary="Get list of patients",
     *     description="Returns paginated list of patients with optional search and filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for patient name, email, or phone",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=active, 0=inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Patient")),
     *             @OA\Property(property="message", type="string", example="Patients retrieved successfully"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search',
                'status',
                'paginate',
                'per_page',
                'page',
                'order_by',
                'order',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Patients retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patients: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/patients",
     *     operationId="createPatient",
     *     tags={"Patients"},
     *     summary="Create a new patient",
     *     description="Creates a new patient with user account",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="gender", type="integer", enum={1, 2}, description="1=Male, 2=Female"),
     *             @OA\Property(property="dob", type="string", format="date", example="1990-01-15"),
     *             @OA\Property(property="blood_group", type="string", example="O+")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Patient created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient"),
     *             @OA\Property(property="message", type="string", example="Patient created successfully")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateStoreRequest($request);

            $patient = $this->service->createWithUser($validated);

            return $this->created($patient, 'Patient created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create patient: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}",
     *     operationId="getPatient",
     *     tags={"Patients"},
     *     summary="Get patient details",
     *     description="Returns detailed information about a specific patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient"),
     *             @OA\Property(property="message", type="string", example="Patient retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $patient = $this->service->getFullProfile($id);

            return $this->success($patient, 'Patient retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patient: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/patients/{id}",
     *     operationId="updatePatient",
     *     tags={"Patients"},
     *     summary="Update patient",
     *     description="Updates an existing patient's information",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="gender", type="integer", enum={1, 2}),
     *             @OA\Property(property="dob", type="string", format="date"),
     *             @OA\Property(property="blood_group", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient"),
     *             @OA\Property(property="message", type="string", example="Patient updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validateUpdateRequest($request);

            $patient = $this->service->updateWithUser($id, $validated);

            return $this->success($patient, 'Patient updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update patient: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/patients/{id}",
     *     operationId="deletePatient",
     *     tags={"Patients"},
     *     summary="Delete patient",
     *     description="Deletes a patient record",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Patient deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Patient deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete patient: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/dropdown",
     *     operationId="getPatientsDropdown",
     *     tags={"Patients"},
     *     summary="Get patients for dropdown",
     *     description="Returns a simplified list of patients for dropdown/select components",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $result = $this->service->getForDropdown($request->all());

            return $this->collection($result, 'Patient dropdown data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dropdown data: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}/cases",
     *     operationId="getPatientCases",
     *     tags={"Patients"},
     *     summary="Get patient cases",
     *     description="Returns all cases for a specific patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function cases(int $id): JsonResponse
    {
        try {
            $cases = $this->service->getPatientCases($id);

            return $this->collection($cases, 'Patient cases retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patient cases: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}/appointments",
     *     operationId="getPatientAppointments",
     *     tags={"Patients"},
     *     summary="Get patient appointments",
     *     description="Returns all appointments for a specific patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function appointments(int $id): JsonResponse
    {
        try {
            $appointments = $this->service->getPatientAppointments($id);

            return $this->collection($appointments, 'Patient appointments retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patient appointments: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}/bills",
     *     operationId="getPatientBills",
     *     tags={"Patients"},
     *     summary="Get patient bills",
     *     description="Returns all bills for a specific patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function bills(int $id): JsonResponse
    {
        try {
            $bills = $this->service->getPatientBills($id);

            return $this->collection($bills, 'Patient bills retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patient bills: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}/documents",
     *     operationId="getPatientDocuments",
     *     tags={"Patients"},
     *     summary="Get patient documents",
     *     description="Returns all documents for a specific patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(response=404, description="Patient not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function documents(int $id): JsonResponse
    {
        try {
            $documents = $this->service->getPatientDocuments($id);

            return $this->collection($documents, 'Patient documents retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Patient not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve patient documents: ' . $e->getMessage());
        }
    }

    /**
     * Validate store request.
     */
    protected function validateStoreRequest(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|integer|in:1,2',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|string|max:10',
        ]);
    }

    /**
     * Validate update request.
     */
    protected function validateUpdateRequest(Request $request): array
    {
        return $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|integer|in:1,2',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|string|max:10',
        ]);
    }
}
