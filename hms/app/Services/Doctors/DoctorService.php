<?php

namespace App\Services\Doctors;

use App\Models\Doctor;
use App\Models\User;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Doctor Service
 *
 * Handles all business logic related to doctor management.
 *
 * @package App\Services\Doctors
 */
class DoctorService extends BaseService
{
    protected string $modelClass = Doctor::class;

    protected array $defaultRelations = [
        'doctorUser',
        'department',
    ];

    protected array $searchableFields = [
        'doctorUser.first_name',
        'doctorUser.last_name',
        'doctorUser.email',
        'specialist',
    ];

    protected array $filterableFields = [
        'department_id' => 'doctor_department_id',
        'status' => 'doctorUser.status',
    ];

    /**
     * Get all doctors with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with([
            'doctorUser',
            'department',
            'address',
        ]);

        if (!empty($params['department_id'])) {
            $query->where('doctor_department_id', $params['department_id']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('doctorUser', function ($subQ) use ($search) {
                    $subQ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('specialist', 'like', "%{$search}%");
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
     * Create a new doctor with user account.
     */
    public function createWithUser(array $data): Doctor
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

            // Assign doctor role
            $user->assignRole('Doctor');

            // Create doctor record
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'doctor_department_id' => $data['department_id'] ?? null,
                'specialist' => $data['specialist'] ?? null,
                'qualification' => $data['qualification'] ?? null,
            ]);

            return $doctor->load($this->defaultRelations);
        });
    }

    /**
     * Update doctor and associated user.
     */
    public function updateWithUser(int|Doctor $doctor, array $data): Doctor
    {
        if (is_int($doctor)) {
            $doctor = $this->findOrFail($doctor);
        }

        return DB::transaction(function () use ($doctor, $data) {
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
                $doctor->doctorUser->update($userData);
            }

            // Update doctor data
            $doctorData = array_filter([
                'doctor_department_id' => $data['department_id'] ?? null,
                'specialist' => $data['specialist'] ?? null,
                'qualification' => $data['qualification'] ?? null,
            ]);

            if (!empty($doctorData)) {
                $doctor->update($doctorData);
            }

            return $doctor->fresh($this->defaultRelations);
        });
    }

    /**
     * Get doctor with full profile.
     */
    public function getFullProfile(int $id): Doctor
    {
        return $this->findOrFail($id, [
            'doctorUser',
            'department',
            'address',
            'schedules',
            'appointments',
        ]);
    }

    /**
     * Get doctors for dropdown.
     */
    public function getForDropdown(array $params = []): Collection
    {
        $query = Doctor::with('doctorUser')
            ->whereHas('doctorUser', function ($q) {
                $q->where('status', 1);
            });

        if (!empty($params['department_id'])) {
            $query->where('doctor_department_id', $params['department_id']);
        }

        return $query->get()->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->doctorUser->full_name ?? 
                    ($doctor->doctorUser->first_name . ' ' . $doctor->doctorUser->last_name),
                'specialist' => $doctor->specialist,
            ];
        });
    }

    /**
     * Get doctors by department.
     */
    public function getByDepartment(int $departmentId): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->where('doctor_department_id', $departmentId)
            ->whereHas('doctorUser', function ($q) {
                $q->where('status', 1);
            })
            ->get();
    }

    /**
     * Get doctor schedules.
     */
    public function getSchedules(int $doctorId)
    {
        $doctor = $this->findOrFail($doctorId);
        return $doctor->schedules()->get();
    }

    /**
     * Get doctor appointments.
     */
    public function getAppointments(int $doctorId, array $params = [])
    {
        $doctor = $this->findOrFail($doctorId);
        $query = $doctor->appointments()->with(['patient.patientUser']);

        if (!empty($params['from_date'])) {
            $query->whereDate('opd_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('opd_date', '<=', $params['to_date']);
        }

        return $query->latest('opd_date')->get();
    }

    /**
     * Get doctor statistics.
     */
    public function getStatistics(): array
    {
        $total = Doctor::count();
        $active = Doctor::whereHas('doctorUser', function ($q) {
            $q->where('status', 1);
        })->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }
}
