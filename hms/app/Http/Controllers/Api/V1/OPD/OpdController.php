<?php

namespace App\Http\Controllers\Api\V1\OPD;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\OPD\OpdVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * OPD Controller
 *
 * Handles HTTP requests for OPD (Outpatient Department) management.
 */
class OpdController extends ApiController
{
    protected OpdVisitService $service;

    public function __construct(OpdVisitService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/opd",
     *     operationId="getOpdVisits",
     *     tags={"OPD"},
     *     summary="Get list of OPD visits",
     *     description="Returns paginated list of OPD visits with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="doctor_id", in="query", description="Filter by doctor", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="patient_id", in="query", description="Filter by patient", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", description="From date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="To date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'doctor_id', 'patient_id', 'is_old_patient',
                'from_date', 'to_date', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'OPD visits retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve OPD visits: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/opd",
     *     operationId="createOpdVisit",
     *     tags={"OPD"},
     *     summary="Create a new OPD visit",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id", "doctor_id"},
     *             @OA\Property(property="patient_id", type="integer"),
     *             @OA\Property(property="doctor_id", type="integer"),
     *             @OA\Property(property="case_id", type="integer"),
     *             @OA\Property(property="appointment_date", type="string", format="date-time"),
     *             @OA\Property(property="height", type="string"),
     *             @OA\Property(property="weight", type="string"),
     *             @OA\Property(property="bp", type="string"),
     *             @OA\Property(property="symptoms", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="standard_charge", type="number")
     *         )
     *     ),
     *     @OA\Response(response=201, description="OPD visit created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:doctors,id',
                'case_id' => 'nullable|exists:patient_cases,id',
                'appointment_date' => 'nullable|date',
                'height' => 'nullable|string|max:10',
                'weight' => 'nullable|string|max:10',
                'bp' => 'nullable|string|max:20',
                'symptoms' => 'nullable|string',
                'notes' => 'nullable|string',
                'is_old_patient' => 'nullable|boolean',
                'standard_charge' => 'nullable|numeric',
                'payment_mode' => 'nullable|integer',
            ]);

            $visit = $this->service->createVisit($validated);

            return $this->created($visit, 'OPD visit created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create OPD visit: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/opd/{id}",
     *     operationId="getOpdVisit",
     *     tags={"OPD"},
     *     summary="Get OPD visit details",
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
            $visit = $this->service->getFullDetails($id);

            return $this->success($visit, 'OPD visit retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('OPD visit not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve OPD visit: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/opd/{id}",
     *     operationId="updateOpdVisit",
     *     tags={"OPD"},
     *     summary="Update OPD visit",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="doctor_id", type="integer"),
     *         @OA\Property(property="symptoms", type="string"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="OPD visit updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'sometimes|exists:doctors,id',
                'height' => 'nullable|string|max:10',
                'weight' => 'nullable|string|max:10',
                'bp' => 'nullable|string|max:20',
                'symptoms' => 'nullable|string',
                'notes' => 'nullable|string',
                'standard_charge' => 'nullable|numeric',
            ]);

            $visit = $this->service->updateVisit($id, $validated);

            return $this->success($visit, 'OPD visit updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('OPD visit not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update OPD visit: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/opd/{id}",
     *     operationId="deleteOpdVisit",
     *     tags={"OPD"},
     *     summary="Delete OPD visit",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OPD visit deleted"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('OPD visit deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('OPD visit not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete OPD visit: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/opd/today",
     *     operationId="getTodayOpdVisits",
     *     tags={"OPD"},
     *     summary="Get today's OPD visits",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function today(): JsonResponse
    {
        try {
            $visits = $this->service->getTodayVisits();

            return $this->collection($visits, 'Today\'s OPD visits retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve today\'s visits: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/opd/statistics",
     *     operationId="getOpdStatistics",
     *     tags={"OPD"},
     *     summary="Get OPD statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'OPD statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
