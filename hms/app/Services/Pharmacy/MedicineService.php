<?php

namespace App\Services\Pharmacy;

use App\Models\Medicine;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Medicine Service
 *
 * Handles all business logic related to medicine/pharmacy management.
 *
 * @package App\Services\Pharmacy
 */
class MedicineService extends BaseService
{
    protected string $modelClass = Medicine::class;

    protected array $defaultRelations = [
        'category',
        'brand',
    ];

    protected array $searchableFields = [
        'name',
        'salt_composition',
    ];

    protected array $filterableFields = [
        'category_id' => 'category_id',
        'brand_id' => 'brand_id',
    ];

    /**
     * Get all medicines with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('salt_composition', 'like', "%{$search}%");
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
     * Create a new medicine.
     */
    public function createMedicine(array $data): Medicine
    {
        return DB::transaction(function () use ($data) {
            $medicine = Medicine::create([
                'name' => $data['name'],
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'salt_composition' => $data['salt_composition'] ?? null,
                'selling_price' => $data['selling_price'] ?? 0,
                'buying_price' => $data['buying_price'] ?? 0,
                'quantity' => $data['quantity'] ?? 0,
                'side_effects' => $data['side_effects'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            return $medicine->load($this->defaultRelations);
        });
    }

    /**
     * Update medicine.
     */
    public function updateMedicine(int|Medicine $medicine, array $data): Medicine
    {
        if (is_int($medicine)) {
            $medicine = $this->findOrFail($medicine);
        }

        return DB::transaction(function () use ($medicine, $data) {
            $medicine->update($data);

            return $medicine->fresh($this->defaultRelations);
        });
    }

    /**
     * Update stock quantity.
     */
    public function updateStock(int|Medicine $medicine, int $quantity, string $operation = 'add'): Medicine
    {
        if (is_int($medicine)) {
            $medicine = $this->findOrFail($medicine);
        }

        return DB::transaction(function () use ($medicine, $quantity, $operation) {
            if ($operation === 'add') {
                $medicine->increment('quantity', $quantity);
            } else {
                if ($medicine->quantity < $quantity) {
                    throw new \Exception('Insufficient stock');
                }
                $medicine->decrement('quantity', $quantity);
            }

            return $medicine->fresh($this->defaultRelations);
        });
    }

    /**
     * Get low stock medicines.
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('quantity', '<=', $threshold)
            ->get();
    }

    /**
     * Get medicines by category.
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('category_id', $categoryId)
            ->get();
    }

    /**
     * Get medicines for dropdown.
     */
    public function getForDropdown(array $params = [], array $fields = ['id', 'name']): Collection
    {
        $query = Medicine::query();

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['in_stock'])) {
            $query->where('quantity', '>', 0);
        }

        return $query->get()->map(function ($medicine) {
            return [
                'id' => $medicine->id,
                'name' => $medicine->name,
                'price' => $medicine->selling_price,
                'quantity' => $medicine->quantity,
            ];
        });
    }

    /**
     * Get medicine statistics.
     */
    public function getStatistics(): array
    {
        $total = Medicine::count();
        $inStock = Medicine::where('quantity', '>', 0)->count();
        $outOfStock = Medicine::where('quantity', '<=', 0)->count();
        $lowStock = Medicine::where('quantity', '>', 0)->where('quantity', '<=', 10)->count();
        $totalValue = Medicine::sum(DB::raw('quantity * buying_price'));

        return [
            'total' => $total,
            'in_stock' => $inStock,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'total_value' => $totalValue,
        ];
    }
}
