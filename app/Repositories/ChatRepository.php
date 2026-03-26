<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ChatRepository
{
    public function findForUser(int $chatId, User $user): ?Chat
    {
        return Chat::query()
            ->whereKey($chatId)
            ->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id))
            ->first();
    }

    /**
     * @return Builder<Chat>
     */
    public function queryForUser(User $user): Builder
    {
        return Chat::query()
            ->join('chat_user', 'chats.id', '=', 'chat_user.chat_id')
            ->where('chat_user.user_id', $user->id)
            ->select('chats.*')
            ->addSelect([
                'chat_user.is_favorite as pivot_is_favorite',
                'chat_user.is_archived as pivot_is_archived',
                'chat_user.last_read_at as pivot_last_read_at',
            ]);
    }

    public function paginateForUser(User $user, ?bool $archived, ?bool $favorite, int $perPage): LengthAwarePaginator
    {
        $q = $this->queryForUser($user);

        if ($archived === true) {
            $q->where('chat_user.is_archived', true);
        } elseif ($archived === false) {
            $q->where('chat_user.is_archived', false);
        }

        if ($favorite === true) {
            $q->where('chat_user.is_favorite', true);
        } elseif ($favorite === false) {
            $q->where('chat_user.is_favorite', false);
        }

        return $q
            ->orderByDesc('chats.updated_at')
            ->with(['doctor.user', 'patient.user', 'appointment'])
            ->paginate($perPage);
    }

    public function firstOrCreateChat(int $patientId, int $doctorId, ?int $appointmentId): Chat
    {
        return Chat::query()->firstOrCreate(
            [
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_id' => $appointmentId,
            ],
        );
    }

    public function syncParticipants(Chat $chat): void
    {
        $chat->loadMissing('patient.user', 'doctor.user');

        $patientUserId = $chat->patient?->user_id;
        $doctorUserId = $chat->doctor?->user_id;

        foreach (array_filter([$patientUserId, $doctorUserId]) as $userId) {
            $chat->users()->syncWithoutDetaching([
                $userId => [
                    'is_favorite' => false,
                    'is_archived' => false,
                ],
            ]);
        }
    }

    public function unreadCountForUser(Chat $chat, User $user): int
    {
        $pivot = $chat->users()->where('users.id', $user->id)->first()?->pivot;
        $lastRead = $pivot?->last_read_at;

        return Message::query()
            ->where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->when($lastRead, fn (Builder $q) => $q->where('messages.created_at', '>', $lastRead))
            ->count();
    }

    public function paginateMessages(Chat $chat, int $perPage): LengthAwarePaginator
    {
        return Message::query()
            ->where('chat_id', $chat->id)
            ->with('sender:id,name')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function updatePivot(Chat $chat, User $user, array $attributes): void
    {
        $chat->users()->updateExistingPivot($user->id, $attributes);
    }
}
