<?php

namespace App\Http\Controllers\Api\V1\Pharmacy;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Pharmacy\MedicineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Medicine Controller
 *
 * Handles HTTP requests for medicine/pharmacy management.
 */
class MedicineController extends ApiController
{
    protected MedicineService $service;

    public function __construct(MedicineService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/medicines",
     *     operationId="getMedicines",
     *     tags={"Pharmacy"},
     *     summary="Get list of medicines",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="brand_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'category_id', 'brand_id', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Medicines retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve medicines: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/medicines",
     *     operationId="createMedicine",
     *     tags={"Pharmacy"},
     *     summary="Create a new medicine",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="category_id", type="integer"),
     *         @OA\Property(property="brand_id", type="integer"),
     *         @OA\Property(property="salt_composition", type="string"),
     *         @OA\Property(property="selling_price", type="number"),
     *         @OA\Property(property="buying_price", type="number"),
     *         @OA\Property(property="quantity", type="integer"),
     *         @OA\Property(property="description", type="string")
     *     )),
     *     @OA\Response(response=201, description="Medicine created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'nullable|exists:medicine_categories,id',
                'brand_id' => 'nullable|exists:medicine_brands,id',
                'salt_composition' => 'nullable|string',
                'selling_price' => 'nullable|numeric|min:0',
                'buying_price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
                'side_effects' => 'nullable|string',
                'description' => 'nullable|string',
            ]);

            $medicine = $this->service->createMedicine($validated);

            return $this->created($medicine, 'Medicine created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create medicine: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/medicines/{id}",
     *     operationId="getMedicine",
     *     tags={"Pharmacy"},
     *     summary="Get medicine details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $medicine = $this->service->findOrFail($id);

            return $this->success($medicine, 'Medicine retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Medicine not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve medicine: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/medicines/{id}",
     *     operationId="updateMedicine",
     *     tags={"Pharmacy"},
     *     summary="Update medicine",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="selling_price", type="number"),
     *         @OA\Property(property="quantity", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Medicine updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'category_id' => 'nullable|exists:medicine_categories,id',
                'brand_id' => 'nullable|exists:medicine_brands,id',
                'salt_composition' => 'nullable|string',
                'selling_price' => 'nullable|numeric|min:0',
                'buying_price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
                'side_effects' => 'nullable|string',
                'description' => 'nullable|string',
            ]);

            $medicine = $this->service->updateMedicine($id, $validated);

            return $this->success($medicine, 'Medicine updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Medicine not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update medicine: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/medicines/{id}",
     *     operationId="deleteMedicine",
     *     tags={"Pharmacy"},
     *     summary="Delete medicine",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Medicine deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Medicine deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Medicine not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete medicine: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/medicines/{id}/update-stock",
     *     operationId="updateMedicineStock",
     *     tags={"Pharmacy"},
     *     summary="Update medicine stock",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"quantity", "operation"},
     *         @OA\Property(property="quantity", type="integer"),
     *         @OA\Property(property="operation", type="string", enum={"add", "subtract"})
     *     )),
     *     @OA\Response(response=200, description="Stock updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'operation' => 'required|string|in:add,subtract',
            ]);

            $medicine = $this->service->updateStock($id, $validated['quantity'], $validated['operation']);

            return $this->success($medicine, 'Stock updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Medicine not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/medicines/low-stock",
     *     operationId="getLowStockMedicines",
     *     tags={"Pharmacy"},
     *     summary="Get low stock medicines",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="threshold", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = $request->input('threshold', 10);
            $medicines = $this->service->getLowStock($threshold);

            return $this->collection($medicines, 'Low stock medicines retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve low stock medicines: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/medicines/dropdown",
     *     operationId="getMedicinesDropdown",
     *     tags={"Pharmacy"},
     *     summary="Get medicines for dropdown",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="in_stock", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $result = $this->service->getForDropdown($request->all());

            return $this->collection($result, 'Medicine dropdown data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dropdown data: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/medicines/statistics",
     *     operationId="getMedicineStatistics",
     *     tags={"Pharmacy"},
     *     summary="Get medicine statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Medicine statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
