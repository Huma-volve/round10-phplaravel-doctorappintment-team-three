<?php

namespace App\Observers;

use App\Models\SessionFeedback;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationType;

class SessionFeedbackObserver
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {
    }

    public function created(SessionFeedback $sessionFeedback): void
    {
        $adminIds = User::query()->where('role', 'admin')->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $this->notifications->sendToMany(
            $adminIds,
            NotificationType::SESSION_FEEDBACK_FOR_ADMIN,
            'Session feedback',
            'A patient submitted session experience feedback.',
            $sessionFeedback->id,
        );
    }
}
