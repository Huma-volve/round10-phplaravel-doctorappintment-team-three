<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\NotificationDispatcher;
use App\Support\NotificationType;

class ReviewObserver
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {
    }

    public function created(Review $review): void
    {
        $review->loadMissing('doctor.user');

        $doctorUserId = $review->doctor?->user_id;

        if ($doctorUserId) {
            $this->notifications->sendToUser(
                $doctorUserId,
                NotificationType::REVIEW_NEW_FOR_DOCTOR,
                'New review',
                'A patient left a new review for you.',
                $review->id,
            );
        }
    }
}
