<?php

namespace App\Http\Controllers\Api\V1\IPD;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\IPD\IpdAdmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * IPD Controller
 *
 * Handles HTTP requests for IPD (Inpatient Department) management.
 */
class IpdController extends ApiController
{
    protected IpdAdmissionService $service;

    public function __construct(IpdAdmissionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/ipd",
     *     operationId="getIpdAdmissions",
     *     tags={"IPD"},
     *     summary="Get list of IPD admissions",
     *     description="Returns paginated list of IPD admissions with optional filters",
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
                'search', 'doctor_id', 'patient_id', 'bed_id', 'status',
                'from_date', 'to_date', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'IPD admissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve IPD admissions: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/ipd",
     *     operationId="createIpdAdmission",
     *     tags={"IPD"},
     *     summary="Create a new IPD admission",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id", "doctor_id", "bed_id"},
     *             @OA\Property(property="patient_id", type="integer"),
     *             @OA\Property(property="doctor_id", type="integer"),
     *             @OA\Property(property="bed_id", type="integer"),
     *             @OA\Property(property="case_id", type="integer"),
     *             @OA\Property(property="admission_date", type="string", format="date-time"),
     *             @OA\Property(property="height", type="string"),
     *             @OA\Property(property="weight", type="string"),
     *             @OA\Property(property="bp", type="string"),
     *             @OA\Property(property="symptoms", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="IPD admission created"),
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
                'bed_id' => 'required|exists:beds,id',
                'case_id' => 'nullable|exists:patient_cases,id',
                'admission_date' => 'nullable|date',
                'height' => 'nullable|string|max:10',
                'weight' => 'nullable|string|max:10',
                'bp' => 'nullable|string|max:20',
                'symptoms' => 'nullable|string',
                'notes' => 'nullable|string',
                'is_old_patient' => 'nullable|boolean',
            ]);

            $admission = $this->service->createAdmission($validated);

            return $this->created($admission, 'IPD admission created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create IPD admission: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/ipd/{id}",
     *     operationId="getIpdAdmission",
     *     tags={"IPD"},
     *     summary="Get IPD admission details",
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
            $admission = $this->service->getFullDetails($id);

            return $this->success($admission, 'IPD admission retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('IPD admission not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve IPD admission: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/ipd/{id}",
     *     operationId="updateIpdAdmission",
     *     tags={"IPD"},
     *     summary="Update IPD admission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="doctor_id", type="integer"),
     *         @OA\Property(property="bed_id", type="integer"),
     *         @OA\Property(property="symptoms", type="string"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="IPD admission updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'sometimes|exists:doctors,id',
                'bed_id' => 'sometimes|exists:beds,id',
                'height' => 'nullable|string|max:10',
                'weight' => 'nullable|string|max:10',
                'bp' => 'nullable|string|max:20',
                'symptoms' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $admission = $this->service->updateAdmission($id, $validated);

            return $this->success($admission, 'IPD admission updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('IPD admission not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update IPD admission: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/ipd/{id}",
     *     operationId="deleteIpdAdmission",
     *     tags={"IPD"},
     *     summary="Delete IPD admission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="IPD admission deleted"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('IPD admission deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('IPD admission not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete IPD admission: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/ipd/{id}/discharge",
     *     operationId="dischargeIpdPatient",
     *     tags={"IPD"},
     *     summary="Discharge IPD patient",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="discharge_date", type="string", format="date-time"),
     *         @OA\Property(property="bill_status", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Patient discharged"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function discharge(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->only(['discharge_date', 'bill_status']);
            $admission = $this->service->dischargePatient($id, $data);

            return $this->success($admission, 'Patient discharged successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('IPD admission not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to discharge patient: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/ipd/current",
     *     operationId="getCurrentIpdAdmissions",
     *     tags={"IPD"},
     *     summary="Get current IPD admissions (not discharged)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function current(): JsonResponse
    {
        try {
            $admissions = $this->service->getCurrentAdmissions();

            return $this->collection($admissions, 'Current IPD admissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve current admissions: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/ipd/statistics",
     *     operationId="getIpdStatistics",
     *     tags={"IPD"},
     *     summary="Get IPD statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'IPD statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
