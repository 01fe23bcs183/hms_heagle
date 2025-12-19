<?php

namespace App\Http\Controllers\Api\V1\Radiology;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Radiology\RadiologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Radiology Controller
 *
 * Handles HTTP requests for radiology test management.
 */
class RadiologyController extends ApiController
{
    protected RadiologyService $service;

    public function __construct(RadiologyService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/radiology",
     *     operationId="getRadiologyTests",
     *     tags={"Radiology"},
     *     summary="Get list of radiology tests",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="doctor_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'patient_id', 'doctor_id', 'radiology_category_id',
                'from_date', 'to_date', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Radiology tests retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve radiology tests: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/radiology",
     *     operationId="createRadiologyTest",
     *     tags={"Radiology"},
     *     summary="Create a new radiology test",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"test_name", "patient_id"},
     *         @OA\Property(property="test_name", type="string"),
     *         @OA\Property(property="patient_id", type="integer"),
     *         @OA\Property(property="doctor_id", type="integer"),
     *         @OA\Property(property="radiology_category_id", type="integer"),
     *         @OA\Property(property="standard_charge", type="number")
     *     )),
     *     @OA\Response(response=201, description="Test created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'test_name' => 'required|string|max:255',
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'nullable|exists:doctors,id',
                'radiology_category_id' => 'nullable|exists:radiology_categories,id',
                'short_name' => 'nullable|string|max:100',
                'test_type' => 'nullable|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'report_days' => 'nullable|integer|min:0',
                'charge_category_id' => 'nullable|integer',
                'standard_charge' => 'nullable|numeric|min:0',
            ]);

            $test = $this->service->createTest($validated);

            return $this->created($test, 'Radiology test created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create radiology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/radiology/{id}",
     *     operationId="getRadiologyTest",
     *     tags={"Radiology"},
     *     summary="Get radiology test details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $test = $this->service->findOrFail($id);

            return $this->success($test, 'Radiology test retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Radiology test not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve radiology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/radiology/{id}",
     *     operationId="updateRadiologyTest",
     *     tags={"Radiology"},
     *     summary="Update radiology test",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="test_name", type="string"),
     *         @OA\Property(property="standard_charge", type="number")
     *     )),
     *     @OA\Response(response=200, description="Test updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'test_name' => 'sometimes|required|string|max:255',
                'doctor_id' => 'nullable|exists:doctors,id',
                'radiology_category_id' => 'nullable|exists:radiology_categories,id',
                'short_name' => 'nullable|string|max:100',
                'test_type' => 'nullable|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'report_days' => 'nullable|integer|min:0',
                'standard_charge' => 'nullable|numeric|min:0',
            ]);

            $test = $this->service->updateTest($id, $validated);

            return $this->success($test, 'Radiology test updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Radiology test not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update radiology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/radiology/{id}",
     *     operationId="deleteRadiologyTest",
     *     tags={"Radiology"},
     *     summary="Delete radiology test",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Test deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Radiology test deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Radiology test not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete radiology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/radiology/statistics",
     *     operationId="getRadiologyStatistics",
     *     tags={"Radiology"},
     *     summary="Get radiology statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Radiology statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
