<?php

namespace App\Http\Controllers\Api\V1\Doctors;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Doctors\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Doctor Controller
 *
 * Handles HTTP requests for doctor management.
 */
class DoctorController extends ApiController
{
    protected DoctorService $service;

    public function __construct(DoctorService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/doctors",
     *     operationId="getDoctors",
     *     tags={"Doctors"},
     *     summary="Get list of doctors",
     *     description="Returns paginated list of doctors with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="department_id", in="query", description="Filter by department", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'department_id', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Doctors retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve doctors: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/doctors",
     *     operationId="createDoctor",
     *     tags={"Doctors"},
     *     summary="Create a new doctor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="department_id", type="integer"),
     *             @OA\Property(property="specialist", type="string"),
     *             @OA\Property(property="qualification", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Doctor created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'gender' => 'nullable|integer|in:1,2',
                'dob' => 'nullable|date',
                'blood_group' => 'nullable|string|max:10',
                'department_id' => 'nullable|exists:doctor_departments,id',
                'specialist' => 'nullable|string|max:255',
                'qualification' => 'nullable|string|max:255',
            ]);

            $doctor = $this->service->createWithUser($validated);

            return $this->created($doctor, 'Doctor created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create doctor: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/{id}",
     *     operationId="getDoctor",
     *     tags={"Doctors"},
     *     summary="Get doctor details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $doctor = $this->service->getFullProfile($id);

            return $this->success($doctor, 'Doctor retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Doctor not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve doctor: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/doctors/{id}",
     *     operationId="updateDoctor",
     *     tags={"Doctors"},
     *     summary="Update doctor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="first_name", type="string"),
     *         @OA\Property(property="last_name", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="department_id", type="integer"),
     *         @OA\Property(property="specialist", type="string"),
     *         @OA\Property(property="qualification", type="string")
     *     )),
     *     @OA\Response(response=200, description="Doctor updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'gender' => 'nullable|integer|in:1,2',
                'dob' => 'nullable|date',
                'blood_group' => 'nullable|string|max:10',
                'department_id' => 'nullable|exists:doctor_departments,id',
                'specialist' => 'nullable|string|max:255',
                'qualification' => 'nullable|string|max:255',
            ]);

            $doctor = $this->service->updateWithUser($id, $validated);

            return $this->success($doctor, 'Doctor updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Doctor not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update doctor: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/doctors/{id}",
     *     operationId="deleteDoctor",
     *     tags={"Doctors"},
     *     summary="Delete doctor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Doctor deleted"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Doctor deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Doctor not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete doctor: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/dropdown",
     *     operationId="getDoctorsDropdown",
     *     tags={"Doctors"},
     *     summary="Get doctors for dropdown",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="department_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $result = $this->service->getForDropdown($request->all());

            return $this->collection($result, 'Doctor dropdown data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dropdown data: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/{id}/schedules",
     *     operationId="getDoctorSchedules",
     *     tags={"Doctors"},
     *     summary="Get doctor schedules",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function schedules(int $id): JsonResponse
    {
        try {
            $schedules = $this->service->getSchedules($id);

            return $this->collection($schedules, 'Doctor schedules retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Doctor not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve schedules: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/{id}/appointments",
     *     operationId="getDoctorAppointments",
     *     tags={"Doctors"},
     *     summary="Get doctor appointments",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function appointments(Request $request, int $id): JsonResponse
    {
        try {
            $params = $request->only(['from_date', 'to_date']);
            $appointments = $this->service->getAppointments($id, $params);

            return $this->collection($appointments, 'Doctor appointments retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Doctor not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve appointments: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/statistics",
     *     operationId="getDoctorStatistics",
     *     tags={"Doctors"},
     *     summary="Get doctor statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Doctor statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
