<?php

namespace App\Services\BloodBank;

use App\Models\BloodBank;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Blood Bank Service
 *
 * Handles all business logic related to blood bank management.
 *
 * @package App\Services\BloodBank
 */
class BloodBankService extends BaseService
{
    protected string $modelClass = BloodBank::class;

    protected array $defaultRelations = [];

    protected array $searchableFields = [
        'blood_group',
    ];

    protected array $filterableFields = [
        'blood_group' => 'blood_group',
    ];

    /**
     * Get all blood bank records with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query();

        $query = $this->applyFilters($query, $params);

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where('blood_group', 'like', "%{$search}%");
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = $params['paginate'] ?? true;
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Create a new blood bank record.
     */
    public function createRecord(array $data): BloodBank
    {
        return DB::transaction(function () use ($data) {
            $record = BloodBank::create([
                'blood_group' => $data['blood_group'],
                'remained_bags' => $data['remained_bags'] ?? 0,
            ]);

            return $record;
        });
    }

    /**
     * Update blood bank record.
     */
    public function updateRecord(int|BloodBank $record, array $data): BloodBank
    {
        if (is_int($record)) {
            $record = $this->findOrFail($record);
        }

        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            return $record->fresh();
        });
    }

    /**
     * Add blood bags.
     */
    public function addBags(int|BloodBank $record, int $quantity): BloodBank
    {
        if (is_int($record)) {
            $record = $this->findOrFail($record);
        }

        return DB::transaction(function () use ($record, $quantity) {
            $record->increment('remained_bags', $quantity);

            return $record->fresh();
        });
    }

    /**
     * Remove blood bags.
     */
    public function removeBags(int|BloodBank $record, int $quantity): BloodBank
    {
        if (is_int($record)) {
            $record = $this->findOrFail($record);
        }

        if ($record->remained_bags < $quantity) {
            throw new \Exception('Insufficient blood bags available');
        }

        return DB::transaction(function () use ($record, $quantity) {
            $record->decrement('remained_bags', $quantity);

            return $record->fresh();
        });
    }

    /**
     * Get blood availability by group.
     */
    public function getByBloodGroup(string $bloodGroup): ?BloodBank
    {
        return BloodBank::where('blood_group', $bloodGroup)->first();
    }

    /**
     * Get blood bank statistics.
     */
    public function getStatistics(): array
    {
        $records = BloodBank::all();
        $totalBags = $records->sum('remained_bags');

        $byGroup = $records->mapWithKeys(function ($record) {
            return [$record->blood_group => $record->remained_bags];
        });

        return [
            'total_bags' => $totalBags,
            'by_group' => $byGroup,
        ];
    }
}
