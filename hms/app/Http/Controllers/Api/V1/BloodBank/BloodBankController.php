<?php

namespace App\Http\Controllers\Api\V1\BloodBank;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\BloodBank\BloodBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Blood Bank Controller
 *
 * Handles HTTP requests for blood bank management.
 */
class BloodBankController extends ApiController
{
    protected BloodBankService $service;

    public function __construct(BloodBankService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/blood-bank",
     *     operationId="getBloodBankRecords",
     *     tags={"Blood Bank"},
     *     summary="Get blood bank records",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="blood_group", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'blood_group', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Blood bank records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve blood bank records: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/blood-bank",
     *     operationId="createBloodBankRecord",
     *     tags={"Blood Bank"},
     *     summary="Create a new blood bank record",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"blood_group"},
     *         @OA\Property(property="blood_group", type="string"),
     *         @OA\Property(property="remained_bags", type="integer")
     *     )),
     *     @OA\Response(response=201, description="Record created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'blood_group' => 'required|string|max:10',
                'remained_bags' => 'nullable|integer|min:0',
            ]);

            $record = $this->service->createRecord($validated);

            return $this->created($record, 'Blood bank record created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create record: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/blood-bank/{id}",
     *     operationId="getBloodBankRecord",
     *     tags={"Blood Bank"},
     *     summary="Get blood bank record details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = $this->service->findOrFail($id);

            return $this->success($record, 'Blood bank record retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Blood bank record not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve record: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/blood-bank/{id}",
     *     operationId="updateBloodBankRecord",
     *     tags={"Blood Bank"},
     *     summary="Update blood bank record",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="blood_group", type="string"),
     *         @OA\Property(property="remained_bags", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Record updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'blood_group' => 'sometimes|required|string|max:10',
                'remained_bags' => 'nullable|integer|min:0',
            ]);

            $record = $this->service->updateRecord($id, $validated);

            return $this->success($record, 'Blood bank record updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Blood bank record not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update record: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/blood-bank/{id}",
     *     operationId="deleteBloodBankRecord",
     *     tags={"Blood Bank"},
     *     summary="Delete blood bank record",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Record deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Blood bank record deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Blood bank record not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete record: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/blood-bank/{id}/add-bags",
     *     operationId="addBloodBags",
     *     tags={"Blood Bank"},
     *     summary="Add blood bags to inventory",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"quantity"},
     *         @OA\Property(property="quantity", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Bags added"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function addBags(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $record = $this->service->addBags($id, $validated['quantity']);

            return $this->success($record, 'Blood bags added successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Blood bank record not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to add bags: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/blood-bank/{id}/remove-bags",
     *     operationId="removeBloodBags",
     *     tags={"Blood Bank"},
     *     summary="Remove blood bags from inventory",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"quantity"},
     *         @OA\Property(property="quantity", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Bags removed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function removeBags(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $record = $this->service->removeBags($id, $validated['quantity']);

            return $this->success($record, 'Blood bags removed successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Blood bank record not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/blood-bank/statistics",
     *     operationId="getBloodBankStatistics",
     *     tags={"Blood Bank"},
     *     summary="Get blood bank statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Blood bank statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
