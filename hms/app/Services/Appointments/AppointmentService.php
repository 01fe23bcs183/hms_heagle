<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Services\Core\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Appointment Service
 *
 * Handles all business logic related to appointment management.
 *
 * @package App\Services\Appointments
 */
class AppointmentService extends BaseService
{
    protected string $modelClass = Appointment::class;

    protected array $defaultRelations = [
        'patient.patientUser',
        'doctor.doctorUser',
        'department',
    ];

    protected array $searchableFields = [
        'patient.patientUser.first_name',
        'patient.patientUser.last_name',
        'doctor.doctorUser.first_name',
        'doctor.doctorUser.last_name',
    ];

    protected array $filterableFields = [
        'doctor_id' => 'doctor_id',
        'patient_id' => 'patient_id',
        'department_id' => 'department_id',
        'is_completed' => 'status',
    ];

    /**
     * Get all appointments with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        // Date range filter
        if (!empty($params['from_date'])) {
            $query->whereDate('opd_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('opd_date', '<=', $params['to_date']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient.patientUser', function ($subQ) use ($search) {
                    $subQ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('doctor.doctorUser', function ($subQ) use ($search) {
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
     * Create a new appointment.
     */
    public function createAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $appointment = Appointment::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'department_id' => $data['department_id'] ?? null,
                'opd_date' => $data['opd_date'],
                'problem' => $data['problem'] ?? null,
                'is_completed' => 0,
            ]);

            return $appointment->load($this->defaultRelations);
        });
    }

    /**
     * Update appointment.
     */
    public function updateAppointment(int|Appointment $appointment, array $data): Appointment
    {
        if (is_int($appointment)) {
            $appointment = $this->findOrFail($appointment);
        }

        return DB::transaction(function () use ($appointment, $data) {
            $appointment->update($data);

            return $appointment->fresh($this->defaultRelations);
        });
    }

    /**
     * Cancel appointment.
     */
    public function cancelAppointment(int|Appointment $appointment): Appointment
    {
        if (is_int($appointment)) {
            $appointment = $this->findOrFail($appointment);
        }

        return DB::transaction(function () use ($appointment) {
            $appointment->update(['is_completed' => 2]); // 2 = cancelled

            return $appointment->fresh($this->defaultRelations);
        });
    }

    /**
     * Complete appointment.
     */
    public function completeAppointment(int|Appointment $appointment): Appointment
    {
        if (is_int($appointment)) {
            $appointment = $this->findOrFail($appointment);
        }

        return DB::transaction(function () use ($appointment) {
            $appointment->update(['is_completed' => 1]); // 1 = completed

            return $appointment->fresh($this->defaultRelations);
        });
    }

    /**
     * Reschedule appointment.
     */
    public function rescheduleAppointment(int|Appointment $appointment, string $newDate): Appointment
    {
        if (is_int($appointment)) {
            $appointment = $this->findOrFail($appointment);
        }

        return DB::transaction(function () use ($appointment, $newDate) {
            $appointment->update(['opd_date' => $newDate]);

            return $appointment->fresh($this->defaultRelations);
        });
    }

    /**
     * Get today's appointments.
     */
    public function getTodayAppointments(): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->whereDate('opd_date', Carbon::today())
            ->orderBy('opd_date')
            ->get();
    }

    /**
     * Get upcoming appointments.
     */
    public function getUpcomingAppointments(int $days = 7): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->whereDate('opd_date', '>=', Carbon::today())
            ->whereDate('opd_date', '<=', Carbon::today()->addDays($days))
            ->where('is_completed', 0)
            ->orderBy('opd_date')
            ->get();
    }

    /**
     * Get appointments by doctor.
     */
    public function getByDoctor(int $doctorId, array $params = [])
    {
        $query = $this->query()
            ->with($this->defaultRelations)
            ->where('doctor_id', $doctorId);

        if (!empty($params['from_date'])) {
            $query->whereDate('opd_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('opd_date', '<=', $params['to_date']);
        }

        return $query->orderBy('opd_date', 'desc')->get();
    }

    /**
     * Get appointments by patient.
     */
    public function getByPatient(int $patientId, array $params = [])
    {
        $query = $this->query()
            ->with($this->defaultRelations)
            ->where('patient_id', $patientId);

        if (!empty($params['from_date'])) {
            $query->whereDate('opd_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('opd_date', '<=', $params['to_date']);
        }

        return $query->orderBy('opd_date', 'desc')->get();
    }

    /**
     * Check doctor availability.
     */
    public function checkDoctorAvailability(int $doctorId, string $date): array
    {
        $appointments = $this->query()
            ->where('doctor_id', $doctorId)
            ->whereDate('opd_date', $date)
            ->where('is_completed', 0)
            ->get();

        return [
            'doctor_id' => $doctorId,
            'date' => $date,
            'booked_slots' => $appointments->count(),
            'appointments' => $appointments,
        ];
    }

    /**
     * Get appointment statistics.
     */
    public function getStatistics(): array
    {
        $total = Appointment::count();
        $today = Appointment::whereDate('opd_date', Carbon::today())->count();
        $pending = Appointment::where('is_completed', 0)->count();
        $completed = Appointment::where('is_completed', 1)->count();
        $cancelled = Appointment::where('is_completed', 2)->count();

        return [
            'total' => $total,
            'today' => $today,
            'pending' => $pending,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
    }
}
