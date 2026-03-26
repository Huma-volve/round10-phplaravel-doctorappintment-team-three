<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Review;
use App\Models\SessionFeedback;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReviewAndFeedbackService
{
    public function submitReview(User $user, Appointment $appointment, int $rating, ?string $comment): Review
    {
        $patient = $user->patient;
        if (! $patient || (int) $appointment->patient_id !== (int) $patient->id) {
            abort(403, 'You cannot review this appointment.');
        }

        if ($appointment->Status !== 'completed') {
            throw ValidationException::withMessages([
                'appointment' => ['You can only review a completed session.'],
            ]);
        }

        if ($appointment->reviews()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => ['A review already exists for this appointment.'],
            ]);
        }

        return Review::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_id' => $appointment->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }

    public function submitSessionFeedback(
        User $user,
        Appointment $appointment,
        int $rating,
        ?string $comment,
        ?array $tags,
    ): SessionFeedback {
        $patient = $user->patient;
        if (! $patient || (int) $appointment->patient_id !== (int) $patient->id) {
            abort(403, 'You cannot submit feedback for this appointment.');
        }

        if ($appointment->Status !== 'completed') {
            throw ValidationException::withMessages([
                'appointment' => ['You can only submit feedback after a completed session.'],
            ]);
        }

        if ($appointment->sessionFeedbacks()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => ['Feedback was already submitted for this appointment.'],
            ]);
        }

        return SessionFeedback::query()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'rating' => $rating,
            'comment' => $comment,
            'tags' => $tags,
        ]);
    }

    public function paginateDoctorReviews(Doctor $doctor, int $perPage = 15): LengthAwarePaginator
    {
        return Review::query()
            ->where('doctor_id', $doctor->id)
            ->with('patient.user')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
