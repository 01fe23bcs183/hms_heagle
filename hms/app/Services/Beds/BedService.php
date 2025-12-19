<?php

namespace App\Services\Beds;

use App\Models\Bed;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bed Service
 *
 * Handles all business logic related to bed management.
 *
 * @package App\Services\Beds
 */
class BedService extends BaseService
{
    protected string $modelClass = Bed::class;

    protected array $defaultRelations = [
        'bedType',
    ];

    protected array $searchableFields = [
        'name',
        'bedType.title',
    ];

    protected array $filterableFields = [
        'bed_type' => 'bed_type',
        'is_available' => 'is_available',
    ];

    /**
     * Get all beds with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('bedType', function ($subQ) use ($search) {
                        $subQ->where('title', 'like', "%{$search}%");
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
     * Create a new bed.
     */
    public function createBed(array $data): Bed
    {
        return DB::transaction(function () use ($data) {
            $bed = Bed::create([
                'name' => $data['name'],
                'bed_type' => $data['bed_type'],
                'description' => $data['description'] ?? null,
                'charge' => $data['charge'] ?? 0,
                'is_available' => $data['is_available'] ?? 1,
            ]);

            return $bed->load($this->defaultRelations);
        });
    }

    /**
     * Update bed.
     */
    public function updateBed(int|Bed $bed, array $data): Bed
    {
        if (is_int($bed)) {
            $bed = $this->findOrFail($bed);
        }

        return DB::transaction(function () use ($bed, $data) {
            $bed->update($data);

            return $bed->fresh($this->defaultRelations);
        });
    }

    /**
     * Get available beds.
     */
    public function getAvailableBeds(): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('is_available', 1)
            ->get();
    }

    /**
     * Get beds by type.
     */
    public function getByType(int $bedTypeId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('bed_type', $bedTypeId)
            ->get();
    }

    /**
     * Mark bed as occupied.
     */
    public function markOccupied(int|Bed $bed): Bed
    {
        if (is_int($bed)) {
            $bed = $this->findOrFail($bed);
        }

        $bed->update(['is_available' => 0]);

        return $bed->fresh($this->defaultRelations);
    }

    /**
     * Mark bed as available.
     */
    public function markAvailable(int|Bed $bed): Bed
    {
        if (is_int($bed)) {
            $bed = $this->findOrFail($bed);
        }

        $bed->update(['is_available' => 1]);

        return $bed->fresh($this->defaultRelations);
    }

    /**
     * Get beds for dropdown.
     */
    public function getForDropdown(array $params = []): Collection
    {
        $query = Bed::with('bedType');

        if (!empty($params['available_only'])) {
            $query->where('is_available', 1);
        }

        if (!empty($params['bed_type'])) {
            $query->where('bed_type', $params['bed_type']);
        }

        return $query->get()->map(function ($bed) {
            return [
                'id' => $bed->id,
                'name' => $bed->name,
                'type' => $bed->bedType->title ?? null,
                'is_available' => $bed->is_available,
            ];
        });
    }

    /**
     * Get bed statistics.
     */
    public function getStatistics(): array
    {
        $total = Bed::count();
        $available = Bed::where('is_available', 1)->count();
        $occupied = Bed::where('is_available', 0)->count();

        return [
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0,
        ];
    }
}
