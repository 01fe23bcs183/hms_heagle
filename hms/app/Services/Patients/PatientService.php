<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\User;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Patient Service
 *
 * Handles all business logic related to patient management.
 *
 * @package App\Services\Patients
 */
class PatientService extends BaseService
{
    protected string $modelClass = Patient::class;

    protected array $defaultRelations = [
        'patientUser',
    ];

    protected array $searchableFields = [
        'patientUser.first_name',
        'patientUser.last_name',
        'patientUser.email',
        'patientUser.phone',
    ];

    protected array $filterableFields = [
        'status' => 'status',
    ];

    /**
     * Get all patients with user details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with([
            'patientUser',
            'address',
        ]);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('patientUser', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
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
     * Create a new patient with associated user account.
     */
    public function createWithUser(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            // Create user account
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'status' => 1,
            ]);

            // Assign patient role
            $user->assignRole('Patient');

            // Create patient record
            $patient = Patient::create([
                'user_id' => $user->id,
            ]);

            return $patient->load('patientUser');
        });
    }

    /**
     * Update patient and associated user.
     */
    public function updateWithUser(int|Patient $patient, array $data): Patient
    {
        if (is_int($patient)) {
            $patient = $this->findOrFail($patient);
        }

        return DB::transaction(function () use ($patient, $data) {
            // Update user data
            $userData = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
            ]);

            if (!empty($userData)) {
                $patient->patientUser->update($userData);
            }

            return $patient->fresh(['patientUser']);
        });
    }

    /**
     * Get patient with full medical history.
     */
    public function getFullProfile(int $id): Patient
    {
        return $this->findOrFail($id, [
            'patientUser',
            'address',
            'cases',
            'appointments',
            'bills',
            'invoices',
            'documents',
            'advancedPayments',
        ]);
    }

    /**
     * Get patients for dropdown.
     */
    public function getForDropdown(array $params = [], array $fields = ['id', 'name']): Collection
    {
        return Patient::with('patientUser')
            ->whereHas('patientUser', function ($q) {
                $q->where('status', 1);
            })
            ->get()
            ->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->patientUser->full_name ?? 
                        ($patient->patientUser->first_name . ' ' . $patient->patientUser->last_name),
                ];
            });
    }

    /**
     * Get active patients count.
     */
    public function getActiveCount(): int
    {
        return Patient::whereHas('patientUser', function ($q) {
            $q->where('status', 1);
        })->count();
    }

    /**
     * Get patient statistics.
     */
    public function getStatistics(): array
    {
        $total = Patient::count();
        $active = $this->getActiveCount();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    /**
     * Search patients.
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Patient::with('patientUser')
            ->whereHas('patientUser', function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get patient cases.
     */
    public function getPatientCases(int $patientId)
    {
        $patient = $this->findOrFail($patientId);
        return $patient->cases()->with(['doctor', 'caseHandler'])->get();
    }

    /**
     * Get patient appointments.
     */
    public function getPatientAppointments(int $patientId)
    {
        $patient = $this->findOrFail($patientId);
        return $patient->appointments()->with(['doctor'])->latest()->get();
    }

    /**
     * Get patient bills.
     */
    public function getPatientBills(int $patientId)
    {
        $patient = $this->findOrFail($patientId);
        return $patient->bills()->latest()->get();
    }

    /**
     * Get patient documents.
     */
    public function getPatientDocuments(int $patientId)
    {
        $patient = $this->findOrFail($patientId);
        return $patient->documents()->with(['documentType'])->latest()->get();
    }
}
