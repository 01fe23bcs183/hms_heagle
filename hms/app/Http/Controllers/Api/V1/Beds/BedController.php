<?php

namespace App\Http\Controllers\Api\V1\Beds;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Beds\BedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Bed Controller
 *
 * Handles HTTP requests for bed management.
 */
class BedController extends ApiController
{
    protected BedService $service;

    public function __construct(BedService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/beds",
     *     operationId="getBeds",
     *     tags={"Beds"},
     *     summary="Get list of beds",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="bed_type", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_available", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'bed_type', 'is_available', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Beds retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve beds: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/beds",
     *     operationId="createBed",
     *     tags={"Beds"},
     *     summary="Create a new bed",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "bed_type"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="bed_type", type="integer"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="charge", type="number")
     *     )),
     *     @OA\Response(response=201, description="Bed created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'bed_type' => 'required|exists:bed_types,id',
                'description' => 'nullable|string',
                'charge' => 'nullable|numeric|min:0',
                'is_available' => 'nullable|boolean',
            ]);

            $bed = $this->service->createBed($validated);

            return $this->created($bed, 'Bed created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create bed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/beds/{id}",
     *     operationId="getBed",
     *     tags={"Beds"},
     *     summary="Get bed details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $bed = $this->service->findOrFail($id);

            return $this->success($bed, 'Bed retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bed not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve bed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/beds/{id}",
     *     operationId="updateBed",
     *     tags={"Beds"},
     *     summary="Update bed",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="bed_type", type="integer"),
     *         @OA\Property(property="charge", type="number")
     *     )),
     *     @OA\Response(response=200, description="Bed updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'bed_type' => 'sometimes|exists:bed_types,id',
                'description' => 'nullable|string',
                'charge' => 'nullable|numeric|min:0',
                'is_available' => 'nullable|boolean',
            ]);

            $bed = $this->service->updateBed($id, $validated);

            return $this->success($bed, 'Bed updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bed not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update bed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/beds/{id}",
     *     operationId="deleteBed",
     *     tags={"Beds"},
     *     summary="Delete bed",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Bed deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Bed deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bed not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete bed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/beds/available",
     *     operationId="getAvailableBeds",
     *     tags={"Beds"},
     *     summary="Get available beds",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function available(): JsonResponse
    {
        try {
            $beds = $this->service->getAvailableBeds();

            return $this->collection($beds, 'Available beds retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve available beds: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/beds/dropdown",
     *     operationId="getBedsDropdown",
     *     tags={"Beds"},
     *     summary="Get beds for dropdown",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="available_only", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="bed_type", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $result = $this->service->getForDropdown($request->all());

            return $this->collection($result, 'Bed dropdown data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dropdown data: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/beds/statistics",
     *     operationId="getBedStatistics",
     *     tags={"Beds"},
     *     summary="Get bed statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Bed statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
