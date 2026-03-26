<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\NotificationDispatcher;
use App\Support\NotificationType;

class PaymentObserver
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {
    }

    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged('payment_status')) {
            return;
        }

        if ($payment->payment_status !== 'paid') {
            return;
        }

        $payment->loadMissing('appointment.doctor.user');

        $doctorUserId = $payment->appointment?->doctor?->user_id;

        if ($doctorUserId) {
            $this->notifications->sendToUser(
                $doctorUserId,
                NotificationType::PAYMENT_UPDATE_FOR_DOCTOR,
                'Payment received',
                'A payment for an appointment was marked as paid.',
                $payment->id,
            );
        }
    }
}
