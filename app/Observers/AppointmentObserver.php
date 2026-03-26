<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationType;

class AppointmentObserver
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {
    }

    public function created(Appointment $appointment): void
    {
        $appointment->loadMissing('patient.user', 'doctor.user');

        $patientUserId = $appointment->patient?->user_id;
        $doctorUserId = $appointment->doctor?->user_id;

        if ($patientUserId) {
            $this->notifications->sendToUser(
                $patientUserId,
                NotificationType::APPOINTMENT_SUBMITTED_FOR_PATIENT,
                'Appointment request sent',
                'Your appointment request has been submitted.',
                $appointment->id,
            );
        }

        if ($doctorUserId) {
            $this->notifications->sendToUser(
                $doctorUserId,
                NotificationType::APPOINTMENT_NEW_FOR_DOCTOR,
                'New booking',
                'You have a new appointment booking.',
                $appointment->id,
            );
        }

        $adminIds = User::query()->where('role', 'admin')->pluck('id');
        if ($adminIds->isNotEmpty()) {
            $this->notifications->sendToMany(
                $adminIds,
                NotificationType::ADMIN_SYSTEM_ALERT,
                'New appointment',
                'A new appointment was created in the system.',
                $appointment->id,
            );
        }
    }

    public function updated(Appointment $appointment): void
    {
        if (! $appointment->wasChanged('Status') && ! $appointment->wasChanged('appointment_time')) {
            return;
        }

        $appointment->loadMissing('patient.user', 'doctor.user');

        $patientUserId = $appointment->patient?->user_id;
        $doctorUserId = $appointment->doctor?->user_id;

        if ($appointment->wasChanged('Status')) {
            $status = $appointment->Status;

            if ($status === 'cancelled') {
                foreach (array_filter([$patientUserId, $doctorUserId]) as $uid) {
                    $this->notifications->sendToUser(
                        (int) $uid,
                        NotificationType::APPOINTMENT_CANCELLED,
                        'Appointment cancelled',
                        'An appointment has been cancelled.',
                        $appointment->id,
                    );
                }
            }

            if ($status === 'rescheduled') {
                foreach (array_filter([$patientUserId, $doctorUserId]) as $uid) {
                    $this->notifications->sendToUser(
                        (int) $uid,
                        NotificationType::APPOINTMENT_RESCHEDULED,
                        'Appointment rescheduled',
                        'An appointment has been rescheduled.',
                        $appointment->id,
                    );
                }
            }
        }

        if ($appointment->wasChanged('appointment_time') && $appointment->Status !== 'cancelled') {
            foreach (array_filter([$patientUserId, $doctorUserId]) as $uid) {
                $this->notifications->sendToUser(
                    (int) $uid,
                    NotificationType::APPOINTMENT_RESCHEDULED,
                    'Appointment time updated',
                    'Your appointment time has been updated.',
                    $appointment->id,
                );
            }
        }
    }
}
