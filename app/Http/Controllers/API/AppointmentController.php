<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $patient = $request->user()->patient;
        if (! $patient) {
            return ApiResponse::error('Only patients can book appointments.', 403);
        }

        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'appointment_time' => ['required', 'date', 'after:now'],
            'Status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'rescheduled', 'completed'])],
        ]);

        $appointment = Appointment::query()->create([
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $patient->id,
            'appointment_time' => $validated['appointment_time'],
            'Status' => $validated['Status'] ?? 'pending',
        ]);

        return ApiResponse::success($appointment->fresh(['doctor.user', 'patient.user']), null, 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorizeParticipant($request, $appointment);

        $validated = $request->validate([
            'appointment_time' => ['sometimes', 'date'],
            'Status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'rescheduled', 'completed'])],
        ]);

        if (isset($validated['appointment_time'])) {
            $appointment->appointment_time = $validated['appointment_time'];
        }

        if (isset($validated['Status'])) {
            $appointment->Status = $validated['Status'];
        }

        $appointment->save();

        return ApiResponse::success($appointment->fresh(['doctor.user', 'patient.user']));
    }

    private function authorizeParticipant(Request $request, Appointment $appointment): void
    {
        $user = $request->user();

        if ($user->isPatient() && $user->patient && (int) $user->patient->id === (int) $appointment->patient_id) {
            return;
        }

        if ($user->isDoctor() && $user->doctor && (int) $user->doctor->id === (int) $appointment->doctor_id) {
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        abort(403, 'You cannot modify this appointment.');
    }
}
