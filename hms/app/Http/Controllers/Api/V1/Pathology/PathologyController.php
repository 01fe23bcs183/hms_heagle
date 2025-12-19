<?php

namespace App\Http\Controllers\Api\V1\Pathology;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Pathology\PathologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Pathology Controller
 *
 * Handles HTTP requests for pathology test management.
 */
class PathologyController extends ApiController
{
    protected PathologyService $service;

    public function __construct(PathologyService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/pathology",
     *     operationId="getPathologyTests",
     *     tags={"Pathology"},
     *     summary="Get list of pathology tests",
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
                'search', 'patient_id', 'doctor_id', 'pathology_category_id',
                'from_date', 'to_date', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Pathology tests retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve pathology tests: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/pathology",
     *     operationId="createPathologyTest",
     *     tags={"Pathology"},
     *     summary="Create a new pathology test",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"test_name", "patient_id"},
     *         @OA\Property(property="test_name", type="string"),
     *         @OA\Property(property="patient_id", type="integer"),
     *         @OA\Property(property="doctor_id", type="integer"),
     *         @OA\Property(property="pathology_category_id", type="integer"),
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
                'pathology_category_id' => 'nullable|exists:pathology_categories,id',
                'short_name' => 'nullable|string|max:100',
                'test_type' => 'nullable|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'method' => 'nullable|string|max:100',
                'report_days' => 'nullable|integer|min:0',
                'charge_category_id' => 'nullable|integer',
                'standard_charge' => 'nullable|numeric|min:0',
            ]);

            $test = $this->service->createTest($validated);

            return $this->created($test, 'Pathology test created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create pathology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/pathology/{id}",
     *     operationId="getPathologyTest",
     *     tags={"Pathology"},
     *     summary="Get pathology test details",
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

            return $this->success($test, 'Pathology test retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Pathology test not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve pathology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/pathology/{id}",
     *     operationId="updatePathologyTest",
     *     tags={"Pathology"},
     *     summary="Update pathology test",
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
                'pathology_category_id' => 'nullable|exists:pathology_categories,id',
                'short_name' => 'nullable|string|max:100',
                'test_type' => 'nullable|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'method' => 'nullable|string|max:100',
                'report_days' => 'nullable|integer|min:0',
                'standard_charge' => 'nullable|numeric|min:0',
            ]);

            $test = $this->service->updateTest($id, $validated);

            return $this->success($test, 'Pathology test updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Pathology test not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update pathology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/pathology/{id}",
     *     operationId="deletePathologyTest",
     *     tags={"Pathology"},
     *     summary="Delete pathology test",
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

            return $this->noContent('Pathology test deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Pathology test not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete pathology test: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/pathology/statistics",
     *     operationId="getPathologyStatistics",
     *     tags={"Pathology"},
     *     summary="Get pathology statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Pathology statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
