<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\NotificationDispatcher;
use App\Support\NotificationType;
use Illuminate\Console\Command;

class SendAppointmentUpcomingReminders extends Command
{
    protected $signature = 'appointments:send-upcoming-reminders';

    protected $description = 'Notify patients and doctors about confirmed appointments in the next 24 hours (once per appointment).';

    public function handle(NotificationDispatcher $notifications): int
    {
        $start = now()->addHours(23);
        $end = now()->addHours(25);

        $appointments = Appointment::query()
            ->where('Status', 'confirmed')
            ->whereNull('reminder_sent_at')
            ->whereBetween('appointment_time', [$start, $end])
            ->with(['patient.user', 'doctor.user'])
            ->get();

        foreach ($appointments as $appointment) {
            $patientUserId = $appointment->patient?->user_id;
            $doctorUserId = $appointment->doctor?->user_id;

            $when = $appointment->appointment_time?->timezone(config('app.timezone'))->toDayDateTimeString() ?? '';

            foreach (array_filter([$patientUserId, $doctorUserId]) as $uid) {
                $notifications->sendToUser(
                    (int) $uid,
                    NotificationType::APPOINTMENT_UPCOMING,
                    'Upcoming appointment',
                    'Reminder: you have an appointment soon at '.$when.'.',
                    $appointment->id,
                );
            }

            $appointment->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info('Processed '.$appointments->count().' appointment reminders.');

        return self::SUCCESS;
    }
}
