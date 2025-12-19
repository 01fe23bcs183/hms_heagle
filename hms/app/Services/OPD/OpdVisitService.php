<?php

namespace App\Services\OPD;

use App\Models\OpdPatientDepartment;
use App\Services\Core\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * OPD Visit Service
 *
 * Handles all business logic related to OPD (Outpatient Department) visits.
 *
 * @package App\Services\OPD
 */
class OpdVisitService extends BaseService
{
    protected string $modelClass = OpdPatientDepartment::class;

    protected array $defaultRelations = [
        'patient.patientUser',
        'doctor.doctorUser',
    ];

    protected array $searchableFields = [
        'opd_number',
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
    ];

    protected array $filterableFields = [
        'doctor_id' => 'doctor_id',
        'patient_id' => 'patient_id',
        'is_old_patient' => 'is_old_patient',
    ];

    /**
     * Get all OPD visits with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['from_date'])) {
            $query->whereDate('appointment_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('appointment_date', '<=', $params['to_date']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('opd_number', 'like', "%{$search}%")
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
     * Create a new OPD visit.
     */
    public function createVisit(array $data): OpdPatientDepartment
    {
        return DB::transaction(function () use ($data) {
            // Generate OPD number
            $opdNumber = $this->generateOpdNumber();

            $visit = OpdPatientDepartment::create([
                'opd_number' => $opdNumber,
                'patient_id' => $data['patient_id'],
                'case_id' => $data['case_id'] ?? null,
                'doctor_id' => $data['doctor_id'],
                'appointment_date' => $data['appointment_date'] ?? now(),
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'bp' => $data['bp'] ?? null,
                'symptoms' => $data['symptoms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_old_patient' => $data['is_old_patient'] ?? 0,
                'standard_charge' => $data['standard_charge'] ?? 0,
                'payment_mode' => $data['payment_mode'] ?? 1,
            ]);

            return $visit->load($this->defaultRelations);
        });
    }

    /**
     * Update OPD visit.
     */
    public function updateVisit(int|OpdPatientDepartment $visit, array $data): OpdPatientDepartment
    {
        if (is_int($visit)) {
            $visit = $this->findOrFail($visit);
        }

        return DB::transaction(function () use ($visit, $data) {
            $visit->update($data);

            return $visit->fresh($this->defaultRelations);
        });
    }

    /**
     * Get visit with full details.
     */
    public function getFullDetails(int $id): OpdPatientDepartment
    {
        return $this->findOrFail($id, [
            'patient.patientUser',
            'doctor.doctorUser',
            'diagnoses',
            'timelines',
        ]);
    }

    /**
     * Get today's OPD visits.
     */
    public function getTodayVisits(): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->whereDate('appointment_date', Carbon::today())
            ->orderBy('appointment_date')
            ->get();
    }

    /**
     * Get visits by doctor.
     */
    public function getByDoctor(int $doctorId, array $params = [])
    {
        $query = $this->query()
            ->with($this->defaultRelations)
            ->where('doctor_id', $doctorId);

        if (!empty($params['from_date'])) {
            $query->whereDate('appointment_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('appointment_date', '<=', $params['to_date']);
        }

        return $query->orderBy('appointment_date', 'desc')->get();
    }

    /**
     * Get visits by patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('patient_id', $patientId)
            ->orderBy('appointment_date', 'desc')
            ->get();
    }

    /**
     * Get OPD statistics.
     */
    public function getStatistics(): array
    {
        $total = OpdPatientDepartment::count();
        $today = OpdPatientDepartment::whereDate('appointment_date', Carbon::today())->count();
        $thisMonth = OpdPatientDepartment::whereMonth('appointment_date', Carbon::now()->month)
            ->whereYear('appointment_date', Carbon::now()->year)
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'this_month' => $thisMonth,
        ];
    }

    /**
     * Generate unique OPD number.
     */
    protected function generateOpdNumber(): string
    {
        $prefix = 'OPD';
        $year = date('Y');
        $lastRecord = OpdPatientDepartment::where('opd_number', 'like', "{$prefix}{$year}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->opd_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
