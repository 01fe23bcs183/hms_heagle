<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Billing\BillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Bill Controller
 *
 * Handles HTTP requests for billing management.
 */
class BillController extends ApiController
{
    protected BillService $service;

    public function __construct(BillService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/bills",
     *     operationId="getBills",
     *     tags={"Billing"},
     *     summary="Get list of bills",
     *     description="Returns paginated list of bills with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="patient_id", in="query", description="Filter by patient", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (0=unpaid, 1=paid)", @OA\Schema(type="integer")),
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
                'search', 'patient_id', 'status',
                'from_date', 'to_date', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Bills retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve bills: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/bills",
     *     operationId="createBill",
     *     tags={"Billing"},
     *     summary="Create a new bill",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id", "amount"},
     *             @OA\Property(property="patient_id", type="integer"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="bill_date", type="string", format="date"),
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 @OA\Property(property="item_name", type="string"),
     *                 @OA\Property(property="qty", type="integer"),
     *                 @OA\Property(property="price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Bill created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'amount' => 'required|numeric|min:0',
                'bill_date' => 'nullable|date',
                'status' => 'nullable|integer|in:0,1',
                'items' => 'nullable|array',
                'items.*.item_name' => 'required_with:items|string',
                'items.*.qty' => 'nullable|integer|min:1',
                'items.*.price' => 'required_with:items|numeric|min:0',
            ]);

            $bill = $this->service->createBill($validated);

            return $this->created($bill, 'Bill created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create bill: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/bills/{id}",
     *     operationId="getBill",
     *     tags={"Billing"},
     *     summary="Get bill details",
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
            $bill = $this->service->getFullDetails($id);

            return $this->success($bill, 'Bill retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bill not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve bill: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/bills/{id}",
     *     operationId="updateBill",
     *     tags={"Billing"},
     *     summary="Update bill",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="amount", type="number"),
     *         @OA\Property(property="status", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Bill updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0',
                'status' => 'nullable|integer|in:0,1',
            ]);

            $bill = $this->service->updateBill($id, $validated);

            return $this->success($bill, 'Bill updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bill not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update bill: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/bills/{id}",
     *     operationId="deleteBill",
     *     tags={"Billing"},
     *     summary="Delete bill",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Bill deleted"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Bill deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bill not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete bill: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/bills/{id}/pay",
     *     operationId="payBill",
     *     tags={"Billing"},
     *     summary="Mark bill as paid",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Bill marked as paid"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        try {
            $bill = $this->service->markAsPaid($id, $request->all());

            return $this->success($bill, 'Bill marked as paid successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Bill not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to mark bill as paid: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/bills/unpaid",
     *     operationId="getUnpaidBills",
     *     tags={"Billing"},
     *     summary="Get unpaid bills",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function unpaid(): JsonResponse
    {
        try {
            $bills = $this->service->getUnpaidBills();

            return $this->collection($bills, 'Unpaid bills retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve unpaid bills: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/bills/statistics",
     *     operationId="getBillingStatistics",
     *     tags={"Billing"},
     *     summary="Get billing statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Billing statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
