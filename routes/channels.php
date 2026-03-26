<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::query()->find($chatId);

    if (! $chat) {
        return false;
    }

    $chat->loadMissing('patient', 'doctor');

    return $chat->patient?->user_id === $user->id || $chat->doctor?->user_id === $user->id;
});
