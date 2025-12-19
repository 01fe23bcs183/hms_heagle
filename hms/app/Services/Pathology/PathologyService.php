<?php

namespace App\Services\Pathology;

use App\Models\PathologyTest;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pathology Service
 *
 * Handles all business logic related to pathology tests.
 *
 * @package App\Services\Pathology
 */
class PathologyService extends BaseService
{
    protected string $modelClass = PathologyTest::class;

    protected array $defaultRelations = [
        'patient.patientUser',
        'doctor.doctorUser',
        'pathologyCategory',
    ];

    protected array $searchableFields = [
        'test_name',
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
    ];

    protected array $filterableFields = [
        'patient_id' => 'patient_id',
        'doctor_id' => 'doctor_id',
        'pathology_category_id' => 'pathology_category_id',
    ];

    /**
     * Get all pathology tests with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['from_date'])) {
            $query->whereDate('created_at', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('created_at', '<=', $params['to_date']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
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
     * Create a new pathology test.
     */
    public function createTest(array $data): PathologyTest
    {
        return DB::transaction(function () use ($data) {
            $test = PathologyTest::create([
                'test_name' => $data['test_name'],
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'pathology_category_id' => $data['pathology_category_id'] ?? null,
                'short_name' => $data['short_name'] ?? null,
                'test_type' => $data['test_type'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'method' => $data['method'] ?? null,
                'report_days' => $data['report_days'] ?? null,
                'charge_category_id' => $data['charge_category_id'] ?? null,
                'standard_charge' => $data['standard_charge'] ?? 0,
            ]);

            return $test->load($this->defaultRelations);
        });
    }

    /**
     * Update pathology test.
     */
    public function updateTest(int|PathologyTest $test, array $data): PathologyTest
    {
        if (is_int($test)) {
            $test = $this->findOrFail($test);
        }

        return DB::transaction(function () use ($test, $data) {
            $test->update($data);

            return $test->fresh($this->defaultRelations);
        });
    }

    /**
     * Get tests by patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get tests by category.
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('pathology_category_id', $categoryId)
            ->get();
    }

    /**
     * Get pathology statistics.
     */
    public function getStatistics(): array
    {
        $total = PathologyTest::count();
        $today = PathologyTest::whereDate('created_at', now())->count();
        $thisMonth = PathologyTest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'this_month' => $thisMonth,
        ];
    }
}
