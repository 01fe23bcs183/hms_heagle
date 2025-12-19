<?php

namespace App\Services\Billing;

use App\Models\Bill;
use App\Services\Core\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bill Service
 *
 * Handles all business logic related to billing management.
 *
 * @package App\Services\Billing
 */
class BillService extends BaseService
{
    protected string $modelClass = Bill::class;

    protected array $defaultRelations = [
        'patient.patientUser',
    ];

    protected array $searchableFields = [
        'bill_id',
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
    ];

    protected array $filterableFields = [
        'patient_id' => 'patient_id',
        'status' => 'status',
    ];

    /**
     * Get all bills with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['from_date'])) {
            $query->whereDate('bill_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('bill_date', '<=', $params['to_date']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('bill_id', 'like', "%{$search}%")
                    ->orWhereHas('patient.patientUser', function ($subQ) use ($search) {
                        $subQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = $params['paginate'] ?? true;
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Create a new bill.
     */
    public function createBill(array $data): Bill
    {
        return DB::transaction(function () use ($data) {
            // Generate bill ID
            $billId = $this->generateBillId();

            $bill = Bill::create([
                'bill_id' => $billId,
                'patient_id' => $data['patient_id'],
                'bill_date' => $data['bill_date'] ?? now(),
                'amount' => $data['amount'],
                'status' => $data['status'] ?? 0, // 0 = unpaid
            ]);

            // Create bill items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $bill->billItems()->create([
                        'item_name' => $item['item_name'],
                        'qty' => $item['qty'] ?? 1,
                        'price' => $item['price'],
                    ]);
                }
            }

            return $bill->load($this->defaultRelations);
        });
    }

    /**
     * Update bill.
     */
    public function updateBill(int|Bill $bill, array $data): Bill
    {
        if (is_int($bill)) {
            $bill = $this->findOrFail($bill);
        }

        return DB::transaction(function () use ($bill, $data) {
            $bill->update($data);

            return $bill->fresh($this->defaultRelations);
        });
    }

    /**
     * Mark bill as paid.
     */
    public function markAsPaid(int|Bill $bill, array $paymentData = []): Bill
    {
        if (is_int($bill)) {
            $bill = $this->findOrFail($bill);
        }

        return DB::transaction(function () use ($bill, $paymentData) {
            $bill->update([
                'status' => 1, // 1 = paid
            ]);

            return $bill->fresh($this->defaultRelations);
        });
    }

    /**
     * Get bill with full details.
     */
    public function getFullDetails(int $id): Bill
    {
        return $this->findOrFail($id, [
            'patient.patientUser',
            'billItems',
        ]);
    }

    /**
     * Get bills by patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('patient_id', $patientId)
            ->orderBy('bill_date', 'desc')
            ->get();
    }

    /**
     * Get unpaid bills.
     */
    public function getUnpaidBills(): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('status', 0)
            ->orderBy('bill_date', 'desc')
            ->get();
    }

    /**
     * Get billing statistics.
     */
    public function getStatistics(): array
    {
        $total = Bill::count();
        $paid = Bill::where('status', 1)->count();
        $unpaid = Bill::where('status', 0)->count();
        $totalAmount = Bill::sum('amount');
        $paidAmount = Bill::where('status', 1)->sum('amount');
        $unpaidAmount = Bill::where('status', 0)->sum('amount');

        return [
            'total_bills' => $total,
            'paid_bills' => $paid,
            'unpaid_bills' => $unpaid,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
        ];
    }

    /**
     * Generate unique bill ID.
     */
    protected function generateBillId(): string
    {
        $prefix = 'BILL';
        $year = date('Y');
        $lastRecord = Bill::where('bill_id', 'like', "{$prefix}{$year}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->bill_id, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
