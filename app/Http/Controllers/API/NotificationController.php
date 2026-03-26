<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InAppNotificationResource;
use App\Models\InAppNotification;
use App\Repositories\NotificationRepository;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $type = $request->input('type');
        $unreadOnly = filter_var($request->input('unread_only', false), FILTER_VALIDATE_BOOLEAN);

        $paginator = $this->notifications->paginateForUser($user, is_string($type) ? $type : null, $unreadOnly, $perPage);

        return ApiResponse::success([
            'data' => InAppNotificationResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $this->notifications->unreadCount($request->user());

        return ApiResponse::success(['unread_count' => $count]);
    }

    public function markRead(Request $request, InAppNotification $inAppNotification)
    {
        if (! $this->notifications->markRead($inAppNotification, $request->user())) {
            return ApiResponse::error('Not found.', 404);
        }

        return ApiResponse::success(new InAppNotificationResource($inAppNotification->fresh()));
    }

    public function markAllRead(Request $request)
    {
        $updated = $this->notifications->markAllRead($request->user());

        return ApiResponse::success(['updated' => $updated]);
    }
}
