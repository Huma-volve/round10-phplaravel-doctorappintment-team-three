<?php

namespace App\Services;

use App\Events\InAppNotificationCreated;
use App\Models\InAppNotification;
use App\Repositories\NotificationRepository;

class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRepository $notifications,
    ) {
    }

    public function sendToUser(int $userId, string $type, string $title, string $body, ?int $relatedId = null): InAppNotification
    {
        $record = $this->notifications->create($userId, $title, $body, $type, $relatedId);

        broadcast(new InAppNotificationCreated($record));

        return $record;
    }

    /**
     * @param  iterable<int>  $userIds
     * @return \Illuminate\Support\Collection<int, InAppNotification>
     */
    public function sendToMany(iterable $userIds, string $type, string $title, string $body, ?int $relatedId = null): \Illuminate\Support\Collection
    {
        $out = collect();

        foreach ($userIds as $id) {
            $out->push($this->sendToUser((int) $id, $type, $title, $body, $relatedId));
        }

        return $out;
    }
}
