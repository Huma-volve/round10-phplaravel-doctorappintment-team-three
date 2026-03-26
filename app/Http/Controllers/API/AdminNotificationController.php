<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminBroadcastNotificationRequest;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\ApiResponse;
use App\Support\NotificationType;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {
    }

    public function broadcast(AdminBroadcastNotificationRequest $request)
    {
        $audience = $request->validated('audience');

        $userIds = match ($audience) {
            'all' => User::query()->pluck('id'),
            'patients' => User::query()->where('role', 'patient')->pluck('id'),
            'doctors' => User::query()->where('role', 'doctor')->pluck('id'),
            'admins' => User::query()->where('role', 'admin')->pluck('id'),
            default => collect(),
        };

        $this->dispatcher->sendToMany(
            $userIds,
            NotificationType::ADMIN_BROADCAST,
            $request->validated('title'),
            $request->validated('body'),
        );

        return ApiResponse::success(['sent' => $userIds->count()]);
    }
}
