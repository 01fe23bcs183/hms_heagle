<?php

namespace App\Services\IPD;

use App\Models\IpdPatientDepartment;
use App\Services\Core\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * IPD Admission Service
 *
 * Handles all business logic related to IPD (Inpatient Department) admissions.
 *
 * @package App\Services\IPD
 */
class IpdAdmissionService extends BaseService
{
    protected string $modelClass = IpdPatientDepartment::class;

    protected array $defaultRelations = [
        'patient.patientUser',
        'doctor.doctorUser',
        'bed',
        'bed.bedType',
    ];

    protected array $searchableFields = [
        'ipd_number',
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
    ];

    protected array $filterableFields = [
        'doctor_id' => 'doctor_id',
        'patient_id' => 'patient_id',
        'bed_id' => 'bed_id',
        'status' => 'status',
    ];

    /**
     * Get all IPD admissions with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['from_date'])) {
            $query->whereDate('admission_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('admission_date', '<=', $params['to_date']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ipd_number', 'like', "%{$search}%")
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
     * Create a new IPD admission.
     */
    public function createAdmission(array $data): IpdPatientDepartment
    {
        return DB::transaction(function () use ($data) {
            // Generate IPD number
            $ipdNumber = $this->generateIpdNumber();

            $admission = IpdPatientDepartment::create([
                'ipd_number' => $ipdNumber,
                'patient_id' => $data['patient_id'],
                'case_id' => $data['case_id'] ?? null,
                'doctor_id' => $data['doctor_id'],
                'bed_id' => $data['bed_id'],
                'admission_date' => $data['admission_date'] ?? now(),
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'bp' => $data['bp'] ?? null,
                'symptoms' => $data['symptoms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_old_patient' => $data['is_old_patient'] ?? 0,
            ]);

            // Mark bed as occupied
            if (!empty($data['bed_id'])) {
                $this->markBedOccupied($data['bed_id']);
            }

            return $admission->load($this->defaultRelations);
        });
    }

    /**
     * Update IPD admission.
     */
    public function updateAdmission(int|IpdPatientDepartment $admission, array $data): IpdPatientDepartment
    {
        if (is_int($admission)) {
            $admission = $this->findOrFail($admission);
        }

        return DB::transaction(function () use ($admission, $data) {
            // Handle bed change
            if (!empty($data['bed_id']) && $data['bed_id'] != $admission->bed_id) {
                // Free old bed
                if ($admission->bed_id) {
                    $this->markBedAvailable($admission->bed_id);
                }
                // Occupy new bed
                $this->markBedOccupied($data['bed_id']);
            }

            $admission->update($data);

            return $admission->fresh($this->defaultRelations);
        });
    }

    /**
     * Discharge patient.
     */
    public function dischargePatient(int|IpdPatientDepartment $admission, array $data = []): IpdPatientDepartment
    {
        if (is_int($admission)) {
            $admission = $this->findOrFail($admission);
        }

        return DB::transaction(function () use ($admission, $data) {
            $admission->update([
                'discharge_date' => $data['discharge_date'] ?? now(),
                'bill_status' => $data['bill_status'] ?? 1,
            ]);

            // Free the bed
            if ($admission->bed_id) {
                $this->markBedAvailable($admission->bed_id);
            }

            return $admission->fresh($this->defaultRelations);
        });
    }

    /**
     * Get admission with full details.
     */
    public function getFullDetails(int $id): IpdPatientDepartment
    {
        return $this->findOrFail($id, [
            'patient.patientUser',
            'doctor.doctorUser',
            'bed',
            'bed.bedType',
            'prescriptions',
            'diagnoses',
            'charges',
            'payments',
            'timelines',
        ]);
    }

    /**
     * Get current admissions (not discharged).
     */
    public function getCurrentAdmissions(): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->whereNull('discharge_date')
            ->orderBy('admission_date', 'desc')
            ->get();
    }

    /**
     * Get admissions by doctor.
     */
    public function getByDoctor(int $doctorId, array $params = [])
    {
        $query = $this->query()
            ->with($this->defaultRelations)
            ->where('doctor_id', $doctorId);

        if (!empty($params['current_only'])) {
            $query->whereNull('discharge_date');
        }

        return $query->orderBy('admission_date', 'desc')->get();
    }

    /**
     * Get admissions by patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('patient_id', $patientId)
            ->orderBy('admission_date', 'desc')
            ->get();
    }

    /**
     * Get IPD statistics.
     */
    public function getStatistics(): array
    {
        $total = IpdPatientDepartment::count();
        $current = IpdPatientDepartment::whereNull('discharge_date')->count();
        $discharged = IpdPatientDepartment::whereNotNull('discharge_date')->count();
        $today = IpdPatientDepartment::whereDate('admission_date', Carbon::today())->count();

        return [
            'total' => $total,
            'current' => $current,
            'discharged' => $discharged,
            'admitted_today' => $today,
        ];
    }

    /**
     * Generate unique IPD number.
     */
    protected function generateIpdNumber(): string
    {
        $prefix = 'IPD';
        $year = date('Y');
        $lastRecord = IpdPatientDepartment::where('ipd_number', 'like', "{$prefix}{$year}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->ipd_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mark bed as occupied.
     */
    protected function markBedOccupied(int $bedId): void
    {
        \App\Models\Bed::where('id', $bedId)->update(['is_available' => 0]);
    }

    /**
     * Mark bed as available.
     */
    protected function markBedAvailable(int $bedId): void
    {
        \App\Models\Bed::where('id', $bedId)->update(['is_available' => 1]);
    }
}
