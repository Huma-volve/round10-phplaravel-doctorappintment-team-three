<?php

namespace App\Repositories;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
class NotificationRepository
{
    public function paginateForUser(User $user, ?string $type, bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        $query = InAppNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($type) {
            $query->where('type', $type);
        }

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markRead(InAppNotification $notification, User $user): bool
    {
        if ($notification->user_id !== $user->id) {
            return false;
        }

        $notification->markRead();

        return true;
    }

    public function markAllRead(User $user): int
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function create(int $userId, string $title, string $body, string $type, ?int $relatedId = null): InAppNotification
    {
        return InAppNotification::query()->create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'is_read' => false,
            'read_at' => null,
            'related_id' => $relatedId,
        ]);
    }
}
