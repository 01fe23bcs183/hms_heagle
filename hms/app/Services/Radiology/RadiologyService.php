<?php

namespace App\Services\Radiology;

use App\Models\RadiologyTest;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Radiology Service
 *
 * Handles all business logic related to radiology tests.
 *
 * @package App\Services\Radiology
 */
class RadiologyService extends BaseService
{
    protected string $modelClass = RadiologyTest::class;

    protected array $defaultRelations = [
        'patient.patientUser',
        'doctor.doctorUser',
        'radiologyCategory',
    ];

    protected array $searchableFields = [
        'test_name',
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
    ];

    protected array $filterableFields = [
        'patient_id' => 'patient_id',
        'doctor_id' => 'doctor_id',
        'radiology_category_id' => 'radiology_category_id',
    ];

    /**
     * Get all radiology tests with details.
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
     * Create a new radiology test.
     */
    public function createTest(array $data): RadiologyTest
    {
        return DB::transaction(function () use ($data) {
            $test = RadiologyTest::create([
                'test_name' => $data['test_name'],
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'radiology_category_id' => $data['radiology_category_id'] ?? null,
                'short_name' => $data['short_name'] ?? null,
                'test_type' => $data['test_type'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'report_days' => $data['report_days'] ?? null,
                'charge_category_id' => $data['charge_category_id'] ?? null,
                'standard_charge' => $data['standard_charge'] ?? 0,
            ]);

            return $test->load($this->defaultRelations);
        });
    }

    /**
     * Update radiology test.
     */
    public function updateTest(int|RadiologyTest $test, array $data): RadiologyTest
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
            ->where('radiology_category_id', $categoryId)
            ->get();
    }

    /**
     * Get radiology statistics.
     */
    public function getStatistics(): array
    {
        $total = RadiologyTest::count();
        $today = RadiologyTest::whereDate('created_at', now())->count();
        $thisMonth = RadiologyTest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'this_month' => $thisMonth,
        ];
    }
}
