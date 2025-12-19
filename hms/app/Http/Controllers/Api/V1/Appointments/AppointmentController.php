<?php

namespace App\Http\Controllers\Api\V1\Appointments;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Appointments\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Appointment Controller
 *
 * Handles HTTP requests for appointment management.
 */
class AppointmentController extends ApiController
{
    protected AppointmentService $service;

    public function __construct(AppointmentService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/appointments",
     *     operationId="getAppointments",
     *     tags={"Appointments"},
     *     summary="Get list of appointments",
     *     description="Returns paginated list of appointments with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="doctor_id", in="query", description="Filter by doctor", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="patient_id", in="query", description="Filter by patient", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", description="From date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="To date (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", description="Status (0=pending, 1=completed, 2=cancelled)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'doctor_id', 'patient_id', 'department_id',
                'from_date', 'to_date', 'status', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Appointments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve appointments: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/appointments",
     *     operationId="createAppointment",
     *     tags={"Appointments"},
     *     summary="Create a new appointment",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id", "doctor_id", "opd_date"},
     *             @OA\Property(property="patient_id", type="integer"),
     *             @OA\Property(property="doctor_id", type="integer"),
     *             @OA\Property(property="department_id", type="integer"),
     *             @OA\Property(property="opd_date", type="string", format="date-time"),
     *             @OA\Property(property="problem", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Appointment created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:doctors,id',
                'department_id' => 'nullable|exists:doctor_departments,id',
                'opd_date' => 'required|date',
                'problem' => 'nullable|string',
            ]);

            $appointment = $this->service->createAppointment($validated);

            return $this->created($appointment, 'Appointment created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/appointments/{id}",
     *     operationId="getAppointment",
     *     tags={"Appointments"},
     *     summary="Get appointment details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $appointment = $this->service->findOrFail($id);

            return $this->success($appointment, 'Appointment retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/appointments/{id}",
     *     operationId="updateAppointment",
     *     tags={"Appointments"},
     *     summary="Update appointment",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="opd_date", type="string", format="date-time"),
     *         @OA\Property(property="problem", type="string")
     *     )),
     *     @OA\Response(response=200, description="Appointment updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'sometimes|exists:doctors,id',
                'department_id' => 'nullable|exists:doctor_departments,id',
                'opd_date' => 'sometimes|date',
                'problem' => 'nullable|string',
            ]);

            $appointment = $this->service->updateAppointment($id, $validated);

            return $this->success($appointment, 'Appointment updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/appointments/{id}",
     *     operationId="deleteAppointment",
     *     tags={"Appointments"},
     *     summary="Delete appointment",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Appointment deleted"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Appointment deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/appointments/{id}/cancel",
     *     operationId="cancelAppointment",
     *     tags={"Appointments"},
     *     summary="Cancel appointment",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Appointment cancelled"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $appointment = $this->service->cancelAppointment($id);

            return $this->success($appointment, 'Appointment cancelled successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to cancel appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/appointments/{id}/complete",
     *     operationId="completeAppointment",
     *     tags={"Appointments"},
     *     summary="Mark appointment as completed",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Appointment completed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $appointment = $this->service->completeAppointment($id);

            return $this->success($appointment, 'Appointment marked as completed');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to complete appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/appointments/{id}/reschedule",
     *     operationId="rescheduleAppointment",
     *     tags={"Appointments"},
     *     summary="Reschedule appointment",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"new_date"},
     *         @OA\Property(property="new_date", type="string", format="date-time")
     *     )),
     *     @OA\Response(response=200, description="Appointment rescheduled"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_date' => 'required|date',
            ]);

            $appointment = $this->service->rescheduleAppointment($id, $validated['new_date']);

            return $this->success($appointment, 'Appointment rescheduled successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Appointment not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to reschedule appointment: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/appointments/today",
     *     operationId="getTodayAppointments",
     *     tags={"Appointments"},
     *     summary="Get today's appointments",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function today(): JsonResponse
    {
        try {
            $appointments = $this->service->getTodayAppointments();

            return $this->collection($appointments, 'Today\'s appointments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve today\'s appointments: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/appointments/upcoming",
     *     operationId="getUpcomingAppointments",
     *     tags={"Appointments"},
     *     summary="Get upcoming appointments",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="days", in="query", description="Number of days ahead", @OA\Schema(type="integer", default=7)),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 7);
            $appointments = $this->service->getUpcomingAppointments($days);

            return $this->collection($appointments, 'Upcoming appointments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve upcoming appointments: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctors/{doctorId}/availability",
     *     operationId="checkDoctorAvailability",
     *     tags={"Appointments"},
     *     summary="Check doctor availability",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="doctorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date", in="query", required=true, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function checkAvailability(Request $request, int $doctorId): JsonResponse
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $availability = $this->service->checkDoctorAvailability($doctorId, $date);

            return $this->success($availability, 'Doctor availability retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to check availability: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/appointments/statistics",
     *     operationId="getAppointmentStatistics",
     *     tags={"Appointments"},
     *     summary="Get appointment statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'Appointment statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
